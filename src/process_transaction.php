<?php
// Database configuration
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

// Email configuration
$admin_email = 'admin@pawconnect.com';
$from_email = 'noreply@pawconnect.com';

// Start session
session_start();

// Set headers
header('Content-Type: application/json');

// Function to connect to database
function connectDB() {
    global $host, $dbname, $username, $password;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to create database tables if they don't exist
function createTables($pdo) {
    // Transactions table
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id VARCHAR(50) UNIQUE NOT NULL,
        payment_method VARCHAR(20) NOT NULL,
        transaction_type VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        mobile_number VARCHAR(15),
        card_name VARCHAR(100),
        card_number VARCHAR(20),
        email VARCHAR(100) NOT NULL,
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        zipcode VARCHAR(10),
        country VARCHAR(10),
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to generate unique transaction ID
function generateTransactionID() {
    return 'PC-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Function to send email
function sendEmail($to, $subject, $message) {
    global $from_email;
    
    $headers = "From: $from_email\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Function to validate mobile banking transaction
function validateMobileTransaction($payment_method, $transaction_id) {
    // In a real application, you would integrate with the mobile banking APIs
    // For now, we'll simulate validation
    $valid_prefixes = [
        'bkash' => ['TRX', 'BK'],
        'nagad' => ['NAG', 'NG'],
        'rocket' => ['RKT', 'RK']
    ];
    
    if (isset($valid_prefixes[$payment_method])) {
        foreach ($valid_prefixes[$payment_method] as $prefix) {
            if (strpos($transaction_id, $prefix) === 0) {
                return true;
            }
        }
    }
    
    return false;
}

// Function to validate card payment
function validateCardPayment($card_number, $expiry, $cvv) {
    // Remove spaces from card number
    $card_number = str_replace(' ', '', $card_number);
    
    // Basic validation
    if (strlen($card_number) < 13 || strlen($card_number) > 19) {
        return false;
    }
    
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        return false;
    }
    
    if (strlen($cvv) < 3 || strlen($cvv) > 4) {
        return false;
    }
    
    // Check if card is expired
    $expiry_parts = explode('/', $expiry);
    $expiry_month = $expiry_parts[0];
    $expiry_year = '20' . $expiry_parts[1];
    
    $current_month = date('m');
    $current_year = date('Y');
    
    if ($expiry_year < $current_year || ($expiry_year == $current_year && $expiry_month < $current_month)) {
        return false;
    }
    
    return true;
}

// Main processing logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to database
        $pdo = connectDB();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        // Create tables if they don't exist
        if (!createTables($pdo)) {
            throw new Exception('Failed to create database tables');
        }
        
        // Get form data
        $payment_method = $_POST['payment_method'] ?? '';
        $transaction_type = $_POST['transaction_type'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $email = $_POST['email'] ?? '';
        
        // Validate required fields
        if (empty($payment_method) || empty($transaction_type) || empty($amount) || empty($email)) {
            throw new Exception('Missing required fields');
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
        
        // Process based on payment method
        $transaction_id = generateTransactionID();
        $status = 'pending';
        $validation_message = '';
        
        if ($payment_method === 'card') {
            // Card payment validation
            $card_name = $_POST['card_name'] ?? '';
            $card_number = $_POST['card_number'] ?? '';
            $expiry = $_POST['expiry'] ?? '';
            $cvv = $_POST['cvv'] ?? '';
            
            if (empty($card_name) || empty($card_number) || empty($expiry) || empty($cvv)) {
                throw new Exception('Missing card details');
            }
            
            if (!validateCardPayment($card_number, $expiry, $cvv)) {
                throw new Exception('Invalid card details');
            }
            
            $status = 'completed';
            $validation_message = 'Card payment validated successfully';
            
        } else {
            // Mobile banking validation
            $mobile_number = $_POST['mobile_number'] ?? '';
            $user_transaction_id = $_POST['transaction_id'] ?? '';
            
            if (empty($mobile_number) || empty($user_transaction_id)) {
                throw new Exception('Missing mobile banking details');
            }
            
            if (!validateMobileTransaction($payment_method, $user_transaction_id)) {
                throw new Exception('Invalid transaction ID for ' . ucfirst($payment_method));
            }
            
            $status = 'completed';
            $validation_message = ucfirst($payment_method) . ' transaction validated successfully';
        }
        
        // Get billing address
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = $_POST['state'] ?? '';
        $zipcode = $_POST['zipcode'] ?? '';
        $country = $_POST['country'] ?? '';
        
        // Insert transaction into database
        $sql = "INSERT INTO transactions (
            transaction_id, payment_method, transaction_type, amount, 
            mobile_number, card_name, card_number, email, 
            address, city, state, zipcode, country, status
        ) VALUES (
            :transaction_id, :payment_method, :transaction_type, :amount,
            :mobile_number, :card_name, :card_number, :email,
            :address, :city, :state, :zipcode, :country, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'transaction_id' => $transaction_id,
            'payment_method' => $payment_method,
            'transaction_type' => $transaction_type,
            'amount' => $amount,
            'mobile_number' => $mobile_number ?? null,
            'card_name' => $card_name ?? null,
            'card_number' => $card_number ? substr($card_number, -4) : null, // Store only last 4 digits
            'email' => $email,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zipcode' => $zipcode,
            'country' => $country,
            'status' => $status
        ]);
        
        // Send confirmation email
        $email_subject = "Payment Confirmation - PawConnect";
        $email_message = "
        <html>
        <head>
            <title>Payment Confirmation</title>
        </head>
        <body>
            <h2>Payment Confirmation</h2>
            <p>Dear Customer,</p>
            <p>Your payment has been processed successfully.</p>
            <p><strong>Transaction Details:</strong></p>
            <ul>
                <li>Transaction ID: $transaction_id</li>
                <li>Payment Method: " . ucfirst($payment_method) . "</li>
                <li>Transaction Type: " . ucfirst($transaction_type) . "</li>
                <li>Amount: ৳" . number_format($amount) . "</li>
                <li>Status: " . ucfirst($status) . "</li>
            </ul>
            <p>Thank you for choosing PawConnect!</p>
            <p>Best regards,<br>PawConnect Team</p>
        </body>
        </html>
        ";
        
        sendEmail($email, $email_subject, $email_message);
        
        // Send notification to admin
        $admin_subject = "New Transaction - PawConnect";
        $admin_message = "
        <html>
        <head>
            <title>New Transaction</title>
        </head>
        <body>
            <h2>New Transaction Received</h2>
            <p><strong>Transaction Details:</strong></p>
            <ul>
                <li>Transaction ID: $transaction_id</li>
                <li>Payment Method: " . ucfirst($payment_method) . "</li>
                <li>Transaction Type: " . ucfirst($transaction_type) . "</li>
                <li>Amount: ৳" . number_format($amount) . "</li>
                <li>Customer Email: $email</li>
                <li>Status: " . ucfirst($status) . "</li>
            </ul>
        </body>
        </html>
        ";
        
        sendEmail($admin_email, $admin_subject, $admin_message);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'transaction_id' => $transaction_id,
            'status' => $status,
            'validation_message' => $validation_message
        ]);
        
    } catch (Exception $e) {
        // Return error response
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    // Invalid request method
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 