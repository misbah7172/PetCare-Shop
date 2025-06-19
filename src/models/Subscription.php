<?php
class Subscription {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Subscriptions CRUD
    public function createSubscription($user_id, $plan_type, $start_date, $end_date) {
        $stmt = $this->pdo->prepare("INSERT INTO subscriptions (user_id, plan_type, start_date, end_date) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $plan_type, $start_date, $end_date]);
    }
    public function getUserSubscriptions($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllSubscriptions() {
        $stmt = $this->pdo->query("SELECT s.*, u.username FROM subscriptions s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updateSubscriptionStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE subscriptions SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    public function deleteSubscription($id) {
        $stmt = $this->pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Subscription Boxes
    public function getAllBoxes() {
        $stmt = $this->pdo->query("SELECT * FROM subscription_boxes WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function subscribeBox($user_id, $box_id, $start_date, $end_date) {
        $stmt = $this->pdo->prepare("INSERT INTO user_subscription_boxes (user_id, box_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $box_id, $start_date, $end_date]);
    }
    public function getUserBoxes($user_id) {
        $stmt = $this->pdo->prepare("SELECT usb.*, sb.name AS box_name FROM user_subscription_boxes usb JOIN subscription_boxes sb ON usb.box_id = sb.id WHERE usb.user_id = ? ORDER BY usb.created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updateBoxStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE user_subscription_boxes SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    public function unsubscribeBox($id) {
        $stmt = $this->pdo->prepare("DELETE FROM user_subscription_boxes WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 