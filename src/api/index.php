<?php
// API Router for PawConnect
// Central routing system for all API endpoints

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/PetController.php';
require_once 'controllers/AdoptionController.php';
require_once 'controllers/VeterinarianController.php';
require_once 'controllers/AppointmentController.php';
require_once 'controllers/ProductController.php';
require_once 'controllers/OrderController.php';
require_once 'controllers/CommunityController.php';
require_once 'controllers/SupportController.php';
require_once 'controllers/LostFoundController.php';
require_once 'controllers/SubscriptionController.php';
require_once 'controllers/ReminderController.php';
require_once 'controllers/EmergencyController.php';
require_once 'controllers/DonationController.php';
require_once 'controllers/NotificationController.php';
require_once 'middleware/AuthMiddleware.php';

class APIRouter {
    private $database;
    private $authMiddleware;

    public function __construct() {
        $this->database = new Database();
        $this->authMiddleware = new AuthMiddleware($this->database);
    }

    public function route() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/pawconnect/src/api', '', $path);
        $segments = explode('/', trim($path, '/'));

        try {
            switch ($segments[0]) {
                case 'auth':
                    $this->handleAuth($method, array_slice($segments, 1));
                    break;
                case 'pets':
                    $this->handlePets($method, array_slice($segments, 1));
                    break;
                case 'adoptions':
                    $this->handleAdoptions($method, array_slice($segments, 1));
                    break;
                case 'veterinarians':
                    $this->handleVeterinarians($method, array_slice($segments, 1));
                    break;
                case 'appointments':
                    $this->handleAppointments($method, array_slice($segments, 1));
                    break;
                case 'products':
                    $this->handleProducts($method, array_slice($segments, 1));
                    break;
                case 'orders':
                    $this->handleOrders($method, array_slice($segments, 1));
                    break;
                case 'community':
                    $this->handleCommunity($method, array_slice($segments, 1));
                    break;
                case 'support':
                    $this->handleSupport($method, array_slice($segments, 1));
                    break;
                case 'lost-found':
                    $this->handleLostFound($method, array_slice($segments, 1));
                    break;
                case 'subscriptions':
                    $this->handleSubscriptions($method, array_slice($segments, 1));
                    break;
                case 'reminders':
                    $this->handleReminders($method, array_slice($segments, 1));
                    break;
                case 'emergency':
                    $this->handleEmergency($method, array_slice($segments, 1));
                    break;
                case 'donations':
                    $this->handleDonations($method, array_slice($segments, 1));
                    break;
                case 'notifications':
                    $this->handleNotifications($method, array_slice($segments, 1));
                    break;
                default:
                    $this->sendResponse(404, ['error' => 'Endpoint not found']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => 'Internal server error', 'message' => $e->getMessage()]);
        }
    }

    private function handleAuth($method, $segments) {
        $controller = new AuthController($this->database);
        
        switch ($method) {
            case 'POST':
                switch ($segments[0] ?? '') {
                    case 'register':
                        $controller->register();
                        break;
                    case 'login':
                        $controller->login();
                        break;
                    case 'refresh':
                        $controller->refreshToken();
                        break;
                    case 'forgot-password':
                        $controller->forgotPassword();
                        break;
                    case 'reset-password':
                        $controller->resetPassword();
                        break;
                    case 'verify-email':
                        $controller->verifyEmail();
                        break;
                    default:
                        $this->sendResponse(404, ['error' => 'Auth endpoint not found']);
                }
                break;
            case 'GET':
                switch ($segments[0] ?? '') {
                    case 'me':
                        $user = $this->authMiddleware->authenticate();
                        $controller->getCurrentUser($user['id']);
                        break;
                    case 'logout':
                        $controller->logout();
                        break;
                    default:
                        $this->sendResponse(404, ['error' => 'Auth endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handlePets($method, $segments) {
        $controller = new PetController($this->database);
        
        switch ($method) {
            case 'GET':
                if (empty($segments[0])) {
                    $controller->getAllPets();
                } elseif (is_numeric($segments[0])) {
                    $controller->getPet($segments[0]);
                } elseif ($segments[0] === 'featured') {
                    $controller->getFeaturedPets();
                } elseif ($segments[0] === 'search') {
                    $controller->searchPets();
                } elseif ($segments[0] === 'favorites') {
                    $user = $this->authMiddleware->authenticate();
                    $controller->getUserFavorites($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Pet endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->createPet($user['id']);
                } elseif ($segments[0] === 'favorite' && is_numeric($segments[1])) {
                    $controller->toggleFavorite($user['id'], $segments[1]);
                } else {
                    $this->sendResponse(404, ['error' => 'Pet endpoint not found']);
                }
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    $controller->updatePet($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Pet endpoint not found']);
                }
                break;
            case 'DELETE':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    $controller->deletePet($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Pet endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleAdoptions($method, $segments) {
        $controller = new AdoptionController($this->database);
        
        switch ($method) {
            case 'GET':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->getUserApplications($user['id']);
                } elseif (is_numeric($segments[0])) {
                    $controller->getApplication($segments[0], $user['id']);
                } elseif ($segments[0] === 'admin' && $user['role'] === 'admin') {
                    $controller->getAllApplications();
                } else {
                    $this->sendResponse(404, ['error' => 'Adoption endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                $controller->submitApplication($user['id']);
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    if ($segments[1] === 'approve' && $user['role'] === 'admin') {
                        $controller->approveApplication($segments[0]);
                    } elseif ($segments[1] === 'reject' && $user['role'] === 'admin') {
                        $controller->rejectApplication($segments[0]);
                    } else {
                        $controller->updateApplication($segments[0], $user['id']);
                    }
                } else {
                    $this->sendResponse(404, ['error' => 'Adoption endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleVeterinarians($method, $segments) {
        $controller = new VeterinarianController($this->database);
        
        switch ($method) {
            case 'GET':
                if (empty($segments[0])) {
                    $controller->getAllVeterinarians();
                } elseif (is_numeric($segments[0])) {
                    $controller->getVeterinarian($segments[0]);
                } elseif ($segments[0] === 'search') {
                    $controller->searchVeterinarians();
                } elseif ($segments[0] === 'nearby') {
                    $controller->getNearbyVeterinarians();
                } else {
                    $this->sendResponse(404, ['error' => 'Veterinarian endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                $controller->registerVeterinarian($user['id']);
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    $controller->updateVeterinarian($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Veterinarian endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleAppointments($method, $segments) {
        $controller = new AppointmentController($this->database);
        
        switch ($method) {
            case 'GET':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->getUserAppointments($user['id']);
                } elseif (is_numeric($segments[0])) {
                    $controller->getAppointment($segments[0], $user['id']);
                } elseif ($segments[0] === 'vet' && is_numeric($segments[1])) {
                    $controller->getVetAppointments($segments[1], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Appointment endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                $controller->bookAppointment($user['id']);
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    if ($segments[1] === 'cancel') {
                        $controller->cancelAppointment($segments[0], $user['id']);
                    } elseif ($segments[1] === 'complete') {
                        $controller->completeAppointment($segments[0], $user['id']);
                    } else {
                        $controller->updateAppointment($segments[0], $user['id']);
                    }
                } else {
                    $this->sendResponse(404, ['error' => 'Appointment endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleProducts($method, $segments) {
        $controller = new ProductController($this->database);
        
        switch ($method) {
            case 'GET':
                if (empty($segments[0])) {
                    $controller->getAllProducts();
                } elseif (is_numeric($segments[0])) {
                    $controller->getProduct($segments[0]);
                } elseif ($segments[0] === 'categories') {
                    $controller->getCategories();
                } elseif ($segments[0] === 'featured') {
                    $controller->getFeaturedProducts();
                } elseif ($segments[0] === 'search') {
                    $controller->searchProducts();
                } else {
                    $this->sendResponse(404, ['error' => 'Product endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate(['admin']);
                $controller->createProduct($user['id']);
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate(['admin']);
                if (is_numeric($segments[0])) {
                    $controller->updateProduct($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Product endpoint not found']);
                }
                break;
            case 'DELETE':
                $user = $this->authMiddleware->authenticate(['admin']);
                if (is_numeric($segments[0])) {
                    $controller->deleteProduct($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Product endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleOrders($method, $segments) {
        $controller = new OrderController($this->database);
        
        switch ($method) {
            case 'GET':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->getUserOrders($user['id']);
                } elseif (is_numeric($segments[0])) {
                    $controller->getOrder($segments[0], $user['id']);
                } elseif ($segments[0] === 'cart') {
                    $controller->getCart($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Order endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                if ($segments[0] === 'cart') {
                    $controller->addToCart($user['id']);
                } elseif ($segments[0] === 'checkout') {
                    $controller->checkout($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Order endpoint not found']);
                }
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if ($segments[0] === 'cart' && is_numeric($segments[1])) {
                    $controller->updateCartItem($segments[1], $user['id']);
                } elseif (is_numeric($segments[0]) && $segments[1] === 'cancel') {
                    $controller->cancelOrder($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Order endpoint not found']);
                }
                break;
            case 'DELETE':
                $user = $this->authMiddleware->authenticate();
                if ($segments[0] === 'cart' && is_numeric($segments[1])) {
                    $controller->removeFromCart($segments[1], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Order endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleCommunity($method, $segments) {
        $controller = new CommunityController($this->database);
        
        switch ($method) {
            case 'GET':
                if (empty($segments[0])) {
                    $controller->getAllPosts();
                } elseif (is_numeric($segments[0])) {
                    $controller->getPost($segments[0]);
                } elseif ($segments[0] === 'user') {
                    $user = $this->authMiddleware->authenticate();
                    $controller->getUserPosts($user['id']);
                } elseif ($segments[0] === 'trending') {
                    $controller->getTrendingPosts();
                } else {
                    $this->sendResponse(404, ['error' => 'Community endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->createPost($user['id']);
                } elseif (is_numeric($segments[0]) && $segments[1] === 'like') {
                    $controller->toggleLike($segments[0], $user['id']);
                } elseif (is_numeric($segments[0]) && $segments[1] === 'comment') {
                    $controller->addComment($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Community endpoint not found']);
                }
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    $controller->updatePost($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Community endpoint not found']);
                }
                break;
            case 'DELETE':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    $controller->deletePost($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Community endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleSupport($method, $segments) {
        $controller = new SupportController($this->database);
        
        switch ($method) {
            case 'GET':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->getUserTickets($user['id']);
                } elseif (is_numeric($segments[0])) {
                    $controller->getTicket($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Support endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->createTicket($user['id']);
                } elseif (is_numeric($segments[0]) && $segments[1] === 'message') {
                    $controller->addMessage($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Support endpoint not found']);
                }
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0]) && $segments[1] === 'close') {
                    $controller->closeTicket($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Support endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleLostFound($method, $segments) {
        $controller = new LostFoundController($this->database);
        
        switch ($method) {
            case 'GET':
                if (empty($segments[0])) {
                    $controller->getAllListings();
                } elseif (is_numeric($segments[0])) {
                    $controller->getListing($segments[0]);
                } elseif ($segments[0] === 'nearby') {
                    $controller->getNearbyListings();
                } elseif ($segments[0] === 'user') {
                    $user = $this->authMiddleware->authenticate();
                    $controller->getUserListings($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Lost/Found endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                $controller->createListing($user['id']);
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    if ($segments[1] === 'reunited') {
                        $controller->markReunited($segments[0], $user['id']);
                    } else {
                        $controller->updateListing($segments[0], $user['id']);
                    }
                } else {
                    $this->sendResponse(404, ['error' => 'Lost/Found endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleSubscriptions($method, $segments) {
        $controller = new SubscriptionController($this->database);
        
        switch ($method) {
            case 'GET':
                if ($segments[0] === 'plans') {
                    $controller->getPlans();
                } elseif ($segments[0] === 'boxes') {
                    $controller->getSubscriptionBoxes();
                } elseif ($segments[0] === 'user') {
                    $user = $this->authMiddleware->authenticate();
                    $controller->getUserSubscriptions($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Subscription endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                if ($segments[0] === 'subscribe') {
                    $controller->subscribe($user['id']);
                } elseif ($segments[0] === 'box-order') {
                    $controller->orderSubscriptionBox($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Subscription endpoint not found']);
                }
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0]) && $segments[1] === 'cancel') {
                    $controller->cancelSubscription($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Subscription endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleReminders($method, $segments) {
        $controller = new ReminderController($this->database);
        
        switch ($method) {
            case 'GET':
                $user = $this->authMiddleware->authenticate();
                if (empty($segments[0])) {
                    $controller->getUserReminders($user['id']);
                } elseif ($segments[0] === 'upcoming') {
                    $controller->getUpcomingReminders($user['id']);
                } elseif (is_numeric($segments[0])) {
                    $controller->getReminder($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Reminder endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                $controller->createReminder($user['id']);
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    if ($segments[1] === 'complete') {
                        $controller->markComplete($segments[0], $user['id']);
                    } elseif ($segments[1] === 'snooze') {
                        $controller->snoozeReminder($segments[0], $user['id']);
                    } else {
                        $controller->updateReminder($segments[0], $user['id']);
                    }
                } else {
                    $this->sendResponse(404, ['error' => 'Reminder endpoint not found']);
                }
                break;
            case 'DELETE':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0])) {
                    $controller->deleteReminder($segments[0], $user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Reminder endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleEmergency($method, $segments) {
        $controller = new EmergencyController($this->database);
        
        switch ($method) {
            case 'GET':
                if ($segments[0] === 'services') {
                    $controller->getEmergencyServices();
                } elseif ($segments[0] === 'requests') {
                    $user = $this->authMiddleware->authenticate();
                    $controller->getUserRequests($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Emergency endpoint not found']);
                }
                break;
            case 'POST':
                $user = $this->authMiddleware->authenticate();
                if ($segments[0] === 'request') {
                    $controller->createEmergencyRequest($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Emergency endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleDonations($method, $segments) {
        $controller = new DonationController($this->database);
        
        switch ($method) {
            case 'GET':
                if ($segments[0] === 'campaigns') {
                    $controller->getDonationCampaigns();
                } elseif ($segments[0] === 'user') {
                    $user = $this->authMiddleware->authenticate();
                    $controller->getUserDonations($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Donation endpoint not found']);
                }
                break;
            case 'POST':
                if ($segments[0] === 'donate') {
                    $controller->processDonation();
                } else {
                    $this->sendResponse(404, ['error' => 'Donation endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function handleNotifications($method, $segments) {
        $controller = new NotificationController($this->database);
        
        switch ($method) {
            case 'GET':
                $user = $this->authMiddleware->authenticate();
                $controller->getUserNotifications($user['id']);
                break;
            case 'PUT':
                $user = $this->authMiddleware->authenticate();
                if (is_numeric($segments[0]) && $segments[1] === 'read') {
                    $controller->markAsRead($segments[0], $user['id']);
                } elseif ($segments[0] === 'read-all') {
                    $controller->markAllAsRead($user['id']);
                } else {
                    $this->sendResponse(404, ['error' => 'Notification endpoint not found']);
                }
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
        }
    }

    private function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Initialize and route the request
$router = new APIRouter();
$router->route();
?>
