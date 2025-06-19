<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database Structure - PawConnect</title>
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
        .warning {
            color: #856404;
            background: #fff3cd;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Database Structure</h1>
        
        <?php
        try {
            require_once '../config/database.php';
            $database = new Database();
            $pdo = $database->getConnection();
            
            echo "<div class='success'>✓ Connected to database successfully</div>";
            
            // Check current users table structure
            echo "<h3>Current Users Table Structure:</h3>";
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            $existingColumns = [];
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "</tr>";
                $existingColumns[] = $column['Field'];
            }
            echo "</table>";
            
            // Check for missing columns
            $requiredColumns = [
                'first_name' => 'VARCHAR(50)',
                'last_name' => 'VARCHAR(50)', 
                'phone' => 'VARCHAR(20)',
                'status' => "ENUM('active', 'inactive', 'suspended') DEFAULT 'active'",
                'last_login' => 'TIMESTAMP NULL'
            ];
            
            $missingColumns = [];
            echo "<h3>Required Columns Check:</h3>";
            foreach ($requiredColumns as $column => $type) {
                if (in_array($column, $existingColumns)) {
                    echo "<div class='success'>✓ $column - Found</div>";
                } else {
                    echo "<div class='error'>❌ $column - Missing</div>";
                    $missingColumns[$column] = $type;
                }
            }
            
            // Add missing columns
            if (!empty($missingColumns)) {
                echo "<h3>Adding Missing Columns:</h3>";
                
                foreach ($missingColumns as $column => $type) {
                    try {
                        $sql = "ALTER TABLE users ADD COLUMN $column $type";
                        $pdo->exec($sql);
                        echo "<div class='success'>✓ Added column: $column</div>";
                    } catch (PDOException $e) {
                        echo "<div class='error'>❌ Failed to add $column: " . $e->getMessage() . "</div>";
                    }
                }
                
                echo "<div class='success'><strong>🎉 Database structure updated!</strong></div>";
            } else {
                echo "<div class='success'><strong>✓ All required columns are present!</strong></div>";
            }
            
            // Test registration process
            echo "<h3>Test Registration Process:</h3>";
            try {
                $testUsername = 'test_' . time();
                $testEmail = 'test_' . time() . '@test.com';
                $testPassword = password_hash('test123', PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name, phone, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'user', 'active', NOW())
                ");
                
                $result = $stmt->execute([$testUsername, $testEmail, $testPassword, 'Test', 'User', '1234567890']);
                
                if ($result) {
                    echo "<div class='success'>✓ Test registration successful</div>";
                    
                    // Clean up test data
                    $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$testUsername]);
                    echo "<div class='success'>✓ Test data cleaned up</div>";
                    
                    echo "<div class='success'><strong>🎉 Registration should now work properly!</strong></div>";
                } else {
                    echo "<div class='error'>❌ Test registration failed</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Test registration error: " . $e->getMessage() . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
            echo "<div class='warning'>Make sure XAMPP MySQL service is running</div>";
        }
        ?>
        
        <hr>
        <h3>Next Steps:</h3>
        <a href="../public/register.html" class="btn">Try Registration Now</a>
        <a href="../public/login.html" class="btn">Login Page</a>
        <a href="test_database.php" class="btn">Test Database</a>
        <a href="../public/index.html" class="btn">Go to Homepage</a>
    </div>
</body>
</html>
