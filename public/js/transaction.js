// Transaction Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    initializeTransactionPage();
});

function initializeTransactionPage() {
    // Add event listeners for transaction type buttons
    const typeButtons = document.querySelectorAll('.type-btn');
    typeButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            typeButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update order summary based on transaction type
            updateOrderSummary(this.dataset.type);
        });
    });

    // Add event listeners for payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            paymentMethods.forEach(m => m.classList.remove('selected'));
            // Add selected class to clicked method
            this.classList.add('selected');
            
            // Show appropriate payment form
            showPaymentForm(this.dataset.method);
        });
    });

    // Add input formatting for card number
    const cardNumberInput = document.getElementById('card-number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', formatCardNumber);
    }

    // Add input formatting for expiry date
    const expiryInput = document.getElementById('expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', formatExpiryDate);
    }

    // Add input formatting for CVV
    const cvvInput = document.getElementById('cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', formatCVV);
    }

    // Add input formatting for mobile number
    const mobileInput = document.getElementById('mobile-number');
    if (mobileInput) {
        mobileInput.addEventListener('input', formatMobileNumber);
    }
}

function updateOrderSummary(transactionType, plan = null) {
    const itemName = document.getElementById('item-name');
    const itemPrice = document.getElementById('item-price');
    const totalAmount = document.getElementById('total-amount');
    const paymentAmount = document.getElementById('payment-amount');
    const mobileAmount = document.getElementById('mobile-amount');
    const cardAmount = document.getElementById('card-amount');
    const mobileTransactionType = document.getElementById('mobile-transaction-type');
    const cardTransactionType = document.getElementById('card-transaction-type');

    switch(transactionType) {
        case 'purchase':
            itemName.textContent = 'Premium Pet Food';
            itemPrice.textContent = '৳2,999';
            totalAmount.textContent = '৳3,099';
            if (paymentAmount) paymentAmount.textContent = '3,099';
            if (mobileAmount) mobileAmount.value = '3099';
            if (cardAmount) cardAmount.value = '3099';
            if (mobileTransactionType) mobileTransactionType.value = 'purchase';
            if (cardTransactionType) cardTransactionType.value = 'purchase';
            break;
        case 'subscription':
            let planName = 'Gold Subscription Plan';
            let planPrice = 499;
            
            if (plan) {
                switch(plan.toLowerCase()) {
                    case 'bronze':
                        planName = 'Bronze Subscription Plan';
                        planPrice = 99;
                        break;
                    case 'silver':
                        planName = 'Silver Subscription Plan';
                        planPrice = 299;
                        break;
                    case 'gold':
                        planName = 'Gold Subscription Plan';
                        planPrice = 499;
                        break;
                }
            }
            
            itemName.textContent = planName;
            itemPrice.textContent = `৳${planPrice.toLocaleString()}`;
            totalAmount.textContent = `৳${planPrice.toLocaleString()}`;
            if (paymentAmount) paymentAmount.textContent = planPrice.toString();
            if (mobileAmount) mobileAmount.value = planPrice.toString();
            if (cardAmount) cardAmount.value = planPrice.toString();
            if (mobileTransactionType) mobileTransactionType.value = 'subscription';
            if (cardTransactionType) cardTransactionType.value = 'subscription';
            break;
        case 'donation':
            itemName.textContent = 'Pet Shelter Donation';
            itemPrice.textContent = '৳5,000';
            totalAmount.textContent = '৳5,000';
            if (paymentAmount) paymentAmount.textContent = '5,000';
            if (mobileAmount) mobileAmount.value = '5000';
            if (cardAmount) cardAmount.value = '5000';
            if (mobileTransactionType) mobileTransactionType.value = 'donation';
            if (cardTransactionType) cardTransactionType.value = 'donation';
            break;
    }
}

function showPaymentForm(method) {
    // Hide all payment forms
    const mobileForm = document.getElementById('mobile-payment-form');
    const cardForm = document.getElementById('card-payment-form');
    const instructions = document.getElementById('payment-instructions');

    if (mobileForm) mobileForm.style.display = 'none';
    if (cardForm) cardForm.style.display = 'none';
    if (instructions) instructions.style.display = 'none';

    // Update hidden payment method field
    const mobilePaymentMethod = document.getElementById('mobile-payment-method');
    if (mobilePaymentMethod) mobilePaymentMethod.value = method;

    // Show appropriate form based on method
    if (method === 'card') {
        if (cardForm) cardForm.style.display = 'block';
    } else {
        // Mobile banking methods
        if (mobileForm) mobileForm.style.display = 'block';
        if (instructions) {
            instructions.style.display = 'block';
            updatePaymentInstructions(method);
        }
    }
}

