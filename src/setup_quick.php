<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawConnect Database Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐾 PawConnect Database Setup</h1>
        
        <?php        // Database configuration
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $dbname = 'pawconnect_db';

        try {
            echo "<div class='success'>Starting database setup...</div>";
            
            // Create connection without database
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<div class='success'>✓ Connected to MySQL server successfully</div>";
            
            // Create database if it doesn't exist
            $sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);
            echo "<div class='success'>✓ Database '$dbname' created successfully</div>";
              // Connect to the specific database
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<div class='success'>✓ Connected to database successfully</div>";
            
            // Read and execute the SQL migration file
            $sqlFile = '../database/migrations/001_unified_core.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                
                // Split the SQL file into individual statements
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                $executed = 0;
                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                        try {
                            $pdo->exec($statement);
                            $executed++;
                        } catch (PDOException $e) {
                            // Skip if table already exists or other non-critical errors
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                echo "<div class='error'>Warning: " . $e->getMessage() . "</div>";
                            }
                        }
                    }
                }
                
                echo "<div class='success'>✓ Executed $executed SQL statements from migration file</div>";
            } else {
                // Fallback: Create tables manually
                echo "<div class='success'>Migration file not found, creating tables manually...</div>";
                
                // Create users table with all required fields
                $sql = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    first_name VARCHAR(50),
                    last_name VARCHAR(50),
                    phone VARCHAR(20),
                    role ENUM('user', 'admin', 'vet', 'premium', 'moderator') DEFAULT 'user',
                    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL
                )";
                $pdo->exec($sql);
                echo "<div class='success'>✓ Users table created successfully</div>";
                
                // Create other essential tables
                $sql = "CREATE TABLE IF NOT EXISTS pets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    type ENUM('dog', 'cat', 'bird', 'fish', 'reptile', 'small_animal', 'other') NOT NULL,
                    breed VARCHAR(100),
                    age INT,
                    age_unit ENUM('weeks', 'months', 'years') DEFAULT 'years',
                    gender ENUM('male', 'female', 'unknown') DEFAULT 'unknown',
                    description TEXT,
                    photo_url VARCHAR(255),
                    is_public BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                $pdo->exec($sql);
                echo "<div class='success'>✓ Pets table created successfully</div>";
                
                $sql = "CREATE TABLE IF NOT EXISTS transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    type ENUM('donation', 'purchase', 'subscription', 'appointment') NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    description TEXT,
                    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
                    payment_method VARCHAR(50),
                    transaction_reference VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )";
                $pdo->exec($sql);
                echo "<div class='success'>✓ Transactions table created successfully</div>";
                
                $sql = "CREATE TABLE IF NOT EXISTS appointments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    vet_id INT,
                    pet_id INT,
                    appointment_date DATETIME NOT NULL,
                    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled') DEFAULT 'scheduled',
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (vet_id) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE SET NULL
                )";
                $pdo->exec($sql);
                echo "<div class='success'>✓ Appointments table created successfully</div>";
            }
            
            // Create a default admin user if none exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute(['admin', 'admin@pawconnect.com', $adminPassword, 'Admin', 'User', 'admin']);
                echo "<div class='success'>✓ Default admin user created (username: admin, password: admin123)</div>";
            }
            
            echo "<div class='success'><strong>🎉 Database setup completed successfully!</strong></div>";
            echo "<p><strong>Your PawConnect application is now ready to use!</strong></p>";
            
        } catch(PDOException $e) {
            echo "<div class='error'>❌ Database setup failed: " . $e->getMessage() . "</div>";
            echo "<div class='error'>Please make sure XAMPP MySQL service is running and try again.</div>";
        }
        ?>
        
        <hr>
        <h3>Next Steps:</h3>
        <a href="../public/index.html" class="btn">Go to Homepage</a>
        <a href="../public/register.html" class="btn">Register New Account</a>
        <a href="../public/login.html" class="btn">Login</a>
        <a href="test.php" class="btn">Test System</a>
        
        <hr>
        <p><strong>Default Admin Account:</strong></p>
        <ul>
            <li>Username: <code>admin</code></li>
            <li>Password: <code>admin123</code></li>
            <li>You can change this after logging in</li>
        </ul>
    </div>
</body>
</html>
