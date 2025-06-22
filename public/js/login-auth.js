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

// Password visibility toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bxs-lock-alt');
        icon.classList.add('bx-show-alt');
    } else {
        input.type = 'password';
        icon.classList.remove('bx-show-alt');
        icon.classList.add('bxs-lock-alt');
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength += 1;
    
    // Contains number
    if (/\d/.test(password)) strength += 1;
    
    // Contains lowercase
    if (/[a-z]/.test(password)) strength += 1;
    
    // Contains uppercase
    if (/[A-Z]/.test(password)) strength += 1;
    
    // Contains special character
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    return strength;
}

// Update password strength meter
const registerPasswordInput = document.getElementById('registerPassword');
if (registerPasswordInput) {
    registerPasswordInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        const strengthText = ['None', 'Weak', 'Medium', 'Strong', 'Very Strong'];
        const strengthClass = ['', 'weak', 'medium', 'strong', 'very-strong'];
        
        const strengthMeter = document.getElementById('strengthMeter');
        const strengthTextElement = document.getElementById('strengthText');
        
        if (strengthMeter && strengthTextElement) {
            strengthMeter.className = 'strength-meter ' + strengthClass[strength];
            strengthTextElement.textContent = 'Password Strength: ' + strengthText[strength];
        }
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

// Handle login form submission
if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const button = document.getElementById('loginButton');
        const username = document.getElementById('loginUsername').value.trim();
        const password = document.getElementById('loginPassword').value;
        
        // Validation
        if (!username || !password) {
            showToast('Please fill in all fields', 'error');
            return;
        }
        
        // Add loading state
        button.classList.add('loading');
        
        try {
            // Create form data
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            
            // Submit form
            const response = await fetch('../src/login.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.redirected) {
                // Login successful, redirect
                window.location.href = response.url;
            } else {
                // Handle error response
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
        }
    });
}

// Handle register form submission
if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const button = document.getElementById('registerButton');
        const firstName = document.getElementById('registerFirstName').value.trim();
        const lastName = document.getElementById('registerLastName').value.trim();
        const username = document.getElementById('registerUsername').value.trim();
        const email = document.getElementById('registerEmail').value.trim();
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('registerConfirmPassword').value;
        
        // Validation
        const errors = [];
        
        if (!firstName) errors.push('First name is required');
        if (!lastName) errors.push('Last name is required');
        if (!username) errors.push('Username is required');
        if (username.length < 3) errors.push('Username must be at least 3 characters long');
        if (!email) errors.push('Email is required');
        if (!isValidEmail(email)) errors.push('Please enter a valid email address');
        if (!password) errors.push('Password is required');
        if (password.length < 6) errors.push('Password must be at least 6 characters long');
        if (password !== confirmPassword) errors.push('Passwords do not match');
        
        if (errors.length > 0) {
            showToast(errors[0], 'error');
            return;
        }
        
        // Add loading state
        button.classList.add('loading');
        
        try {
            // Create form data
            const formData = new FormData();
            formData.append('first_name', firstName);
            formData.append('last_name', lastName);
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);
            
            // Submit form
            const response = await fetch('../src/register.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.redirected) {
                if (response.url.includes('success=1')) {
                    showToast('Registration successful! You can now login.', 'success');
                    // Switch to login form after success
                    setTimeout(() => {
                        container.classList.remove('active');
                    }, 1500);
                } else if (response.url.includes('error=1')) {
                    showToast('Registration failed. Please check your details.', 'error');
                }
            } else {
                const text = await response.text();
                showToast('Registration failed. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showToast('An error occurred. Please try again.', 'error');
        } finally {
            button.classList.remove('loading');
        }
    });
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
