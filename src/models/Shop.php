<?php
class Shop {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Product CRUD
    public function createProduct($name, $category, $description, $price, $stock, $image_url) {
        $stmt = $this->pdo->prepare("INSERT INTO shop_products (name, category, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $category, $description, $price, $stock, $image_url]);
    }
    public function getAllProducts() {
        $stmt = $this->pdo->query("SELECT * FROM shop_products WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProduct($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM shop_products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateProduct($id, $name, $category, $description, $price, $stock, $image_url) {
        $stmt = $this->pdo->prepare("UPDATE shop_products SET name=?, category=?, description=?, price=?, stock=?, image_url=? WHERE id=?");
        return $stmt->execute([$name, $category, $description, $price, $stock, $image_url, $id]);
    }
    public function deleteProduct($id) {
        $stmt = $this->pdo->prepare("UPDATE shop_products SET is_active=0 WHERE id=?");
        return $stmt->execute([$id]);
    }

    // Order CRUD
    public function createOrder($user_id, $items, $total) {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare("INSERT INTO shop_orders (user_id, total) VALUES (?, ?)");
        $stmt->execute([$user_id, $total]);
        $order_id = $this->pdo->lastInsertId();
        $item_stmt = $this->pdo->prepare("INSERT INTO shop_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            // Optionally update stock
            $this->pdo->prepare("UPDATE shop_products SET stock = stock - ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
        }
        $this->pdo->commit();
        return $order_id;
    }
    public function getUserOrders($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM shop_orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getOrder($order_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM shop_orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order) {
            $item_stmt = $this->pdo->prepare("SELECT oi.*, p.name AS product_name FROM shop_order_items oi JOIN shop_products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $item_stmt->execute([$order_id]);
            $order['items'] = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $order;
    }
    public function updateOrderStatus($order_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE shop_orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $order_id]);
    }
    public function deleteOrder($order_id) {
        $stmt = $this->pdo->prepare("DELETE FROM shop_orders WHERE id = ?");
        return $stmt->execute([$order_id]);
    }
} 