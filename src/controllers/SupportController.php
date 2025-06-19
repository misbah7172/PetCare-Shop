<?php
require_once __DIR__ . '/../models/Support.php';

class SupportController {
    private $support;
    public function __construct($pdo) {
        $this->support = new Support($pdo);
    }

    // Ticket CRUD
    public function createTicket($user_id, $subject, $message) {
        if (!$user_id || !$subject || !$message) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->support->createTicket($user_id, $subject, $message);
        return ['success' => $result];
    }
    public function getUserTickets($user_id) {
        return $this->support->getUserTickets($user_id);
    }
    public function getAllTickets() {
        return $this->support->getAllTickets();
    }
    public function getTicket($id) {
        return $this->support->getTicket($id);
    }
    public function updateTicketStatus($id, $status) {
        if (!in_array($status, ['open', 'pending', 'closed'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        $result = $this->support->updateTicketStatus($id, $status);
        return ['success' => $result];
    }
    public function deleteTicket($id) {
        $result = $this->support->deleteTicket($id);
        return ['success' => $result];
    }

    // Replies
    public function addReply($ticket_id, $user_id, $message) {
        if (!$ticket_id || !$user_id || !$message) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->support->addReply($ticket_id, $user_id, $message);
        return ['success' => $result];
    }
    public function getReplies($ticket_id) {
        return $this->support->getReplies($ticket_id);
    }
} 