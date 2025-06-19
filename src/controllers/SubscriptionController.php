<?php
require_once __DIR__ . '/../models/Subscription.php';

class SubscriptionController {
    private $subscription;
    public function __construct($pdo) {
        $this->subscription = new Subscription($pdo);
    }

    // Subscriptions CRUD
    public function createSubscription($user_id, $plan_type, $start_date, $end_date) {
        if (!$user_id || !$plan_type || !$start_date || !$end_date) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->subscription->createSubscription($user_id, $plan_type, $start_date, $end_date);
        return ['success' => $result];
    }
    public function getUserSubscriptions($user_id) {
        return $this->subscription->getUserSubscriptions($user_id);
    }
    public function getAllSubscriptions() {
        return $this->subscription->getAllSubscriptions();
    }
    public function updateSubscriptionStatus($id, $status) {
        if (!in_array($status, ['active', 'expired', 'cancelled'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        $result = $this->subscription->updateSubscriptionStatus($id, $status);
        return ['success' => $result];
    }
    public function deleteSubscription($id) {
        $result = $this->subscription->deleteSubscription($id);
        return ['success' => $result];
    }

    // Subscription Boxes
    public function getAllBoxes() {
        return $this->subscription->getAllBoxes();
    }
    public function subscribeBox($user_id, $box_id, $start_date, $end_date) {
        if (!$user_id || !$box_id || !$start_date || !$end_date) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->subscription->subscribeBox($user_id, $box_id, $start_date, $end_date);
        return ['success' => $result];
    }
    public function getUserBoxes($user_id) {
        return $this->subscription->getUserBoxes($user_id);
    }
    public function updateBoxStatus($id, $status) {
        if (!in_array($status, ['active', 'expired', 'cancelled'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        $result = $this->subscription->updateBoxStatus($id, $status);
        return ['success' => $result];
    }
    public function unsubscribeBox($id) {
        $result = $this->subscription->unsubscribeBox($id);
        return ['success' => $result];
    }
} 