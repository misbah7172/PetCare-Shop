<?php
// Database Setup Script for PawConnect
// This script sets up the complete database with all tables and sample data

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

class DatabaseSetup {
    private $pdo;
    
    public function __construct() {
        try {
            // Connect to MySQL server (not specific database)
            $this->pdo = new PDO(
                "mysql:host=localhost;charset=utf8mb4",
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function setup() {
        try {
            echo "<h2>Setting up PawConnect Database...</h2>";
            
            // Create database
            $this->createDatabase();
            
            // Use the database
            $this->pdo->exec("USE pawconnect_db");
            
            // Create tables
            $this->createTables();
            
            // Insert sample data
            $this->insertSampleData();
            
            echo "<div style='color: green; font-weight: bold;'>✅ Database setup completed successfully!</div>";
            echo "<p><a href='../public/index.html'>Go to PawConnect Homepage</a></p>";
            echo "<p><a href='../public/admin_comprehensive.html'>Go to Admin Panel</a></p>";
            
        } catch (Exception $e) {
            echo "<div style='color: red; font-weight: bold;'>❌ Setup failed: " . $e->getMessage() . "</div>";
        }
    }
    
    private function createDatabase() {
        echo "Creating database...<br>";
        $this->pdo->exec("CREATE DATABASE IF NOT EXISTS pawconnect_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database created<br>";
    }
      private function createTables() {
        echo "Creating tables...<br>";
        
        // Read and execute the schema file
        $schema = file_get_contents(__DIR__ . '/../database/comprehensive_schema.sql');
        
        // Remove the CREATE DATABASE and USE statements since we already did that
        $schema = preg_replace('/CREATE DATABASE.*?;/', '', $schema);
        $schema = preg_replace('/USE.*?;/', '', $schema);
        
        // Remove INSERT statements from schema - we'll handle data separately
        $schema = preg_replace('/INSERT INTO.*?;/s', '', $schema);
          // Split into individual statements and filter out problematic ones
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                // Skip problematic CREATE INDEX statements for now
                if (strpos($statement, 'CREATE INDEX') === 0) {
                    continue;
                }
                try {
                    $this->pdo->exec($statement);
                } catch (PDOException $e) {
                    // Continue on non-critical errors (like table already exists)
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        echo "✅ Tables created<br>";
    }
      private function insertSampleData() {
        echo "Inserting sample data...<br>";
        
        // Sample product categories first
        $this->insertSampleProductCategories();
        
        // Sample users
        $this->insertSampleUsers();
        
        // Sample pets
        $this->insertSamplePets();
        
        // Sample veterinarians
        $this->insertSampleVeterinarians();
        
        // Sample products
        $this->insertSampleProducts();
        
        // Sample subscription plans
        $this->insertSampleSubscriptionPlans();
        
        // Sample emergency services
        
        echo "✅ Sample data inserted<br>";
    }
    
    private function insertSampleUsers() {
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@pawconnect.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'role' => 'admin',
                'status' => 'active'
            ],
            [
                'username' => 'john_doe',
                'email' => 'john@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '+1234567890',
                'city' => 'New York',
                'state' => 'NY',
                'role' => 'user',
                'status' => 'active'
            ],
            [
                'username' => 'jane_smith',
                'email' => 'jane@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone' => '+1234567891',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'role' => 'user',
                'status' => 'active'
            ],
            [
                'username' => 'dr_sarah',
                'email' => 'dr.sarah@vetclinic.com',
                'password_hash' => password_hash('vet123', PASSWORD_DEFAULT),
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'phone' => '+1234567892',
                'city' => 'Chicago',
                'state' => 'IL',
                'role' => 'veterinarian',
                'status' => 'active'
            ]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, phone, city, state, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($users as $user) {
            $stmt->execute([
                $user['username'], $user['email'], $user['password_hash'],
                $user['first_name'], $user['last_name'], $user['phone'] ?? null,
                $user['city'] ?? null, $user['state'] ?? null,
                $user['role'], $user['status']
            ]);
        }
    }
    
    private function insertSamplePets() {
        $pets = [
            [
                'owner_id' => 2, // john_doe
                'name' => 'Buddy',
                'species' => 'dog',
                'breed' => 'Golden Retriever',
                'age_years' => 3,
                'gender' => 'male',
                'size' => 'large',
                'description' => 'Friendly and energetic dog, great with kids and other pets.',
                'adoption_status' => 'available',
                'adoption_fee' => 250.00,
                'location' => 'New York, NY',
                'images' => '["https://example.com/buddy1.jpg", "https://example.com/buddy2.jpg"]'
            ],
            [
                'owner_id' => 3, // jane_smith
                'name' => 'Whiskers',
                'species' => 'cat',
                'breed' => 'Persian',
                'age_years' => 2,
                'gender' => 'female',
                'size' => 'medium',
                'description' => 'Calm and affectionate cat, loves to cuddle.',
                'adoption_status' => 'available',
                'adoption_fee' => 150.00,
                'location' => 'Los Angeles, CA',
                'images' => '["https://example.com/whiskers1.jpg"]'
            ],
            [
                'owner_id' => 2,
                'name' => 'Charlie',
                'species' => 'dog',
                'breed' => 'Labrador Mix',
                'age_years' => 1,
                'gender' => 'male',
                'size' => 'medium',
                'description' => 'Young and playful puppy, needs training but very smart.',
                'adoption_status' => 'available',
                'adoption_fee' => 200.00,
                'location' => 'New York, NY',
                'is_featured' => true,
                'images' => '["https://example.com/charlie1.jpg"]'
            ]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO pets (owner_id, name, species, breed, age_years, gender, size, description, adoption_status, adoption_fee, location, is_featured, images)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($pets as $pet) {
            $stmt->execute([
                $pet['owner_id'], $pet['name'], $pet['species'], $pet['breed'],
                $pet['age_years'], $pet['gender'], $pet['size'], $pet['description'],
                $pet['adoption_status'], $pet['adoption_fee'], $pet['location'],
                $pet['is_featured'] ?? false, $pet['images']
            ]);
        }
    }
    
    private function insertSampleVeterinarians() {
        $vets = [
            [
                'user_id' => 4, // dr_sarah
                'license_number' => 'VET123456',
                'clinic_name' => 'Downtown Animal Hospital',
                'clinic_address' => '123 Main St, Chicago, IL 60601',
                'clinic_phone' => '+1234567892',
                'clinic_email' => 'info@downtownvet.com',
                'specialties' => '["General Practice", "Surgery", "Emergency Care"]',
                'years_experience' => 8,
                'consultation_fee' => 75.00,
                'emergency_available' => true,
                'telemedicine' => true,
                'profile_verified' => true,
                'rating' => 4.8
            ]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO veterinarians (user_id, license_number, clinic_name, clinic_address, clinic_phone, clinic_email, specialties, years_experience, consultation_fee, emergency_available, telemedicine, profile_verified, rating)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($vets as $vet) {
            $stmt->execute([
                $vet['user_id'], $vet['license_number'], $vet['clinic_name'],
                $vet['clinic_address'], $vet['clinic_phone'], $vet['clinic_email'],
                $vet['specialties'], $vet['years_experience'], $vet['consultation_fee'],
                $vet['emergency_available'], $vet['telemedicine'],
                $vet['profile_verified'], $vet['rating']
            ]);
        }
    }
    
    private function insertSampleProducts() {
        $products = [
            [
                'category_id' => 1, // Pet Food
                'name' => 'Premium Dog Food - Adult',
                'slug' => 'premium-dog-food-adult',
                'description' => 'High-quality nutrition for adult dogs with real chicken as the first ingredient.',
                'sku' => 'PDF001',
                'price' => 29.99,
                'stock_quantity' => 100,
                'brand' => 'PawNutrition',
                'pet_species' => '["dog"]',
                'images' => '["https://example.com/dogfood1.jpg"]'
            ],
            [
                'category_id' => 2, // Toys
                'name' => 'Interactive Cat Toy',
                'slug' => 'interactive-cat-toy',
                'description' => 'Electronic toy that moves randomly to keep cats engaged.',
                'sku' => 'ICT001',
                'price' => 15.99,
                'stock_quantity' => 50,
                'brand' => 'PlayTime',
                'pet_species' => '["cat"]',
                'images' => '["https://example.com/cattoy1.jpg"]'
            ],
            [
                'category_id' => 5, // Accessories
                'name' => 'Adjustable Dog Collar',
                'slug' => 'adjustable-dog-collar',
                'description' => 'Durable nylon collar with quick-release buckle.',
                'sku' => 'ADC001',
                'price' => 12.99,
                'stock_quantity' => 75,
                'brand' => 'PetGear',
                'pet_species' => '["dog"]',
                'images' => '["https://example.com/collar1.jpg"]'
            ]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO products (category_id, name, slug, description, sku, price, stock_quantity, brand, pet_species, images, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        foreach ($products as $product) {
            $stmt->execute([
                $product['category_id'], $product['name'], $product['slug'],
                $product['description'], $product['sku'], $product['price'],
                $product['stock_quantity'], $product['brand'],
                $product['pet_species'], $product['images']
            ]);
        }
    }
    
    private function insertSampleProductCategories() {
        $categories = [
            ['name' => 'Pet Food', 'slug' => 'pet-food', 'description' => 'Nutritious food for all types of pets'],
            ['name' => 'Toys & Entertainment', 'slug' => 'toys-entertainment', 'description' => 'Fun toys and entertainment items for pets'],
            ['name' => 'Health & Medicine', 'slug' => 'health-medicine', 'description' => 'Medical supplies and health products'],
            ['name' => 'Grooming & Care', 'slug' => 'grooming-care', 'description' => 'Grooming tools and care products'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Collars, leashes, and other accessories'],
            ['name' => 'Bedding & Furniture', 'slug' => 'bedding-furniture', 'description' => 'Comfortable bedding and furniture for pets']
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO product_categories (name, slug, description)
            VALUES (?, ?, ?)
        ");
        
        foreach ($categories as $category) {
            $stmt->execute([$category['name'], $category['slug'], $category['description']]);
        }
    }
    
    private function insertSampleSubscriptionPlans() {
        $plans = [
            [
                'name' => 'Basic',
                'description' => 'Essential features for pet owners',
                'price' => 9.99,
                'billing_cycle' => 'monthly',
                'features' => '["Basic pet profiles", "Community access", "Basic reminders"]'
            ],
            [
                'name' => 'Premium',
                'description' => 'Enhanced features with priority support',
                'price' => 19.99,
                'billing_cycle' => 'monthly',
                'features' => '["Unlimited pet profiles", "Priority support", "Advanced reminders", "Telemedicine consultations", "Premium community features"]'
            ],
            [
                'name' => 'Family',
                'description' => 'Perfect for families with multiple pets',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'features' => '["Unlimited pets", "Family sharing", "All premium features", "Emergency support", "Veterinary discounts"]'
            ]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO subscription_plans (name, description, price, billing_cycle, features)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($plans as $plan) {
            $stmt->execute([
                $plan['name'], $plan['description'], $plan['price'],
                $plan['billing_cycle'], $plan['features']
            ]);
        }
    }
}

// Run the setup
$setup = new DatabaseSetup();
$setup->setup();
?>

<!DOCTYPE html>
<html>
<head>
    <title>PawConnect Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🐾 PawConnect Database Setup</h1>
    <p>This script sets up the complete database with all tables and sample data for the PawConnect platform.</p>
</body>
</html>
