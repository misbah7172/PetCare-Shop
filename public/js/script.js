// Get DOM elements
const container = document.querySelector('.container');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const togglePasswordBtns = document.querySelectorAll('.toggle-password');
const strengthMeter = document.querySelector('.strength-meter');
const strengthValue = document.getElementById('strengthValue');
const toast = document.getElementById('toast');

// Toggle between login and register forms
registerBtn.addEventListener('click', () => {
    container.classList.add('active');
});

loginBtn.addEventListener('click', () => {
    container.classList.remove('active');
});

// Toggle password visibility
togglePasswordBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.previousElementSibling.previousElementSibling;
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.classList.toggle('bx-show-alt');
        this.classList.toggle('bx-hide');
    });
});

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
document.getElementById('registerPassword').addEventListener('input', function() {
    const strength = checkPasswordStrength(this.value);
    const strengthText = ['None', 'Weak', 'Medium', 'Strong', 'Very Strong'];
    const strengthClass = ['', 'weak', 'medium', 'strong', 'strong'];
    
    strengthMeter.className = 'strength-meter ' + strengthClass[strength];
    strengthValue.textContent = strengthText[strength];
});

// Show toast notification
function showToast(message, duration = 3000) {
    toast.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

// Handle form submissions
async function handleLogin(event) {
    event.preventDefault();
    const button = document.getElementById('loginButton');
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;
    
    try {
        button.classList.add('loading');
        
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // TODO: Replace with actual API call
        if (username && password) {
            showToast('Login successful!');
            setTimeout(() => {
                window.location.href = 'home_first.html';
            }, 1000);
        } else {
            showToast('Please fill in all fields');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.');
    } finally {
        button.classList.remove('loading');
    }
}

async function handleRegister(event) {
    event.preventDefault();
    const button = document.getElementById('registerButton');
    const username = document.getElementById('registerUsername').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    
    try {
        button.classList.add('loading');
        
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // TODO: Replace with actual API call
        if (username && email && password) {
            showToast('Registration successful!');
            setTimeout(() => {
                window.location.href = 'home.html';
            }, 1000);
        } else {
            showToast('Please fill in all fields');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.');
    } finally {
        button.classList.remove('loading');
    }
}

// Handle social login
function handleSocialLogin(platform) {
    showToast(`Logging in with ${platform}...`);
    // TODO: Implement social login
}

// Handle forgot password
function handleForgotPassword() {
    showToast('Password reset functionality coming soon!');
    // TODO: Implement forgot password
}

// Add form validation
loginForm.addEventListener('submit', handleLogin);
registerForm.addEventListener('submit', handleRegister);
