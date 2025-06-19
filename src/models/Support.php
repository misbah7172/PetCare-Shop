<?php
class Support {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Ticket CRUD
    public function createTicket($user_id, $subject, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $subject, $message]);
    }
    public function getUserTickets($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllTickets() {
        $stmt = $this->pdo->query("SELECT st.*, u.username FROM support_tickets st JOIN users u ON st.user_id = u.id ORDER BY st.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getTicket($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM support_tickets WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateTicketStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    public function deleteTicket($id) {
        $stmt = $this->pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Replies
    public function addReply($ticket_id, $user_id, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO support_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
        return $stmt->execute([$ticket_id, $user_id, $message]);
    }
    public function getReplies($ticket_id) {
        $stmt = $this->pdo->prepare("SELECT sr.*, u.username FROM support_replies sr JOIN users u ON sr.user_id = u.id WHERE sr.ticket_id = ? ORDER BY sr.created_at ASC");
        $stmt->execute([$ticket_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 