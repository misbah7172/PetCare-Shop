// Simple Rule-Based Chatbot for PawConnect
class PawConnectChatbot {
    constructor() {
        this.chatMessages = document.getElementById('chat-messages');
        this.chatInput = document.getElementById('chat-input');
        this.sendBtn = document.getElementById('send-btn');
        this.typingIndicator = document.getElementById('typing-indicator');
        this.quickActions = document.getElementById('quick-actions');
        
        this.conversationHistory = [];
        this.isTyping = false;
        
        this.initializeEventListeners();
        this.loadChatHistory();
    }

    initializeEventListeners() {
        // Send message on button click
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        
        // Send message on Enter key
        this.chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Auto-resize input
        this.chatInput.addEventListener('input', () => {
            this.chatInput.style.height = 'auto';
            this.chatInput.style.height = this.chatInput.scrollHeight + 'px';
        });

        // Focus input on page load
        window.addEventListener('load', () => {
            this.chatInput.focus();
        });
    }

    async sendMessage(messageText = null) {
        const message = messageText || this.chatInput.value.trim();
        
        if (!message || this.isTyping) return;

        // Clear input
        if (!messageText) {
            this.chatInput.value = '';
            this.chatInput.style.height = 'auto';
        }

        // Add user message to chat
        this.addMessage(message, 'user');
        
        // Show typing indicator
        this.showTypingIndicator();

        // Simulate AI thinking time
        setTimeout(() => {
            this.hideTypingIndicator();
            
            // Get smart response
            const response = this.getSmartResponse(message);
            this.addMessage(response, 'bot');
            
            // Save to conversation history
            this.conversationHistory.push(
                { role: 'user', content: message },
                { role: 'assistant', content: response }
            );
            
            // Save to localStorage
            this.saveChatHistory();
        }, 1000 + Math.random() * 1000); // Random delay between 1-2 seconds
    }

    getSmartResponse(userMessage) {
        const message = userMessage.toLowerCase();
        
        // Pet Adoption Related
        if (message.includes('adopt') || message.includes('adoption') || message.includes('pet adoption')) {
            return `🐕 Great question! Here's how to adopt a pet through PawConnect:

1. **Browse Available Pets** - Visit our Pet Adoption page to see adorable pets looking for homes
2. **Fill Application** - Complete our adoption form with your details
3. **Meet & Greet** - Schedule a meeting with your potential pet
4. **Home Check** - We'll ensure your home is pet-ready
5. **Adoption Day** - Take your new family member home!

Ready to find your perfect companion? Visit our adoption page now! 🏠`;
        }
        
        // Vet Appointment Related
        if (message.includes('vet') || message.includes('appointment') || message.includes('doctor') || message.includes('health')) {
            return `🏥 Need veterinary care? Here's how to book an appointment:

1. **Choose Your Vet** - Browse our network of experienced veterinarians
2. **Select Time** - Pick from available appointment slots
3. **Pet Details** - Tell us about your pet's health concerns
4. **Confirm Booking** - Get instant confirmation
5. **Visit** - Show up for your appointment

We have vets available for:
• Regular check-ups
• Vaccinations
• Emergency care
• Surgery
• Dental care

Book your appointment today! 🩺`;
        }
        
        // Subscription Plans
        if (message.includes('subscription') || message.includes('plan') || message.includes('premium') || message.includes('membership')) {
            return `💳 Choose the perfect subscription plan for you:

**🥉 Bronze Plan - ৳99/month**
• Basic pet care tips
• Access to community forum
• Monthly newsletter

**🥈 Silver Plan - ৳299/month**
• Everything in Bronze
• Priority vet appointments
• Exclusive pet care guides
• 10% off pet shop products

**🥇 Gold Plan - ৳499/month**
• Everything in Silver
• 24/7 vet consultation hotline
• Free pet grooming sessions
• 20% off all services
• VIP community access

Which plan interests you? I can help you choose! 👑`;
        }
        
        // Donation Related
        if (message.includes('donate') || message.includes('donation') || message.includes('help pets') || message.includes('charity')) {
            return `❤️ Thank you for wanting to help! Your donations make a huge difference:

**How to Donate:**
1. Visit our transaction page
2. Select "Donation" as transaction type
3. Choose your amount
4. Use bKash, Nagad, Rocket, or cards
5. Complete payment

**Your donations help:**
• Feed homeless pets
• Provide medical care
• Build better shelters
• Rescue injured animals
• Support adoption programs

Every ৳100 helps feed a pet for a week! 🐾`;
        }
        
        // Pet Care Advice
        if (message.includes('care') || message.includes('food') || message.includes('exercise') || message.includes('training')) {
            return `🐾 Here are some essential pet care tips:

**Daily Care:**
• Fresh water always available
• Regular feeding schedule
• Daily exercise and playtime
• Grooming and hygiene

**Health & Safety:**
• Regular vet check-ups
• Keep vaccinations updated
• Pet-proof your home
• Emergency contact numbers ready

**Training Tips:**
• Use positive reinforcement
• Be patient and consistent
• Start training early
• Reward good behavior

Need specific advice? I can help with any pet care questions! 🏠`;
        }
        
        // Pet Shop
        if (message.includes('shop') || message.includes('buy') || message.includes('product') || message.includes('food') || message.includes('toy')) {
            return `🛍️ Welcome to PawConnect Pet Shop! We have everything your pet needs:

**Pet Food:**
• Premium dry food
• Wet food varieties
• Special diet options
• Treats and snacks

**Accessories:**
• Collars and leashes
• Beds and blankets
• Toys and games
• Grooming supplies

**Health Products:**
• Vitamins and supplements
• Flea and tick treatments
• Dental care products
• First aid kits

Browse our shop for the best prices and quality! 🎁`;
        }
        
        // Payment Methods
        if (message.includes('payment') || message.includes('pay') || message.includes('money') || message.includes('bkash') || message.includes('nagad')) {
            return `💳 We accept multiple payment methods for your convenience:

**Mobile Banking:**
• bKash - Fast and secure
• Nagad - Government-backed
• Rocket - Easy transfers

**Card Payments:**
• Credit cards
• Debit cards
• International cards

**Security Features:**
• SSL encrypted payments
• Secure transaction processing
• Instant payment confirmation
• Receipt via email

All payments are secure and protected! 🔒`;
        }
        
        // General Help
        if (message.includes('help') || message.includes('support') || message.includes('contact') || message.includes('assist')) {
            return `📞 I'm here to help! Here's what I can assist you with:

**Services I can explain:**
• Pet adoption process
• Vet appointment booking
• Subscription plans
• Donation process
• Pet care advice
• Shop products
• Payment methods

**Need more help?**
• Visit our customer support page
• Call our hotline: +880-XXX-XXXXXXX
• Email: support@pawconnect.com
• Live chat available 24/7

What would you like to know more about? 🤝`;
        }
        
        // Greetings
        if (message.includes('hello') || message.includes('hi') || message.includes('hey') || message.includes('good morning') || message.includes('good afternoon')) {
            return `🐾 Hello! Welcome to PawConnect! I'm your AI assistant, ready to help you with:

• 🐕 Pet adoption information
• 🏥 Veterinary appointments
• 💳 Subscription plans
• ❤️ Donation process
• 🛍️ Pet shop products
• 🏠 Pet care advice

How can I assist you today? 😊`;
        }
        
        // About PawConnect
        if (message.includes('about') || message.includes('what is') || message.includes('tell me about') || message.includes('pawconnect')) {
            return `🏠 PawConnect is Bangladesh's premier pet care platform! Here's what we do:

**Our Mission:**
Connecting pets with loving homes and providing comprehensive pet care services.

**What We Offer:**
• Pet adoption services
• Veterinary care network
• Pet shop with quality products
• Community for pet lovers
• Subscription plans
• Donation programs

**Why Choose Us:**
• Trusted by thousands of pet owners
• Professional veterinary network
• Quality pet products
• 24/7 customer support
• Secure payment options

We're here to make pet care easy and accessible! 🐾`;
        }
        
        // Default response with suggestions
        return `🤖 I'm your PawConnect AI assistant! I can help you with:

**Quick Actions:**
• 🐕 Pet adoption process
• 🏥 Book vet appointments  
• 💳 Subscription plans
• ❤️ Make donations
• 🛍️ Shop for pet products
• 🏠 Pet care advice

**Try asking me:**
• "How do I adopt a pet?"
• "What subscription plans do you offer?"
• "How do I book a vet appointment?"
• "Tell me about pet care"

What would you like to know? 😊`;
    }

    addMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.innerHTML = sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        
        const messageText = document.createElement('div');
        messageText.className = 'message-text';
        messageText.innerHTML = this.formatMessage(content);
        
        const messageTime = document.createElement('div');
        messageTime.className = 'message-time';
        messageTime.textContent = this.getCurrentTime();
        
        messageContent.appendChild(messageText);
        messageContent.appendChild(messageTime);
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(messageContent);
        
        this.chatMessages.appendChild(messageDiv);
        this.scrollToBottom();
    }

    formatMessage(content) {
        // Convert line breaks to <br> tags
        return content.replace(/\n/g, '<br>');
    }

    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    }

    showTypingIndicator() {
        this.isTyping = true;
        this.typingIndicator.style.display = 'flex';
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        this.isTyping = false;
        this.typingIndicator.style.display = 'none';
    }

    scrollToBottom() {
        setTimeout(() => {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }, 100);
    }

    saveChatHistory() {
        try {
            localStorage.setItem('pawconnect_chat_history', JSON.stringify(this.conversationHistory));
        } catch (error) {
            console.error('Error saving chat history:', error);
        }
    }

    loadChatHistory() {
        try {
            const saved = localStorage.getItem('pawconnect_chat_history');
            if (saved) {
                this.conversationHistory = JSON.parse(saved);
            }
        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }

    clearChatHistory() {
        this.conversationHistory = [];
        localStorage.removeItem('pawconnect_chat_history');
        this.chatMessages.innerHTML = '';
        this.addWelcomeMessage();
    }

    addWelcomeMessage() {
        const welcomeMessage = `🐾 Hello! I'm your PawConnect AI Assistant! I'm here to help you with:

• 🐕 Pet adoption information and guidance
• 🏥 Veterinary care and appointment booking
• 🛍️ Pet shop products and recommendations
• 👥 Community features and pet corner
• 💳 Subscription plans and payments
• 📞 General customer support

How can I assist you today?`;

        this.addMessage(welcomeMessage, 'bot');
    }
}

// Quick message function for buttons
function sendQuickMessage(message) {
    chatbot.sendMessage(message);
}

// Initialize chatbot when DOM is loaded
let chatbot;
document.addEventListener('DOMContentLoaded', () => {
    chatbot = new PawConnectChatbot();
});

// Add some utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('bn-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0
    }).format(amount);
}

// Add smooth scrolling for better UX
function smoothScrollTo(element, duration = 300) {
    const targetPosition = element.getBoundingClientRect().top;
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    let startTime = null;

    function animation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const run = ease(timeElapsed, startPosition, distance, duration);
        window.scrollTo(0, run);
        if (timeElapsed < duration) requestAnimationFrame(animation);
    }

    function ease(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t + b;
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
    }

    requestAnimationFrame(animation);
} 