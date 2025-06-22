// Get DOM elements
const container = document.querySelector('.container');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const toast = document.getElementById('toast');

// Toggle between login and register forms
if (registerBtn) {
    registerBtn.addEventListener('click', () => {
        container.classList.add('active');
    });
}

if (loginBtn) {
    loginBtn.addEventListener('click', () => {
        container.classList.remove('active');
    });
}

// Show toast notification
function showToast(message, type = 'info', duration = 3000) {
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = 'toast show ' + type;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

// Handle form submissions
async function handleLogin(event) {
    event.preventDefault();
    const button = document.getElementById('loginButton');
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!username || !password) {
        showToast('Please fill in all fields', 'error');
        return;
    }
    
    try {
        button.classList.add('loading');
        button.disabled = true;
        
        // Create form data for backend
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        // Submit to backend with cache busting
        const timestamp = new Date().getTime();
        const response = await fetch(`../src/login.php?t=${timestamp}`, {
            method: 'POST',
            body: formData,
            cache: 'no-cache',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        
        if (response.redirected) {
            // Login successful, redirect
            showToast('Login successful!', 'success');
            setTimeout(() => {
                window.location.href = response.url;
            }, 1000);
        } else {
            const text = await response.text();
            if (text.includes('error=1')) {
                showToast('Invalid username or password', 'error');
            } else {
                showToast('Login failed. Please try again.', 'error');
            }
        }
    } catch (error) {
        console.error('Login error:', error);
        showToast('An error occurred. Please try again.', 'error');
    } finally {
        button.classList.remove('loading');
        button.disabled = false;
    }
}

async function handleRegister(event) {
    event.preventDefault();
    const button = document.getElementById('registerButton');
    const name = document.getElementById('registerName').value.trim();
    const username = document.getElementById('registerUsername').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    
    console.log('Registration attempt:', { name, username, email, passwordLength: password.length });
    
    // Basic validation
    if (!name || !username || !email || !password) {
        showToast('Please fill in all fields', 'error');
        return;
    }
    
    if (name.length < 2) {
        showToast('Name must be at least 2 characters long', 'error');
        return;
    }
    
    if (username.length < 3) {
        showToast('Username must be at least 3 characters long', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showToast('Please enter a valid email address', 'error');
        return;
    }
    
    if (password.length < 6) {
        showToast('Password must be at least 6 characters long', 'error');
        return;
    }
    
    try {
        button.classList.add('loading');
        button.disabled = true;
        showToast('Processing registration...', 'info');
        
        // Create form data for backend - simplified schema
        const formData = new FormData();
        formData.append('name', name);
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', password);
        
        console.log('Sending registration data...');
        
        // Submit to backend with cache busting
        const timestamp = new Date().getTime();
        const response = await fetch(`../src/register.php?t=${timestamp}&debug=1`, {
            method: 'POST',
            body: formData,
            cache: 'no-cache',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        
        console.log('Response received:', response);
        
        if (response.redirected) {
            const redirectUrl = response.url;
            console.log('Redirected to:', redirectUrl);
            
            if (redirectUrl.includes('success=1')) {
                showToast('Registration successful! You can now login.', 'success');
                // Switch to login form after success
                setTimeout(() => {
                    container.classList.remove('active');
                    // Clear the form
                    document.getElementById('registerForm').reset();
                }, 1500);
            } else if (redirectUrl.includes('error=1')) {
                console.log('Registration failed - checking for error messages...');
                // Try to get specific error message
                try {
                    const errorResponse = await fetch('../src/get_messages.php?type=registration_errors');
                    const errorData = await errorResponse.json();
                    if (errorData.messages && errorData.messages.length > 0) {
                        showToast(errorData.messages[0], 'error');
                    } else {
                        showToast('Registration failed. Username or email may already exist.', 'error');
                    }
                } catch (msgError) {
                    console.error('Error getting error messages:', msgError);
                    showToast('Registration failed. Please try again.', 'error');
                }
            } else {
                showToast('Registration completed', 'info');
            }
        } else {
            // Try to read response text for debugging
            const responseText = await response.text();
            console.log('Non-redirect response:', responseText);
            
            if (responseText.includes('successful') || responseText.includes('created')) {
                showToast('Registration successful! You can now login.', 'success');
                setTimeout(() => {
                    container.classList.remove('active');
                    document.getElementById('registerForm').reset();
                }, 1500);
            } else {
                showToast('Registration failed. Please try again.', 'error');
            }
        }
    } catch (error) {
        console.error('Registration error:', error);
        showToast('Network error. Please check your connection and try again.', 'error');
    } finally {
        button.classList.remove('loading');
        button.disabled = false;
    }
}

// Helper function to validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Handle social login (placeholder)
function handleSocialLogin(platform) {
    showToast(`${platform} login coming soon!`, 'info');
}

// Handle forgot password (placeholder)
function handleForgotPassword() {
    showToast('Password reset functionality coming soon!', 'info');
}

// Add form event listeners
if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
}

if (registerForm) {
    registerForm.addEventListener('submit', handleRegister);
}

// Display messages from URL parameters
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('error') === '1') {
        // Check for login errors first
        fetch('../src/get_messages.php?type=login_errors')
            .then(response => response.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    showToast(data.messages[0], 'error', 5000);
                    return;
                }
                
                // If no login errors, check for registration errors
                return fetch('../src/get_messages.php?type=registration_errors');
            })
            .then(response => {
                if (response) return response.json();
                return null;
            })
            .then(data => {
                if (data && data.messages && data.messages.length > 0) {
                    showToast(data.messages[0], 'error', 5000);
                    // Switch to register form if registration error
                    container.classList.add('active');
                } else if (!document.querySelector('.toast.show')) {
                    // Only show generic error if no specific error was found
                    showToast('An error occurred. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Error fetching error messages:', error);
                showToast('An error occurred. Please try again.', 'error');
            });
    }

    if (urlParams.get('success') === '1') {
        fetch('../src/get_messages.php?type=registration_success')
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    showToast(data.message, 'success', 5000);
                    // Ensure we're on the login form
                    container.classList.remove('active');
                } else {
                    showToast('Registration successful! You can now login.', 'success');
                    container.classList.remove('active');
                }
            })
            .catch(error => {
                console.error('Error fetching success message:', error);
                showToast('Registration successful! You can now login.', 'success');
                container.classList.remove('active');
            });
    }
});
