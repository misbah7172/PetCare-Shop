// Pet Corner JavaScript
// PawConnect Pet Corner Feature - Special Community Access

class PetCorner {
    constructor() {
        this.currentUser = null;
        this.authToken = localStorage.getItem('communityAuthToken');
        this.currentTab = 'gallery';
        this.photoPosts = [];
        this.petStories = [];
        this.petProfiles = [];
        this.currentPage = 1;
        this.hasMorePosts = true;
        
        this.init();
    }
    
    async init() {
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);
        
        if (this.authToken) {
            await this.loadUserProfile();
        } else {
            this.showWelcomeSection();
        }
        
        this.setupEventListeners();
    }
    
    updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const dateString = now.toLocaleDateString('en-US', { 
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        });
        
        const timeElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');
        
        if (timeElement) timeElement.textContent = timeString;
        if (dateElement) dateElement.textContent = dateString;
    }
    
    showWelcomeSection() {
        document.getElementById('welcome-section').style.display = 'block';
        document.getElementById('user-dashboard').style.display = 'none';
    }
    
    showUserDashboard() {
        document.getElementById('welcome-section').style.display = 'none';
        document.getElementById('user-dashboard').style.display = 'block';
    }
    
    async loadUserProfile() {
        try {
            const response = await fetch('pet_corner_api.php/profile', {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.currentUser = data.data.user;
                    this.petProfiles = data.data.pets || [];
                    this.updateUserInterface();
                    this.showUserDashboard();
                    await this.loadGallery();
                } else {
                    this.showWelcomeSection();
                }
            } else {
                this.showWelcomeSection();
            }
        } catch (error) {
            console.error('Error loading user profile:', error);
            this.showWelcomeSection();
        }
    }
    
    updateUserInterface() {
        if (!this.currentUser) return;
        
        // Update user info
        const avatar = document.getElementById('user-avatar');
        const displayName = document.getElementById('user-display-name');
        const bio = document.getElementById('user-bio');
        const pets = document.getElementById('user-pets');
        
        if (avatar) avatar.src = this.currentUser.avatar_url || 'images/default-avatar.png';
        if (displayName) displayName.textContent = this.currentUser.display_name || this.currentUser.username;
        if (bio) bio.textContent = this.currentUser.bio || 'Share your pet photos and stories with the community!';
        if (pets) pets.textContent = this.petProfiles.length;
        
        // Update pet profiles list
        this.renderPetProfiles();
    }
    
    renderPetProfiles() {
        const profilesList = document.getElementById('pet-profiles-list');
        if (!profilesList) return;
        
        profilesList.innerHTML = '';
        
        this.petProfiles.forEach(pet => {
            const petItem = document.createElement('div');
            petItem.className = 'pet-profile-item';
            petItem.innerHTML = `
                <div class="pet-avatar">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="pet-info">
                    <div class="pet-name">${pet.pet_name}</div>
                    <div class="pet-details">${pet.breed || pet.pet_type} • ${pet.age || 'Unknown'} ${pet.age_unit || 'years'}</div>
                </div>
            `;
            profilesList.appendChild(petItem);
        });
    }
    
    async loadGallery() {
        try {
            const params = new URLSearchParams({
                limit: 10,
                offset: (this.currentPage - 1) * 10
            });
            
            const response = await fetch(`pet_corner_api.php/photos?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    if (this.currentPage === 1) {
                        this.photoPosts = data.data;
                    } else {
                        this.photoPosts = [...this.photoPosts, ...data.data];
                    }
                    
                    this.hasMorePosts = data.data.length === 10;
                    this.renderPhotoPosts();
                }
            }
        } catch (error) {
            console.error('Error loading gallery:', error);
        }
    }
    
    renderPhotoPosts() {
        const galleryContent = document.getElementById('gallery-content');
        if (!galleryContent) return;
        
        if (this.currentPage === 1) {
            galleryContent.innerHTML = '';
        }
        
        this.photoPosts.forEach(post => {
            const postCard = this.createPhotoPostCard(post);
            galleryContent.appendChild(postCard);
        });
        
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.style.display = this.hasMorePosts ? 'block' : 'none';
        }
    }
    
    createPhotoPostCard(post) {
        const postCard = document.createElement('div');
        postCard.className = 'photo-post-card';
        postCard.dataset.postId = post.id;
        
        const authorName = post.is_anonymous ? 'Anonymous' : (post.display_name || post.username);
        const authorAvatar = post.avatar_url || 'images/default-avatar.png';
        
        // Extract photo URL from media_urls JSON
        let photoUrl = 'images/default-pet.jpg';
        if (post.media_urls) {
            try {
                const mediaUrls = JSON.parse(post.media_urls);
                if (mediaUrls && mediaUrls.length > 0) {
                    photoUrl = mediaUrls[0];
                }
            } catch (e) {
                console.error('Error parsing media URLs:', e);
            }
        }
        
        postCard.innerHTML = `
            <div class="post-header">
                <img src="${authorAvatar}" alt="Author" class="post-author-avatar">
                <div class="post-author-info">
                    <div class="post-author-name">${authorName}</div>
                    <div class="post-meta">
                        <span>${this.formatDate(post.created_at)}</span>
                    </div>
                </div>
            </div>
            <div class="post-photo">
                <img src="${photoUrl}" alt="Pet photo" onerror="this.src='images/default-pet.jpg'">
            </div>
            <div class="post-caption">${post.content}</div>
            <div class="post-actions">
                <button class="action-btn like-btn" onclick="petCorner.toggleLike(${post.id})">
                    <i class="fas fa-heart"></i>
                    <span>${post.likes_count || 0}</span>
                </button>
                <button class="action-btn comment-btn" onclick="petCorner.showComments(${post.id})">
                    <i class="fas fa-comment"></i>
                    <span>${post.comments_count || 0}</span>
                </button>
            </div>
        `;
        
        return postCard;
    }
    
    async loadStories() {
        try {
            const params = new URLSearchParams({
                limit: 10,
                offset: (this.currentPage - 1) * 10
            });
            
            const response = await fetch(`pet_corner_api.php/stories?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    if (this.currentPage === 1) {
                        this.petStories = data.data;
                    } else {
                        this.petStories = [...this.petStories, ...data.data];
                    }
                    
                    this.hasMorePosts = data.data.length === 10;
                    this.renderPetStories();
                }
            }
        } catch (error) {
            console.error('Error loading stories:', error);
        }
    }
    
    renderPetStories() {
        const storiesContent = document.getElementById('stories-content');
        if (!storiesContent) return;
        
        if (this.currentPage === 1) {
            storiesContent.innerHTML = '';
        }
        
        this.petStories.forEach(story => {
            const storyCard = this.createStoryCard(story);
            storiesContent.appendChild(storyCard);
        });
        
        const loadMoreBtn = document.getElementById('load-more-stories-btn');
        if (loadMoreBtn) {
            loadMoreBtn.style.display = this.hasMorePosts ? 'block' : 'none';
        }
    }
    
    createStoryCard(story) {
        const storyCard = document.createElement('div');
        storyCard.className = 'story-card';
        storyCard.dataset.storyId = story.id;
        
        const authorName = story.is_anonymous ? 'Anonymous' : (story.display_name || story.username);
        const authorAvatar = story.avatar_url || 'images/default-avatar.png';
        
        storyCard.innerHTML = `
            <div class="story-header">
                <img src="${authorAvatar}" alt="Author" class="story-author-avatar">
                <div class="story-author-info">
                    <div class="story-author-name">${authorName}</div>
                    <div class="story-meta">
                        <span>${this.formatDate(story.created_at)}</span>
                    </div>
                </div>
            </div>
            <div class="story-title">${story.title}</div>
            <div class="story-content">${story.content}</div>
            <div class="story-actions">
                <button class="vote-btn upvote-btn" onclick="petCorner.voteStory(${story.id}, 'upvote')">
                    <i class="fas fa-arrow-up"></i>
                    <span>${story.upvotes || 0}</span>
                </button>
                <button class="vote-btn downvote-btn" onclick="petCorner.voteStory(${story.id}, 'downvote')">
                    <i class="fas fa-arrow-down"></i>
                    <span>${story.downvotes || 0}</span>
                </button>
            </div>
        `;
        
        return storyCard;
    }
    
    async toggleLike(postId) {
        try {
            const response = await fetch('pet_corner_api.php/like', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify({
                    post_id: postId
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Update UI
                    const likeBtn = document.querySelector(`[data-post-id="${postId}"] .like-btn`);
                    if (likeBtn) {
                        const icon = likeBtn.querySelector('i');
                        const count = likeBtn.querySelector('span');
                        
                        if (data.action === 'liked') {
                            icon.style.color = '#e74c3c';
                            count.textContent = parseInt(count.textContent) + 1;
                        } else {
                            icon.style.color = '#666';
                            count.textContent = parseInt(count.textContent) - 1;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    }
    
    async voteStory(storyId, voteType) {
        try {
            const response = await fetch('pet_corner_api.php/vote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify({
                    post_id: storyId,
                    vote_type: voteType
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Update UI
                    const storyCard = document.querySelector(`[data-story-id="${storyId}"]`);
                    if (storyCard) {
                        const upvoteBtn = storyCard.querySelector('.upvote-btn span');
                        const downvoteBtn = storyCard.querySelector('.downvote-btn span');
                        
                        // This is a simplified update - in a real app you'd track the user's vote
                        if (voteType === 'upvote') {
                            upvoteBtn.textContent = parseInt(upvoteBtn.textContent) + 1;
                        } else {
                            downvoteBtn.textContent = parseInt(downvoteBtn.textContent) + 1;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error voting on story:', error);
        }
    }
    
    async createPhotoPost(formData) {
        try {
            const response = await fetch('pet_corner_api.php/photos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify(formData)
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.closeModal('share-photo-modal');
                    this.currentPage = 1;
                    await this.loadGallery();
                    this.showNotification('Photo shared successfully!', 'success');
                }
            }
        } catch (error) {
            console.error('Error creating photo post:', error);
            this.showNotification('Error sharing photo. Please try again.', 'error');
        }
    }
    
    async createPetStory(formData) {
        try {
            const response = await fetch('pet_corner_api.php/stories', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify(formData)
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.closeModal('share-story-modal');
                    this.currentPage = 1;
                    await this.loadStories();
                    this.showNotification('Story shared successfully!', 'success');
                }
            }
        } catch (error) {
            console.error('Error creating story:', error);
            this.showNotification('Error sharing story. Please try again.', 'error');
        }
    }
    
    async addPet(formData) {
        try {
            const response = await fetch('pet_corner_api.php/pets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify(formData)
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.closeModal('add-pet-modal');
                    await this.loadUserProfile();
                    this.showNotification('Pet added successfully!', 'success');
                }
            }
        } catch (error) {
            console.error('Error adding pet:', error);
            this.showNotification('Error adding pet. Please try again.', 'error');
        }
    }
    
    switchTab(tabName) {
        this.currentTab = tabName;
        
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`${tabName}-tab`).classList.add('active');
        
        // Load content based on tab
        switch (tabName) {
            case 'gallery':
                this.loadGallery();
                break;
            case 'stories':
                this.loadStories();
                break;
            case 'profiles':
                this.loadUserProfile();
                break;
        }
    }
    
    showAuthModal(type) {
        // Redirect to community login page instead of showing modal
        window.location.href = 'community_login.php';
    }
    
    showSharePhotoModal() {
        document.getElementById('share-photo-modal').style.display = 'block';
    }
    
    showShareStoryModal() {
        document.getElementById('share-story-modal').style.display = 'block';
    }
    
    showAddPetModal() {
        document.getElementById('add-pet-modal').style.display = 'block';
    }
    
    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    loadMorePosts() {
        this.currentPage++;
        if (this.currentTab === 'gallery') {
            this.loadGallery();
        } else if (this.currentTab === 'stories') {
            this.loadStories();
        }
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInHours = (now - date) / (1000 * 60 * 60);
        
        if (diffInHours < 1) {
            return 'Just now';
        } else if (diffInHours < 24) {
            return `${Math.floor(diffInHours)}h ago`;
        } else if (diffInHours < 168) {
            return `${Math.floor(diffInHours / 24)}d ago`;
        } else {
            return date.toLocaleDateString();
        }
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 3000;
            animation: slideIn 0.3s ease;
            max-width: 300px;
        `;
        
        notification.style.backgroundColor = type === 'success' ? '#27ae60' : 
                                           type === 'error' ? '#e74c3c' : '#6f87f7';
        
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    setupEventListeners() {
        // Share photo form
        document.getElementById('share-photo-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSharePhoto();
        });
        
        // Share story form
        document.getElementById('share-story-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleShareStory();
        });
        
        // Add pet form
        document.getElementById('add-pet-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleAddPet();
        });
        
        // Character counters
        document.getElementById('photo-caption')?.addEventListener('input', (e) => {
            const count = e.target.value.length;
            document.getElementById('caption-char-count').textContent = count;
        });
        
        document.getElementById('story-content')?.addEventListener('input', (e) => {
            const count = e.target.value.length;
            document.getElementById('story-char-count').textContent = count;
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    }
    
    handleSharePhoto() {
        const formData = {
            photo_url: document.getElementById('photo-url').value,
            caption: document.getElementById('photo-caption').value,
            is_anonymous: document.getElementById('photo-anonymous').checked
        };
        
        this.createPhotoPost(formData);
    }
    
    handleShareStory() {
        const formData = {
            title: document.getElementById('story-title').value,
            content: document.getElementById('story-content').value,
            is_anonymous: document.getElementById('story-anonymous').checked
        };
        
        this.createPetStory(formData);
    }
    
    handleAddPet() {
        const formData = {
            pet_name: document.getElementById('pet-name').value,
            pet_type: document.getElementById('pet-type').value,
            breed: document.getElementById('pet-breed').value,
            age: document.getElementById('pet-age').value || null,
            age_unit: document.getElementById('pet-age-unit').value,
            gender: document.getElementById('pet-gender').value,
            description: document.getElementById('pet-description').value
        };
        
        this.addPet(formData);
    }
}

// Initialize Pet Corner when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.petCorner = new PetCorner();
});

// Global functions for onclick handlers
function showAuthModal(type) {
    // Redirect to community login page
    window.location.href = 'community_login.php';
}

function showSharePhotoModal() {
    window.petCorner.showSharePhotoModal();
}

function showShareStoryModal() {
    window.petCorner.showShareStoryModal();
}

function showAddPetModal() {
    window.petCorner.showAddPetModal();
}

function closeModal(modalId) {
    window.petCorner.closeModal(modalId);
}

function switchTab(tabName) {
    window.petCorner.switchTab(tabName);
}

function loadMorePosts() {
    window.petCorner.loadMorePosts();
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style); 