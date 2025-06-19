<?php
class VetAppointment {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a vet profile
    public function createVet($user_id, $name, $specialization, $contact_info) {
        $stmt = $this->pdo->prepare("INSERT INTO vets (user_id, name, specialization, contact_info) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $name, $specialization, $contact_info]);
    }

    // Get all vets
    public function getAllVets() {
        $stmt = $this->pdo->query("SELECT * FROM vets WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Book a vet appointment
    public function bookAppointment($pet_id, $vet_id, $user_id, $appointment_time, $notes) {
        $stmt = $this->pdo->prepare("INSERT INTO vet_appointments (pet_id, vet_id, user_id, appointment_time, notes) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$pet_id, $vet_id, $user_id, $appointment_time, $notes]);
    }

    // Get all appointments for a user
    public function getUserAppointments($user_id) {
        $stmt = $this->pdo->prepare("SELECT va.*, v.name AS vet_name, p.name AS pet_name FROM vet_appointments va JOIN vets v ON va.vet_id = v.id JOIN pets p ON va.pet_id = p.id WHERE va.user_id = ? ORDER BY va.appointment_time DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all appointments for a vet
    public function getVetAppointments($vet_id) {
        $stmt = $this->pdo->prepare("SELECT va.*, u.username AS user_name, p.name AS pet_name FROM vet_appointments va JOIN users u ON va.user_id = u.id JOIN pets p ON va.pet_id = p.id WHERE va.vet_id = ? ORDER BY va.appointment_time DESC");
        $stmt->execute([$vet_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update appointment status
    public function updateStatus($appointment_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE vet_appointments SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $appointment_id]);
    }

    // Delete an appointment
    public function deleteAppointment($appointment_id) {
        $stmt = $this->pdo->prepare("DELETE FROM vet_appointments WHERE id = ?");
        return $stmt->execute([$appointment_id]);
    }
} 