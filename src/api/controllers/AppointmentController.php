<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AppointmentController {
    private $db;
    private $authMiddleware;

    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->authMiddleware = new AuthMiddleware($database);
    }

    public function handleRequest($method, $segments) {
        switch ($method) {
            case 'GET':
                if (empty($segments)) {
                    return $this->getAppointments();
                } elseif ($segments[0] === 'available-slots') {
                    return $this->getAvailableSlots();
                } else {
                    return $this->getAppointment($segments[0]);
                }
                break;
            case 'POST':
                return $this->createAppointment();
                break;
            case 'PUT':
                return $this->updateAppointment($segments[0]);
                break;
            case 'DELETE':
                return $this->cancelAppointment($segments[0]);
                break;
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }

    public function getAppointments() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "SELECT a.*, v.name as vet_name, v.specialization, u.name as user_name 
                  FROM appointments a 
                  JOIN veterinarians v ON a.vet_id = v.id 
                  JOIN users u ON a.user_id = u.id";
        
        $params = [];
        
        if ($user['role'] !== 'admin') {
            $query .= " WHERE a.user_id = ? OR a.vet_id = (SELECT id FROM veterinarians WHERE user_id = ?)";
            $params = [$user['id'], $user['id']];
        }
        
        $query .= " ORDER BY a.appointment_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['appointments' => $appointments];
    }

    public function getAppointment($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "SELECT a.*, v.name as vet_name, v.specialization, v.phone as vet_phone, 
                         u.name as user_name, u.phone as user_phone
                  FROM appointments a 
                  JOIN veterinarians v ON a.vet_id = v.id 
                  JOIN users u ON a.user_id = u.id 
                  WHERE a.id = ?";
        
        $params = [$id];
        
        if ($user['role'] !== 'admin') {
            $query .= " AND (a.user_id = ? OR a.vet_id = (SELECT id FROM veterinarians WHERE user_id = ?))";
            $params[] = $user['id'];
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            http_response_code(404);
            return ['error' => 'Appointment not found'];
        }

        return ['appointment' => $appointment];
    }

    public function getAvailableSlots() {
        $vet_id = $_GET['vet_id'] ?? null;
        $date = $_GET['date'] ?? null;

        if (!$vet_id || !$date) {
            http_response_code(400);
            return ['error' => 'vet_id and date are required'];
        }

        // Check vet availability
        $availabilityQuery = "SELECT * FROM vet_availability 
                             WHERE vet_id = ? AND day_of_week = ? AND is_available = 1";
        $dayOfWeek = date('l', strtotime($date));
        
        $stmt = $this->db->prepare($availabilityQuery);
        $stmt->execute([$vet_id, $dayOfWeek]);
        $availability = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$availability) {
            return ['available_slots' => []];
        }

        // Get booked appointments for that date
        $bookedQuery = "SELECT appointment_time FROM appointments 
                       WHERE vet_id = ? AND DATE(appointment_date) = ? AND status != 'cancelled'";
        $stmt = $this->db->prepare($bookedQuery);
        $stmt->execute([$vet_id, $date]);
        $bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Generate available time slots
        $startTime = new DateTime($availability['start_time']);
        $endTime = new DateTime($availability['end_time']);
        $interval = new DateInterval('PT30M'); // 30-minute slots
        $slots = [];

        while ($startTime < $endTime) {
            $timeString = $startTime->format('H:i:s');
            if (!in_array($timeString, $bookedTimes)) {
                $slots[] = $timeString;
            }
            $startTime->add($interval);
        }

        return ['available_slots' => $slots];
    }

    public function createAppointment() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['vet_id', 'appointment_date', 'appointment_time', 'reason'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                return ['error' => "Field '$field' is required"];
            }
        }

        // Check if the time slot is available
        $checkQuery = "SELECT id FROM appointments 
                      WHERE vet_id = ? AND DATE(appointment_date) = ? AND appointment_time = ? 
                      AND status != 'cancelled'";
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute([$input['vet_id'], date('Y-m-d', strtotime($input['appointment_date'])), $input['appointment_time']]);
        
        if ($stmt->fetch()) {
            http_response_code(409);
            return ['error' => 'Time slot is not available'];
        }

        $query = "INSERT INTO appointments (user_id, vet_id, appointment_date, appointment_time, reason, notes, status) 
                  VALUES (?, ?, ?, ?, ?, ?, 'scheduled')";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $user['id'],
            $input['vet_id'],
            $input['appointment_date'],
            $input['appointment_time'],
            $input['reason'],
            $input['notes'] ?? null
        ]);

        if ($success) {
            $appointmentId = $this->db->lastInsertId();
            http_response_code(201);
            return ['message' => 'Appointment created successfully', 'appointment_id' => $appointmentId];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to create appointment'];
        }
    }

    public function updateAppointment($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Check if appointment exists and user has permission
        $checkQuery = "SELECT * FROM appointments WHERE id = ?";
        $params = [$id];
        
        if ($user['role'] !== 'admin') {
            $checkQuery .= " AND (user_id = ? OR vet_id = (SELECT id FROM veterinarians WHERE user_id = ?))";
            $params[] = $user['id'];
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute($params);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            http_response_code(404);
            return ['error' => 'Appointment not found'];
        }

        $updates = [];
        $values = [];
        
        $allowedFields = ['appointment_date', 'appointment_time', 'reason', 'notes', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }

        if (empty($updates)) {
            http_response_code(400);
            return ['error' => 'No valid fields to update'];
        }

        $values[] = $id;
        $query = "UPDATE appointments SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute($values);

        if ($success) {
            return ['message' => 'Appointment updated successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to update appointment'];
        }
    }

    public function cancelAppointment($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        // Check if appointment exists and user has permission
        $checkQuery = "SELECT * FROM appointments WHERE id = ?";
        $params = [$id];
        
        if ($user['role'] !== 'admin') {
            $checkQuery .= " AND (user_id = ? OR vet_id = (SELECT id FROM veterinarians WHERE user_id = ?))";
            $params[] = $user['id'];
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute($params);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            http_response_code(404);
            return ['error' => 'Appointment not found'];
        }

        $query = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$id]);

        if ($success) {
            return ['message' => 'Appointment cancelled successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to cancel appointment'];
        }
    }
}
?>