function updatePaymentInstructions(method) {
    const paymentMethodName = document.getElementById('payment-method-name');
    const paymentMethodName2 = document.getElementById('payment-method-name-2');
    const paymentAmount = document.getElementById('payment-amount');

    if (paymentMethodName) paymentMethodName.textContent = method.charAt(0).toUpperCase() + method.slice(1);
    if (paymentMethodName2) paymentMethodName2.textContent = method.charAt(0).toUpperCase() + method.slice(1);
    
    // Update payment number based on method
    const paymentNumber = getPaymentNumber(method);
    const instructionText = document.querySelector('.instruction-step p');
    if (instructionText && paymentNumber) {
        instructionText.innerHTML = `Send ৳<span id="payment-amount">${paymentAmount.textContent}</span> to our <span id="payment-method-name">${method.charAt(0).toUpperCase() + method.slice(1)}</span> number: <strong>${paymentNumber}</strong>`;
    }
}

function getPaymentNumber(method) {
    const paymentNumbers = {
        'bkash': '01XXXXXXXXX',
        'nagad': '01XXXXXXXXX',
        'rocket': '01XXXXXXXXX'
    };
    return paymentNumbers[method] || '01XXXXXXXXX';
}

function formatCardNumber(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
}

function formatExpiryDate(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2, 4);
    }
    e.target.value = value;
}

function formatCVV(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    e.target.value = value.slice(0, 4);
}

function formatMobileNumber(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    e.target.value = value.slice(0, 11);
}

function processTransaction() {
    // Get selected payment method
    const selectedMethod = document.querySelector('.payment-method.selected');
    if (!selectedMethod) {
        showNotification('Please select a payment method', 'error');
        return;
    }

    const method = selectedMethod.dataset.method;
    
    // Validate form based on payment method
    if (method === 'card') {
        if (!validateCardForm()) {
            return;
        }
        // Submit card form
        document.getElementById('card-payment-form-content').submit();
    } else {
        if (!validateMobileForm()) {
            return;
        }
        // Submit mobile form
        document.getElementById('mobile-payment-form-content').submit();
    }
}

function validateCardForm() {
    const cardName = document.getElementById('card-name').value.trim();
    const cardNumber = document.getElementById('card-number').value.trim();
    const expiry = document.getElementById('expiry').value.trim();
    const cvv = document.getElementById('cvv').value.trim();
    const email = document.getElementById('email').value.trim();

    if (!cardName) {
        showNotification('Please enter cardholder name', 'error');
        return false;
    }

    if (!cardNumber || cardNumber.replace(/\s/g, '').length < 16) {
        showNotification('Please enter a valid card number', 'error');
        return false;
    }

    if (!expiry || !/^\d{2}\/\d{2}$/.test(expiry)) {
        showNotification('Please enter a valid expiry date (MM/YY)', 'error');
        return false;
    }

    if (!cvv || cvv.length < 3) {
        showNotification('Please enter a valid CVV', 'error');
        return false;
    }

    if (!email || !isValidEmail(email)) {
        showNotification('Please enter a valid email address', 'error');
        return false;
    }

    return true;
}

function validateMobileForm() {
    const mobileNumber = document.getElementById('mobile-number').value.trim();
    const transactionId = document.getElementById('transaction-id').value.trim();
    const email = document.getElementById('email').value.trim();

    if (!mobileNumber || mobileNumber.length < 11) {
        showNotification('Please enter a valid mobile number', 'error');
        return false;
    }

    if (!transactionId) {
        showNotification('Please enter the transaction ID', 'error');
        return false;
    }

    if (!email || !isValidEmail(email)) {
        showNotification('Please enter a valid email address', 'error');
        return false;
    }

    return true;
}

function validateBillingForm() {
    const address = document.getElementById('address').value.trim();
    const city = document.getElementById('city').value.trim();
    const state = document.getElementById('state').value.trim();
    const zipcode = document.getElementById('zipcode').value.trim();
    const country = document.getElementById('country').value;

    if (!address) {
        showNotification('Please enter your street address', 'error');
        return false;
    }

    if (!city) {
        showNotification('Please enter your city', 'error');
        return false;
    }

    if (!state) {
        showNotification('Please enter your state/division', 'error');
        return false;
    }

    if (!zipcode) {
        showNotification('Please enter your ZIP code', 'error');
        return false;
    }

    if (!country) {
        showNotification('Please select your country', 'error');
        return false;
    }

    return true;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;

    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'error' ? '#fed7d7' : '#c6f6d5'};
        color: ${type === 'error' ? '#742a2a' : '#22543d'};
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        z-index: 10000;
        max-width: 300px;
        animation: slideIn 0.3s ease;
    `;

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    // Add to page
    document.body.appendChild(notification);

    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Add global function for processTransaction
window.processTransaction = processTransaction; 