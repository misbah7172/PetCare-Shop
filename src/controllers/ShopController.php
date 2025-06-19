<?php
require_once __DIR__ . '/../models/Shop.php';

class ShopController {
    private $shop;
    public function __construct($pdo) {
        $this->shop = new Shop($pdo);
    }

    // Product CRUD
    public function createProduct($name, $category, $description, $price, $stock, $image_url) {
        if (!$name || !$category || !$price) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->shop->createProduct($name, $category, $description, $price, $stock, $image_url);
        return ['success' => $result];
    }
    public function listProducts() {
        return $this->shop->getAllProducts();
    }
    public function getProduct($id) {
        return $this->shop->getProduct($id);
    }
    public function updateProduct($id, $name, $category, $description, $price, $stock, $image_url) {
        $result = $this->shop->updateProduct($id, $name, $category, $description, $price, $stock, $image_url);
        return ['success' => $result];
    }
    public function deleteProduct($id) {
        $result = $this->shop->deleteProduct($id);
        return ['success' => $result];
    }

    // Order CRUD
    public function createOrder($user_id, $items, $total) {
        if (!$user_id || !is_array($items) || !$total) return ['success' => false, 'error' => 'Missing required fields'];
        $order_id = $this->shop->createOrder($user_id, $items, $total);
        return ['success' => (bool)$order_id, 'order_id' => $order_id];
    }
    public function listUserOrders($user_id) {
        return $this->shop->getUserOrders($user_id);
    }
    public function getOrder($order_id) {
        return $this->shop->getOrder($order_id);
    }
    public function updateOrderStatus($order_id, $status) {
        $result = $this->shop->updateOrderStatus($order_id, $status);
        return ['success' => $result];
    }
    public function deleteOrder($order_id) {
        $result = $this->shop->deleteOrder($order_id);
        return ['success' => $result];
    }
} 