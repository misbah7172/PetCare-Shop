// Adoption Feed JavaScript
class AdoptionFeed {
    constructor() {
        this.apiBase = 'get_adoption_posts.php'; // Use simple direct database API
        this.currentPosts = [];
        this.filters = {
            type: '',
            age: '',
            gender: '',
            status: '',
            search: ''
        };
        this.init();
    }

    async init() {
        console.log('Adoption Feed initializing...');
        
        // Load initial data
        await this.loadAdoptionPosts();
        
        // Setup event listeners
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.searchPets();
                }
            });
        }

        // Filter changes
        const filterSelects = document.querySelectorAll('.filter-select');
        filterSelects.forEach(select => {
            select.addEventListener('change', () => this.applyFilters());
        });
    }    async loadAdoptionPosts() {
        try {
            this.showLoading();

            const response = await fetch(this.apiBase, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to load adoption posts');
            }

            this.currentPosts = result.posts || [];
            this.renderAdoptionPosts();
            this.updateStatistics();

        } catch (error) {
            console.error('Error loading adoption posts:', error);
            this.showError('Failed to load adoption posts');
        } finally {
            this.hideLoading();
        }
    }

    renderAdoptionPosts() {
        const container = document.getElementById('adoptions-container');
        const noResults = document.getElementById('no-results');
        
        if (!container) return;

        if (this.currentPosts.length === 0) {
            container.innerHTML = '';
            noResults.style.display = 'block';
            return;
        }

        noResults.style.display = 'none';
        
        container.innerHTML = this.currentPosts.map(post => `
            <div class="pet-card" data-post-id="${post.id}">
                <div class="pet-image">
                    ${post.photo_path ? 
                        `<img src="${post.photo_path}" alt="${post.pet_name}" loading="lazy">` :
                        `<div class="placeholder-image">
                            <i class="fas fa-${this.getPetIcon(post.pet_type)}"></i>
                        </div>`
                    }
                    <div class="pet-status ${this.getStatusClass(post)}">
                        ${this.getStatusText(post)}
                    </div>
                </div>
                <div class="pet-info">
                    <div class="pet-name">${this.escapeHtml(post.pet_name)}</div>
                    <div class="pet-breed">${post.pet_breed || post.pet_type.charAt(0).toUpperCase() + post.pet_type.slice(1)}</div>
                    
                    <div class="pet-details">
                        <div class="pet-detail">
                            <i class="fas fa-birthday-cake"></i>
                            <span>${post.pet_age || 'Unknown age'}</span>
                        </div>
                        <div class="pet-detail">
                            <i class="fas fa-venus-mars"></i>
                            <span>${post.pet_gender ? post.pet_gender.charAt(0).toUpperCase() + post.pet_gender.slice(1) : 'Unknown'}</span>
                        </div>
                        <div class="pet-detail">
                            <i class="fas fa-weight"></i>
                            <span>${post.pet_weight || 'Unknown weight'}</span>
                        </div>
                        ${post.adoption_fee > 0 ? `
                            <div class="pet-detail">
                                <i class="fas fa-dollar-sign"></i>
                                <span>$${parseFloat(post.adoption_fee).toFixed(2)}</span>
                            </div>
                        ` : `
                            <div class="pet-detail">
                                <i class="fas fa-heart"></i>
                                <span>Free adoption</span>
                            </div>
                        `}
                    </div>
                    
                    ${post.pet_description ? `
                        <div class="pet-description">
                            ${this.escapeHtml(post.pet_description).substring(0, 150)}...
                        </div>
                    ` : ''}
                    
                    ${post.adoption_notes ? `
                        <div class="adoption-notes">
                            <strong>Notes:</strong> ${this.escapeHtml(post.adoption_notes).substring(0, 100)}...
                        </div>
                    ` : ''}
                    
                    <div class="pet-actions">
                        <button class="btn btn-adopt" onclick="adoptionFeed.requestAdoption(${post.id}, '${post.pet_name}')">
                            <i class="fas fa-heart"></i> Adopt ${post.pet_name}
                        </button>
                        <button class="btn btn-details" onclick="adoptionFeed.showPetDetails(${post.id})">
                            <i class="fas fa-info-circle"></i> Details
                        </button>
                    </div>
                    
                    <div class="post-meta">
                        <small><i class="fas fa-clock"></i> Posted ${this.formatDate(post.created_at)}</small>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async requestAdoption(postId, petName) {
        // Check if user is logged in (simplified check)
        const userId = this.getCurrentUserId();
        if (!userId) {
            alert('Please log in to request pet adoption.');
            window.location.href = 'login.html';
            return;
        }

        const message = prompt(`Tell ${petName}'s owner why you'd like to adopt them:`);
        if (!message) return;

        try {
            this.showLoading('Sending adoption request...');

            const requestData = {
                adoption_post_id: postId,
                message: message
            };

            const response = await fetch(`${this.apiBase}?action=request_adoption`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData),
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to send adoption request');
            }

            alert(`Your adoption request for ${petName} has been sent! The owner will be notified.`);
            
            // Optionally redirect to a chat or messages page
            if (result.conversation_id) {
                this.openChat(result.conversation_id, petName);
            }

        } catch (error) {
            console.error('Error requesting adoption:', error);
            alert('Failed to send adoption request: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }    openChat(conversationId, petName) {
        // For now, just show an alert. In a full implementation, this would open a chat window
        const proceed = confirm(`Would you like to start chatting with ${petName}'s owner?`);
        if (proceed) {
            // Redirect to a chat page or open chat modal
            window.location.href = `adoption_chat.html?conversation=${conversationId}&pet=${encodeURIComponent(petName)}`;
        }
    }

    showPetDetails(postId) {
        const post = this.currentPosts.find(p => p.id == postId);
        if (!post) return;

        // Create a modal or detailed view
        const details = `
            Pet: ${post.pet_name}
            Type: ${post.pet_type}
            Breed: ${post.pet_breed || 'Mixed/Unknown'}
            Age: ${post.pet_age || 'Unknown'}
            Gender: ${post.pet_gender || 'Unknown'}
            Weight: ${post.pet_weight || 'Unknown'}
            
            Description: ${post.pet_description || 'No description provided'}
            
            Adoption Notes: ${post.adoption_notes || 'No special notes'}
            
            Reason for adoption: ${post.adoption_reason}
            Fee: ${post.adoption_fee > 0 ? '$' + post.adoption_fee : 'Free'}
            ${post.urgent_adoption ? '\n⚠️ URGENT ADOPTION NEEDED' : ''}
        `;
        
        alert(details);
    }

    searchPets() {
        this.filters.search = document.getElementById('search-input').value;
        this.applyFilters();
    }

    applyFilters() {
        // Update filter values
        this.filters.type = document.getElementById('type-filter').value;
        this.filters.age = document.getElementById('age-filter').value;
        this.filters.gender = document.getElementById('gender-filter').value;
        this.filters.status = document.getElementById('status-filter').value;
        this.filters.search = document.getElementById('search-input').value;

        // Filter posts
        let filteredPosts = this.currentPosts;

        if (this.filters.type) {
            filteredPosts = filteredPosts.filter(post => post.pet_type === this.filters.type);
        }

        if (this.filters.gender) {
            filteredPosts = filteredPosts.filter(post => post.pet_gender === this.filters.gender);
        }

        if (this.filters.status) {
            if (this.filters.status === 'available') {
                filteredPosts = filteredPosts.filter(post => post.status === 'active');
            } else if (this.filters.status === 'pending') {
                filteredPosts = filteredPosts.filter(post => post.status === 'pending');
            }
        }

        if (this.filters.search) {
            const searchTerm = this.filters.search.toLowerCase();
            filteredPosts = filteredPosts.filter(post => 
                post.pet_name.toLowerCase().includes(searchTerm) ||
                (post.pet_breed && post.pet_breed.toLowerCase().includes(searchTerm)) ||
                post.pet_type.toLowerCase().includes(searchTerm) ||
                (post.pet_description && post.pet_description.toLowerCase().includes(searchTerm))
            );
        }

        if (this.filters.age) {
            filteredPosts = filteredPosts.filter(post => {
                if (!post.pet_age) return false;
                const age = post.pet_age.toLowerCase();
                
                if (this.filters.age === 'young') {
                    return age.includes('puppy') || age.includes('kitten') || age.includes('young') || 
                           age.includes('1') || age.includes('2') || age.includes('0');
                } else if (this.filters.age === 'adult') {
                    return age.includes('3') || age.includes('4') || age.includes('5') || 
                           age.includes('6') || age.includes('7') || age.includes('adult');
                } else if (this.filters.age === 'senior') {
                    return age.includes('8') || age.includes('9') || age.includes('senior') || 
                           age.includes('old') || age.includes('10');
                }
                return true;
            });
        }

        // Temporarily store filtered posts and render
        const originalPosts = this.currentPosts;
        this.currentPosts = filteredPosts;
        this.renderAdoptionPosts();
        this.currentPosts = originalPosts;
    }

    updateStatistics() {
        const totalPets = this.currentPosts.length;
        const adoptedPets = this.currentPosts.filter(p => p.status === 'adopted').length;

        const totalElement = document.getElementById('total-pets');
        const adoptedElement = document.getElementById('adopted-pets');

        if (totalElement) totalElement.textContent = totalPets;
        if (adoptedElement) adoptedElement.textContent = adoptedPets;
    }

    // Utility functions
    getPetIcon(type) {
        const icons = {
            dog: 'dog',
            cat: 'cat',
            bird: 'dove',
            fish: 'fish',
            rabbit: 'rabbit',
            other: 'paw'
        };
        return icons[type] || 'paw';
    }

    getStatusClass(post) {
        if (post.urgent_adoption) return 'status-urgent';
        if (post.status === 'pending') return 'status-pending';
        return 'status-available';
    }

    getStatusText(post) {
        if (post.urgent_adoption) return '🚨 Urgent';
        if (post.status === 'pending') return 'Pending';
        return 'Available';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) return 'Today';
        if (days === 1) return 'Yesterday';
        if (days < 7) return `${days} days ago`;
        if (days < 30) return `${Math.floor(days / 7)} weeks ago`;
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getCurrentUserId() {
        // Simplified user ID detection - in real app, use session manager
        return window.sessionManager?.currentUser?.id || 1;
    }

    showLoading(message = 'Loading...') {
        const loading = document.getElementById('loading-indicator');
        if (loading) {
            loading.style.display = 'block';
            loading.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
        }
    }

    hideLoading() {
        const loading = document.getElementById('loading-indicator');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    showError(message) {
        console.error(message);
        const container = document.getElementById('adoptions-container');
        if (container) {
            container.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Pets</h3>
                    <p>${message}</p>
                    <button class="btn btn-primary" onclick="adoptionFeed.loadAdoptionPosts()">
                        <i class="fas fa-refresh"></i> Try Again
                    </button>
                </div>
            `;
        }
    }
}

// Global functions for onclick handlers
window.searchPets = function() {
    if (window.adoptionFeed) {
        window.adoptionFeed.searchPets();
    }
};

window.filterPets = function() {
    if (window.adoptionFeed) {
        window.adoptionFeed.applyFilters();
    }
};

window.toggleFilterTag = function(element, filterType, value) {
    element.classList.toggle('active');
    
    const selectElement = document.getElementById(filterType + '-filter');
    if (element.classList.contains('active')) {
        selectElement.value = value;
    } else {
        selectElement.value = '';
    }
    
    if (window.adoptionFeed) {
        window.adoptionFeed.applyFilters();
    }
};

window.clearFilters = function() {
    document.getElementById('search-input').value = '';
    document.getElementById('type-filter').value = '';
    document.getElementById('age-filter').value = '';
    document.getElementById('gender-filter').value = '';
    document.getElementById('status-filter').value = '';
    
    document.querySelectorAll('.filter-tag').forEach(tag => {
        tag.classList.remove('active');
    });
    
    if (window.adoptionFeed) {
        window.adoptionFeed.applyFilters();
    }
};

// Initialize adoption feed when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.adoptionFeed = new AdoptionFeed();
});
