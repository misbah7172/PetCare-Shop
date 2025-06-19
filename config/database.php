<?php
// Database Configuration for PawConnect
// Central database connection file

class Database {
    private $host = 'localhost';
    private $db_name = 'pawconnect_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo;

    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                  $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $e) {                // Check if the error is about unknown database
                if (strpos($e->getMessage(), 'Unknown database') !== false) {
                    die("Database 'pawconnect_db' does not exist. Please run the setup first: <a href='../src/setup_quick.php'>Setup Database</a>");
                } else {
                    die("Database connection failed: " . $e->getMessage());
                }
            }
        }
        return $this->pdo;
    }
}
?>
