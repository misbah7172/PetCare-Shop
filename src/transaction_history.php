<?php
// Database configuration
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

// Start session
session_start();

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

// Get transactions from database
$transactions = [];
$pdo = connectDB();
if ($pdo) {
    try {
        $sql = "SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10";
        $stmt = $pdo->query($sql);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Handle error silently
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - PawConnect</title>
    <link rel="stylesheet" href="transaction.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="home.html"><img src="images/logo_pwc.png" alt="PawConnect Logo" class="logo"></a>
        <div class="header-content">
            <h1 class="header-title">PawConnect</h1>
        </div>
        
        <!-- Clock and Calendar Section -->
        <div class="header-clock-calendar">
            <div class="clock-display">
                <i class="fas fa-clock"></i>
                <span id="current-time">--:--:--</span>
            </div>
            <div class="calendar-display">
                <i class="fas fa-calendar"></i>
                <span id="current-date">--/--/----</span>
            </div>
        </div>
        
        <div class="navbar">
            <div class="navlist">
                <button onclick="window.location.href='pet_adoption_feed.html'">pet adoption</button>
                <button onclick="window.location.href='vet_appointment.html'">vet appointment</button>
                <button onclick="window.location.href='pet_corner.html'">pet corner</button>
                <button onclick="window.location.href='pet_community.html'">community</button>
                <button onclick="window.location.href='shop_feed.html'">shop</button>
                <button onclick="window.location.href='customer_support.html'">Customer Support</button>
                <button class="subscription-nav-btn" onclick="window.location.href='subscription_plans.html'">Subscription</button>
            </div>
        </div>
    </header>

    <main class="transaction-container">
        <div class="transaction-header">
            <h1><i class="fas fa-history"></i> Transaction History</h1>
            <p>View your recent transactions and payment history</p>
        </div>

        <div class="transaction-content">
            <!-- Transaction History -->
            <div class="transaction-history">
                <h2><i class="fas fa-history"></i> Recent Transactions</h2>
                
                <?php if (empty($transactions)): ?>
                    <div class="no-transactions">
                        <i class="fas fa-inbox"></i>
                        <h3>No Transactions Found</h3>
                        <p>You haven't made any transactions yet.</p>
                        <button onclick="window.location.href='transaction.html'" class="btn-primary">
                            <i class="fas fa-plus"></i> Make a Transaction
                        </button>
                    </div>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="history-item">
                                <div class="history-icon <?php echo $transaction['status']; ?>">
                                    <?php if ($transaction['payment_method'] === 'card'): ?>
                                        <i class="fas fa-credit-card"></i>
                                    <?php else: ?>
                                        <i class="fas fa-mobile-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="history-details">
                                    <h4><?php echo ucfirst($transaction['transaction_type']); ?></h4>
                                    <p>Order #<?php echo htmlspecialchars($transaction['transaction_id']); ?></p>
                                    <span class="amount">৳<?php echo number_format($transaction['amount']); ?></span>
                                </div>
                                <div class="history-date">
                                    <span><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></span>
                                    <span class="status <?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-secondary" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn-primary" onclick="window.location.href='transaction.html'">
                    <i class="fas fa-plus"></i> New Transaction
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>PawConnect is your one-stop platform for pet adoption, veterinary services, and pet care products.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.html">Home</a></li>
                    <li><a href="pet_adoption_feed.html">Adopt a Pet</a></li>
                    <li><a href="vet_appointment.html">Vet Appointments</a></li>
                    <li><a href="shop_feed.html">Pet Shop</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Support</h3>
                <ul>
                    <li><a href="customer_support.html">Customer Support</a></li>
                    <li><a href="#">Payment Help</a></li>
                    <li><a href="#">Refund Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 PawConnect. All rights reserved.</p>
        </div>
    </footer>

    <!-- Clock and Calendar JavaScript -->
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        function updateDate() {
            const now = new Date();
            const dateString = now.toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            });
            document.getElementById('current-date').textContent = dateString;
        }
        
        updateClock();
        updateDate();
        setInterval(updateClock, 1000);
        setInterval(updateDate, 60000);
    </script>
</body>
</html> 