<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Populate Database - PawConnect</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🎭 Populate Database with Sample Data</h1>
        
        <?php
        try {
            require_once '../config/database.php';
            $database = new Database();
            $pdo = $database->getConnection();
            
            echo "<div class='success'>✓ Connected to database successfully</div>";
            
            // Create sample users first
            $users = [
                ['john_doe', 'john@example.com', 'John', 'Doe', '1234567890', 'user'],
                ['jane_smith', 'jane@example.com', 'Jane', 'Smith', '0987654321', 'user'],
                ['vet_sarah', 'vet@example.com', 'Dr. Sarah', 'Wilson', '5555551234', 'vet'],
                ['admin_user', 'admin@pawconnect.com', 'Admin', 'User', '5555555555', 'admin']
            ];
            
            $userIds = [];
            echo "<h3>Creating Sample Users:</h3>";
            
            foreach ($users as $user) {
                try {
                    $password = password_hash('password123', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, first_name, last_name, phone, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $stmt->execute([$user[0], $user[1], $password, $user[2], $user[3], $user[4], $user[5]]);
                    $userIds[$user[0]] = $pdo->lastInsertId();
                    echo "<div class='success'>✓ Created user: {$user[0]} (ID: {$userIds[$user[0]]})</div>";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        // User already exists, get the ID
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$user[0]]);
                        $userIds[$user[0]] = $stmt->fetchColumn();
                        echo "<div class='success'>✓ User already exists: {$user[0]} (ID: {$userIds[$user[0]]})</div>";
                    } else {
                        echo "<div class='error'>❌ Failed to create user {$user[0]}: " . $e->getMessage() . "</div>";
                    }
                }
            }
            
            // Create sample pets
            $pets = [
                [$userIds['john_doe'], 'Buddy', 'dog', 'Golden Retriever', 3, 'years', 'male', 'Friendly and energetic dog, loves playing fetch!', 'assets/doggo.png'],
                [$userIds['jane_smith'], 'Whiskers', 'cat', 'Persian', 2, 'years', 'female', 'Calm and affectionate cat, perfect for apartments.', 'assets/cool_cat.png'],
                [$userIds['john_doe'], 'Charlie', 'dog', 'Labrador', 1, 'years', 'male', 'Young and playful puppy, great with kids.', 'assets/doge_one.png'],
                [$userIds['jane_smith'], 'Luna', 'cat', 'Siamese', 4, 'years', 'female', 'Independent but loving, enjoys sunny spots.', 'assets/kitten_one.png'],
                [$userIds['john_doe'], 'Max', 'dog', 'German Shepherd', 5, 'years', 'male', 'Well-trained guard dog, loyal companion.', 'assets/review_dogg.png']
            ];
            
            $petIds = [];
            echo "<h3>Creating Sample Pets:</h3>";
            
            foreach ($pets as $pet) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO pets (user_id, name, type, breed, age, age_unit, gender, description, photo_url, is_public) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute($pet);
                    $petId = $pdo->lastInsertId();
                    $petIds[] = $petId;
                    echo "<div class='success'>✓ Created pet: {$pet[1]} (ID: $petId)</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to create pet {$pet[1]}: " . $e->getMessage() . "</div>";
                }
            }
            
            // Create adoptions for some pets
            echo "<h3>Creating Adoption Listings:</h3>";
            foreach (array_slice($petIds, 0, 3) as $petId) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO adoptions (pet_id, listed_by, status, description) 
                        VALUES (?, ?, 'available', 'Looking for a loving home for this wonderful pet!')
                    ");
                    $stmt->execute([$petId, $userIds['john_doe']]);
                    echo "<div class='success'>✓ Created adoption listing for pet ID: $petId</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to create adoption for pet $petId: " . $e->getMessage() . "</div>";
                }
            }
            
            // Create vet profile
            echo "<h3>Creating Vet Profile:</h3>";
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO vets (user_id, name, specialization, contact_info, is_active) 
                    VALUES (?, 'Dr. Sarah Wilson', 'General Veterinary Medicine', 'Phone: (555) 123-4567, Available Mon-Fri 9AM-6PM', 1)
                ");
                $stmt->execute([$userIds['vet_sarah']]);
                echo "<div class='success'>✓ Created vet profile for Dr. Sarah Wilson</div>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "<div class='error'>❌ Failed to create vet profile: " . $e->getMessage() . "</div>";
                } else {
                    echo "<div class='success'>✓ Vet profile already exists</div>";
                }
            }
            
            // Create shop products
            $products = [
                ['Premium Dog Food', 'food', 'High-quality nutrition for adult dogs', 29.99, 50, 'assets/cat_food.jpg'],
                ['Cat Scratching Post', 'accessory', 'Durable scratching post for cats', 45.99, 25, 'assets/cat_food.jpg'],
                ['Pet Vitamins', 'medicine', 'Daily vitamins for pet health', 19.99, 100, 'assets/cat_food.jpg'],
                ['Dog Collar', 'accessory', 'Adjustable collar with name tag', 15.99, 75, 'assets/cat_food.jpg'],
                ['Pet Shampoo', 'other', 'Gentle shampoo for all pets', 12.99, 60, 'assets/cat_food.jpg']
            ];
            
            echo "<h3>Creating Shop Products:</h3>";
            foreach ($products as $product) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO shop_products (name, category, description, price, stock, image_url, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute($product);
                    echo "<div class='success'>✓ Created product: {$product[0]}</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to create product {$product[0]}: " . $e->getMessage() . "</div>";
                }
            }
            
            // Create community posts
            $posts = [
                [$userIds['john_doe'], null, 'Just adopted a new puppy! Any tips for first-time dog owners?', null],
                [$userIds['jane_smith'], null, 'My cat has been acting strange lately. Should I be worried?', null],
                [$userIds['john_doe'], null, 'Looking for a good vet in the downtown area. Any recommendations?', null],
                [$userIds['jane_smith'], null, 'Pet photography session was amazing! Highly recommend PetSnaps Studio.', 'assets/pet_photos.png']
            ];
            
            echo "<h3>Creating Community Posts:</h3>";
            foreach ($posts as $post) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO community_posts (user_id, group_id, content, media_url) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute($post);
                    echo "<div class='success'>✓ Created community post</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to create community post: " . $e->getMessage() . "</div>";
                }
            }
            
            echo "<div class='success'><strong>🎉 Database populated with sample data successfully!</strong></div>";
            echo "<p><strong>Test Login Credentials:</strong></p>";
            echo "<ul>";
            echo "<li>Username: <code>john_doe</code> | Password: <code>password123</code></li>";
            echo "<li>Username: <code>jane_smith</code> | Password: <code>password123</code></li>";
            echo "<li>Username: <code>vet_sarah</code> | Password: <code>password123</code> (Vet)</li>";
            echo "<li>Username: <code>admin_user</code> | Password: <code>password123</code> (Admin)</li>";
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <hr>
        <h3>Next Steps:</h3>
        <a href="../api/data.php?endpoint=pets" class="btn">View Pets API</a>
        <a href="../api/data.php?endpoint=adoptions" class="btn">View Adoptions API</a>
        <a href="../api/data.php?endpoint=products" class="btn">View Products API</a>
        <a href="../../public/index.html" class="btn">Go to Website</a>
    </div>
</body>
</html>
