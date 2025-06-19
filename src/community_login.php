<?php
// Pet Corner & Pet Community Login Handler
// PawConnect Special Features Login System

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    $dbError = "Database connection failed. Please try again later.";
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username/Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors and database is connected, attempt login
    if (empty($errors) && isset($pdo)) {
        try {
            // Check if user exists and password is correct
            $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.password, cu.display_name, cu.bio, cu.experience_level 
                                  FROM users u 
                                  LEFT JOIN community_users cu ON u.id = cu.user_id 
                                  WHERE u.username = ? OR u.email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if user has community profile
                if (!$user['display_name']) {
                    $errors[] = "This account doesn't have a community profile. Please register for Pet Corner & Pet Community.";
                } else {
                    // Generate new auth token
                    $authToken = bin2hex(random_bytes(32));
                    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Update auth token
                    $stmt = $pdo->prepare("UPDATE users SET auth_token = ?, token_expiry = ? WHERE id = ?");
                    $stmt->execute([$authToken, $tokenExpiry, $user['id']]);
                    
                    // Set session for community access
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['auth_token'] = $authToken;
                    $_SESSION['role'] = 'user';
                    $_SESSION['access_level'] = 'community'; // Community access
                    $_SESSION['display_name'] = $user['display_name'];
                    
                    // Redirect to community page
                    header("Location: pet_community.html?login=success");
                    exit();
                }
            } else {
                $errors[] = "Invalid username/email or password";
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "Login failed. Please try again.";
        }
    } elseif (isset($dbError)) {
        $errors[] = $dbError;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Login - PawConnect</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="loginstyles.css">
    <style>
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #ffcdd2;
        }
        .success-message {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #c8e6c9;
        }
        .info-box {
            background: #fff3e0;
            color: #e65100;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #ffcc02;
        }
        .premium-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <header>
        <a href="index.html"><img src="images/logo_pwc.png" alt="PawConnect Logo" class="logo"></a>
    </header>

    <div class="container">
        <!-- Login Form -->
        <div class="form-box login">
            <form action="community_login.php" method="POST">
                <h1>Community Login</h1>
                
                <div class="premium-box">
                    <h3>🌟 Pet Corner & Pet Community</h3>
                    <p>Access to exclusive pet community features</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="input-box">
                    <input type="text" name="username" placeholder="Username or Email" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class='bx bxs-lock-alt'></i>
                </div>
                <div class="forgot-link">
                    <a href="#">Forgot Password?</a>
                </div>
                <button type="submit" class="btn">Login to Community</button>
                <p>or login with social platforms</p>
                <div class="social-icon">
                    <a href="#"><i class='bx bxl-facebook'></i></a>
                    <a href="#"><i class='bx bxl-twitter'></i></a>
                    <a href="#"><i class='bx bxl-google'></i></a>
                </div>
            </form>
        </div>
 
        <!-- Register Form -->
        <div class="form-box register">
            <form action="community_register.php" method="POST">
                <h1>Community Registration</h1>
                
                <div class="premium-box">
                    <h3>🌟 Join Pet Corner & Pet Community</h3>
                    <p>Create your pet community profile and start sharing!</p>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="display_name">Display Name *</label>
                    <input type="text" id="display_name" name="display_name" required
                           placeholder="How you'll appear in the community">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" placeholder="Tell us about yourself and your pets..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="experience_level">Pet Experience Level</label>
                    <select id="experience_level" name="experience_level">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Create Community Profile</button>
                <p>or sign in with social platforms</p>
                <div class="social-icon">
                    <a href="#"><i class='bx bxl-facebook'></i></a>
                    <a href="#"><i class='bx bxl-twitter'></i></a>
                    <a href="#"><i class='bx bxl-google'></i></a>
                </div>
            </form>
        </div>

        <!-- Toggle Panel -->
        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Pet Community!</h1>
                <p>Join our exclusive pet community?</p>
                <button class="btn register-btn">Register</button>
            </div>
            <div class="toggle-panel toggle-right">
                <h1>Pet Community!</h1>
                <p>Already a community member?</p>
                <button class="btn login-btn">Login</button>
            </div>
        </div>
    </div>

    <script src="javascripts/script.js"></script>
</body>
</html> 