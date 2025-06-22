// Global Session Manager and UI Controller
class SessionManager {
    const        const registerButtons = document.querySelectorAll('a[href="login.html"], button[onclick*="register"], .register-btn:not(.header-nav .register-btn)');uctor() {
        this.user = null;
        this.isLoggedIn = false;
        this.init();
    }
    
    async init() {
        await this.checkSession();
        this.updateUI();
        this.initializeGlobalStyles();
    }
    
    async checkSession() {
        try {
            const response = await fetch('../src/session_manager.php?action=status');
            const data = await response.json();
            this.isLoggedIn = data.logged_in;
            this.user = data.user;
            console.log('Session check:', { logged_in: this.isLoggedIn, user: this.user });
        } catch (error) {
            console.error('Session check failed:', error);
            this.isLoggedIn = false;
            this.user = null;
        }
    }
    
    initializeGlobalStyles() {
        // Apply consistent styling to all pages
        if (!document.getElementById('global-styles')) {
            const link = document.createElement('link');
            link.id = 'global-styles';
            link.rel = 'stylesheet';
            link.href = 'css/global.css';
            document.head.appendChild(link);
        }
    }      updateUI() {
        this.updateNavigation();
        this.updateUserDisplay();
        this.updateAuthButtons();
        this.ensureModernHeader();
        this.loadNotificationManager();
    }
    
    ensureModernHeader() {
        // Ensure header has the correct structure and classes
        const header = document.querySelector('header');
        if (header && !header.classList.contains('site-header')) {
            header.className = 'site-header';
        }
        
        // Ensure navigation exists
        let nav = document.querySelector('.header-nav');
        if (!nav) {
            nav = document.createElement('nav');
            nav.className = 'header-nav';
            header.appendChild(nav);
        }
    }
    
    updateNavigation() {
        const navElements = document.querySelectorAll('.header-nav, .navbar');
        navElements.forEach(nav => {
            if (this.isLoggedIn) {
                this.showLoggedInNav(nav);
            } else {
                this.showLoggedOutNav(nav);
            }
        });
        
        // Also update any standalone login/register buttons
        this.updateStandaloneAuthButtons();
    }
    
    updateStandaloneAuthButtons() {
        // Hide login/register buttons that are not in navigation
        const loginButtons = document.querySelectorAll('a[href="login.html"], button[onclick*="login"], .login-btn:not(.header-nav .login-btn)');
        const registerButtons = document.querySelectorAll('a[href="login.html"], button[onclick*="register"], .register-btn:not(.header-nav .register-btn)');
        
        loginButtons.forEach(btn => {
            if (this.isLoggedIn) {
                btn.style.display = 'none';
            } else {
                btn.style.display = '';
            }
        });
        
        registerButtons.forEach(btn => {
            if (this.isLoggedIn) {
                btn.style.display = 'none';
            } else {
                btn.style.display = '';
            }
        });
    }      showLoggedInNav(nav) {
        nav.className = 'header-nav';
        nav.innerHTML = `
            <a href="index.html" class="nav-link">Home</a>
            <a href="pet_adoption_feed.html" class="nav-link">Adopt</a>
            <a href="shop_feed.html" class="nav-link">Shop</a>
            <a href="vet_appointment.html" class="nav-link">Vets</a>
            <a href="pet_community.html" class="nav-link">Community</a>
            <a href="pet_corner.html" class="nav-link">Pet Corner</a>
            <div class="user-menu">
                <div class="notification-container">
                    <div class="notification-bell" onclick="notificationManager.toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                    </div>
                    <div class="notification-dropdown" id="notification-dropdown" style="display: none;">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <div class="notification-actions">
                                <button onclick="notificationManager.markAllAsRead()" class="mark-all-read-btn">
                                    <i class="fas fa-check-double"></i> Mark all read
                                </button>
                            </div>
                        </div>
                        <div class="notification-list" id="notification-list">
                            <div class="loading">Loading notifications...</div>
                        </div>
                    </div>
                </div>
                <span class="user-greeting">Hi, ${this.user?.name || this.user?.username || 'User'}!</span>
                <a href="dashboard.html" class="nav-link">Dashboard</a>
                <a href="#" onclick="sessionManager.logout()" class="nav-link logout-btn">Logout</a>
            </div>
        `;
    }
    
    showLoggedOutNav(nav) {
        nav.className = 'header-nav';
        nav.innerHTML = `
            <a href="index.html" class="nav-link">Home</a>
            <a href="pet_adoption_feed.html" class="nav-link">Adopt</a>
            <a href="shop_feed.html" class="nav-link">Shop</a>
            <a href="vet_appointment.html" class="nav-link">Vets</a>
            <a href="pet_community.html" class="nav-link">Community</a>
            <a href="login.html" class="nav-link login-btn">Login</a>
            <a href="login.html" class="nav-link register-btn">Register</a>
        `;
    }
    
