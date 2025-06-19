<?php
require_once __DIR__ . '/../models/VetAppointment.php';

class VetAppointmentController {
    private $vetAppointment;
    public function __construct($pdo) {
        $this->vetAppointment = new VetAppointment($pdo);
    }

    // Create a vet profile
    public function createVet($user_id, $name, $specialization, $contact_info) {
        if (!$user_id || !$name) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->vetAppointment->createVet($user_id, $name, $specialization, $contact_info);
        return ['success' => $result];
    }

    // Get all vets
    public function listVets() {
        return $this->vetAppointment->getAllVets();
    }

    // Book a vet appointment
    public function book($pet_id, $vet_id, $user_id, $appointment_time, $notes) {
        if (!$pet_id || !$vet_id || !$user_id || !$appointment_time) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->vetAppointment->bookAppointment($pet_id, $vet_id, $user_id, $appointment_time, $notes);
        return ['success' => $result];
    }

    // Get all appointments for a user
    public function userAppointments($user_id) {
        return $this->vetAppointment->getUserAppointments($user_id);
    }

    // Get all appointments for a vet
    public function vetAppointments($vet_id) {
        return $this->vetAppointment->getVetAppointments($vet_id);
    }

    // Update appointment status
    public function updateStatus($appointment_id, $status) {
        if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        $result = $this->vetAppointment->updateStatus($appointment_id, $status);
        return ['success' => $result];
    }

    // Delete an appointment
    public function delete($appointment_id) {
        $result = $this->vetAppointment->deleteAppointment($appointment_id);
        return ['success' => $result];
    }
} 