    updateUserDisplay() {
        const userDisplays = document.querySelectorAll('.user-display, .user-info');
        userDisplays.forEach(display => {
            if (this.isLoggedIn && this.user) {
                display.innerHTML = `
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name">${this.user.first_name} ${this.user.last_name}</span>
                        <span class="user-email">${this.user.email}</span>
                    </div>
                `;
                display.style.display = 'flex';
            } else {
                display.style.display = 'none';
            }
        });
    }
    
    updateAuthButtons() {
        // Hide login/register buttons when logged in
        const authButtons = document.querySelectorAll('.auth-required');
        authButtons.forEach(btn => {
            btn.style.display = this.isLoggedIn ? 'none' : 'block';
        });
        
        // Show user-only content when logged in
        const userOnlyContent = document.querySelectorAll('.user-only');
        userOnlyContent.forEach(content => {
            content.style.display = this.isLoggedIn ? 'block' : 'none';
        });
    }
    
    async logout() {
        try {
            await fetch('../src/session_manager.php?action=logout');
            this.isLoggedIn = false;
            this.user = null;
            this.updateUI();
            window.location.href = 'index.html';
        } catch (error) {
            console.error('Logout failed:', error);
        }
    }
    
    requireLogin() {
        if (!this.isLoggedIn) {
            window.location.href = 'login.html';
            return false;
        }
        return true;
    }
    
    loadNotificationManager() {
        // Only load notification manager for logged-in users
        if (this.isLoggedIn && !window.notificationManager) {
            if (!document.getElementById('notification-manager-script')) {
                const script = document.createElement('script');
                script.id = 'notification-manager-script';
                script.src = 'js/notification-manager.js';
                script.onload = () => {
                    console.log('Notification manager loaded');
                };
                document.head.appendChild(script);
            }
        }
    }
}

// Global CSS for consistent styling
const globalStyles = `
    /* Global Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: #333;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    /* Header Styles */
    .site-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        padding: 1rem 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .logo-section {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .logo {
        height: 50px;
        width: auto;
    }
    
    .header-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #4a5568;
    }
    
    /* Navigation Styles */
    .header-nav {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .nav-link {
        text-decoration: none;
        color: #4a5568;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }
    
    .user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-left: 1rem;
        border-left: 1px solid #e2e8f0;
    }
    
    .user-greeting {
        color: #667eea;
        font-weight: 600;
    }
    
    .logout-btn {
        background: #e53e3e;
        color: white !important;
    }
    
    .logout-btn:hover {
        background: #c53030;
    }
    
    .login-btn, .register-btn {
        background: #667eea;
        color: white !important;
    }
    
    .login-btn:hover, .register-btn:hover {
        background: #5a67d8;
    }
    
    /* Container Styles */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    /* Card Styles */
    .pet-card, .product-card, .vet-card, .adoption-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .pet-card:hover, .product-card:hover, .vet-card:hover, .adoption-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .pet-image, .product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    
    .pet-info, .product-info, .vet-info, .adoption-info {
        padding: 1.5rem;
    }
    
    .pet-info h3, .product-info h3, .vet-info h3, .adoption-info h3 {
        color: #2d3748;
        margin-bottom: 0.5rem;
        font-size: 1.25rem;
    }
    
    /* Grid Layouts */
    .product-grid, .vet-profiles {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }
    
    /* Button Styles */
    .btn, .details-btn, .add-to-cart-btn, .btn-appointment, .btn-adopt {
        background: #667eea;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-weight: 500;
        transition: all 0.3s ease;
        margin: 0.25rem;
    }
    
    .btn:hover, .details-btn:hover, .add-to-cart-btn:hover, .btn-appointment:hover, .btn-adopt:hover {
        background: #5a67d8;
        transform: translateY(-2px);
    }
    
    /* Search and Filter Styles */
    .search-filter {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }
    
    .search-bar {
        display: flex;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .search-bar input {
        flex: 1;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
    }
    
    .search-bar input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .filters {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .filter-select {
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        background: white;
        font-size: 1rem;
    }
    
    /* Loading Indicator */
    .loading, #loading-indicator {
        text-align: center;
        padding: 2rem;
        color: #667eea;
        font-size: 1.1rem;
    }
    
    .loading i, #loading-indicator i {
        font-size: 2rem;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Error Styles */
    .error {
        background: #fed7d7;
        color: #c53030;
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }
    
    /* Success Styles */
    .success {
        background: #c6f6d5;
        color: #25543e;
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .site-header {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }
        
        .header-nav {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .container {
            padding: 1rem;
        }
        
        .product-grid, .vet-profiles {
            grid-template-columns: 1fr;
        }
        
        .search-bar {
            flex-direction: column;
        }
        
        .filters {
            justify-content: center;
        }
    }
`;

// Inject global styles
function injectGlobalStyles() {
    const styleSheet = document.createElement('style');
    styleSheet.textContent = globalStyles;
    document.head.appendChild(styleSheet);
}

// Initialize session manager when DOM is loaded
let sessionManager;
document.addEventListener('DOMContentLoaded', () => {
    injectGlobalStyles();
    sessionManager = new SessionManager();
});

// Export for use in other scripts
window.sessionManager = sessionManager;
