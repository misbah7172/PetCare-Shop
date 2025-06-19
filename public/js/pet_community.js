// Pet Community JavaScript
// PawConnect Pet Community Feature - Special Community Access

class PetCommunity {
    constructor() {
        this.currentUser = null;
        this.authToken = localStorage.getItem('communityAuthToken');
        this.currentTab = 'feed';
        this.posts = [];
        this.groups = [];
        this.events = [];
        this.currentPage = 1;
        this.hasMorePosts = true;
        this.selectedCategory = 'all';
        
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
            const response = await fetch('pet_community_api.php/profile', {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.currentUser = data.data.user;
                    this.updateUserInterface();
                    this.showUserDashboard();
                    await this.loadFeed();
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
        const badges = document.getElementById('user-badges');
        const joinDate = document.getElementById('user-join-date');
        
        if (avatar) avatar.src = this.currentUser.avatar_url || 'images/default-avatar.png';
        if (displayName) displayName.textContent = this.currentUser.display_name || this.currentUser.username;
        if (bio) bio.textContent = this.currentUser.bio || 'Share your pet experiences with the community!';
        if (joinDate) joinDate.textContent = this.formatDate(this.currentUser.created_at);
        
        // Update badges
        if (badges && this.currentUser.badges) {
            badges.innerHTML = '';
            this.currentUser.badges.forEach(badge => {
                const badgeElement = document.createElement('span');
                badgeElement.className = 'badge';
                badgeElement.textContent = badge.name;
                badgeElement.title = badge.description;
                badges.appendChild(badgeElement);
            });
        }
    }
    
    async loadFeed() {
        try {
            const params = new URLSearchParams({
                limit: 10,
                offset: (this.currentPage - 1) * 10,
                category: this.selectedCategory
            });
            
            const response = await fetch(`pet_community_api.php/posts?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    if (this.currentPage === 1) {
                        this.posts = data.data;
                    } else {
                        this.posts = [...this.posts, ...data.data];
                    }
                    
                    this.hasMorePosts = data.data.length === 10;
                    this.renderPosts();
                }
            }
        } catch (error) {
            console.error('Error loading feed:', error);
        }
    }
    
    renderPosts() {
        const feedContent = document.getElementById('feed-content');
        if (!feedContent) return;
        
        if (this.currentPage === 1) {
            feedContent.innerHTML = '';
        }
        
        this.posts.forEach(post => {
            const postCard = this.createPostCard(post);
            feedContent.appendChild(postCard);
        });
        
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.style.display = this.hasMorePosts ? 'block' : 'none';
        }
    }
    
    createPostCard(post) {
        const postCard = document.createElement('div');
        postCard.className = 'post-card';
        postCard.dataset.postId = post.id;
        
        const authorName = post.display_name || post.username;
        const authorAvatar = post.avatar_url || 'images/default-avatar.png';
        
        postCard.innerHTML = `
            <div class="post-header">
                <img src="${authorAvatar}" alt="Author" class="post-author-avatar">
                <div class="post-author-info">
                    <div class="post-author-name">${authorName}</div>
                    <div class="post-meta">
                        <span class="post-category">${post.category_name}</span>
                        <span class="post-date">${this.formatDate(post.created_at)}</span>
                    </div>
                </div>
            </div>
            <div class="post-content">
                <h3 class="post-title">${post.title}</h3>
                <p class="post-text">${post.content}</p>
                ${post.image_url ? `<img src="${post.image_url}" alt="Post image" class="post-image">` : ''}
            </div>
            <div class="post-actions">
                <button class="action-btn like-btn" onclick="petCommunity.toggleLike(${post.id})">
                    <i class="fas fa-heart"></i>
                    <span>${post.likes_count || 0}</span>
                </button>
                <button class="action-btn comment-btn" onclick="petCommunity.showComments(${post.id})">
                    <i class="fas fa-comment"></i>
                    <span>${post.comments_count || 0}</span>
                </button>
                <button class="action-btn share-btn" onclick="petCommunity.sharePost(${post.id})">
                    <i class="fas fa-share"></i>
                </button>
            </div>
        `;
        
        return postCard;
    }
    
    async loadGroups() {
        try {
            const response = await fetch('pet_community_api.php/groups', {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.groups = data.data;
                    this.renderGroups();
                }
            }
        } catch (error) {
            console.error('Error loading groups:', error);
        }
    }
    
    renderGroups() {
        const groupsContent = document.getElementById('groups-content');
        if (!groupsContent) return;
        
        groupsContent.innerHTML = '';
        
        this.groups.forEach(group => {
            const groupCard = this.createGroupCard(group);
            groupsContent.appendChild(groupCard);
        });
    }
    
    createGroupCard(group) {
        const groupCard = document.createElement('div');
        groupCard.className = 'group-card';
        groupCard.dataset.groupId = group.id;
        
        groupCard.innerHTML = `
            <div class="group-header">
                <div class="group-avatar">
                    <i class="fas fa-users"></i>
                </div>
                <div class="group-info">
                    <h3 class="group-name">${group.name}</h3>
                    <p class="group-description">${group.description}</p>
                    <div class="group-meta">
                        <span class="group-members">${group.members_count} members</span>
                        <span class="group-privacy">${group.privacy}</span>
                    </div>
                </div>
            </div>
            <div class="group-actions">
                <button class="join-btn" onclick="petCommunity.joinGroup(${group.id})">
                    ${group.is_member ? 'Leave' : 'Join'}
                </button>
            </div>
        `;
        
        return groupCard;
    }
    
    async loadEvents() {
        try {
            const response = await fetch('pet_community_api.php/events', {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.events = data.data;
                    this.renderEvents();
                }
            }
        } catch (error) {
            console.error('Error loading events:', error);
        }
    }
    
    renderEvents() {
        const eventsContent = document.getElementById('events-content');
        if (!eventsContent) return;
        
        eventsContent.innerHTML = '';
        
        this.events.forEach(event => {
            const eventCard = this.createEventCard(event);
            eventsContent.appendChild(eventCard);
        });
    }
    
    createEventCard(event) {
        const eventCard = document.createElement('div');
        eventCard.className = 'event-card';
        eventCard.dataset.eventId = event.id;
        
        const eventDate = new Date(event.event_date);
        const isUpcoming = eventDate > new Date();
        
        eventCard.innerHTML = `
            <div class="event-header">
                <div class="event-date">
                    <div class="event-day">${eventDate.getDate()}</div>
                    <div class="event-month">${eventDate.toLocaleDateString('en-US', { month: 'short' })}</div>
                </div>
                <div class="event-info">
                    <h3 class="event-title">${event.title}</h3>
                    <p class="event-description">${event.description}</p>
                    <div class="event-meta">
                        <span class="event-time">${eventDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</span>
                        <span class="event-location">${event.location}</span>
                        <span class="event-status ${isUpcoming ? 'upcoming' : 'past'}">${isUpcoming ? 'Upcoming' : 'Past'}</span>
                    </div>
                </div>
            </div>
            <div class="event-actions">
                <button class="rsvp-btn" onclick="petCommunity.rsvpEvent(${event.id})">
                    ${event.is_rsvped ? 'Cancel RSVP' : 'RSVP'}
                </button>
            </div>
        `;
        
        return eventCard;
    }
    
    async toggleLike(postId) {
        try {
            const response = await fetch('pet_community_api.php/like', {
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
    
    async joinGroup(groupId) {
        try {
            const response = await fetch('pet_community_api.php/groups/join', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify({
                    group_id: groupId
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Update UI
                    const joinBtn = document.querySelector(`[data-group-id="${groupId}"] .join-btn`);
                    if (joinBtn) {
                        joinBtn.textContent = data.action === 'joined' ? 'Leave' : 'Join';
                    }
                    
                    this.showNotification(`Successfully ${data.action} the group!`, 'success');
                }
            }
        } catch (error) {
            console.error('Error joining group:', error);
            this.showNotification('Error joining group. Please try again.', 'error');
        }
    }
    
    async rsvpEvent(eventId) {
        try {
            const response = await fetch('pet_community_api.php/events/rsvp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: JSON.stringify({
                    event_id: eventId
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Update UI
                    const rsvpBtn = document.querySelector(`[data-event-id="${eventId}"] .rsvp-btn`);
                    if (rsvpBtn) {
                        rsvpBtn.textContent = data.action === 'rsvped' ? 'Cancel RSVP' : 'RSVP';
                    }
                    
                    this.showNotification(`Successfully ${data.action} for the event!`, 'success');
                }
            }
        } catch (error) {
            console.error('Error RSVPing event:', error);
            this.showNotification('Error RSVPing event. Please try again.', 'error');
        }
    }
    
    async createPost(formData) {
        try {
            const response = await fetch('pet_community_api.php/posts', {
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
                    this.closeModal('create-post-modal');
                    this.currentPage = 1;
                    await this.loadFeed();
                    this.showNotification('Post created successfully!', 'success');
                }
            }
        } catch (error) {
            console.error('Error creating post:', error);
            this.showNotification('Error creating post. Please try again.', 'error');
        }
    }
    
    async createGroup(formData) {
        try {
            const response = await fetch('pet_community_api.php/groups', {
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
                    this.closeModal('create-group-modal');
                    await this.loadGroups();
                    this.showNotification('Group created successfully!', 'success');
                }
            }
        } catch (error) {
            console.error('Error creating group:', error);
            this.showNotification('Error creating group. Please try again.', 'error');
        }
    }
    
    async createEvent(formData) {
        try {
            const response = await fetch('pet_community_api.php/events', {
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
                    this.closeModal('create-event-modal');
                    await this.loadEvents();
                    this.showNotification('Event created successfully!', 'success');
                }
            }
        } catch (error) {
            console.error('Error creating event:', error);
            this.showNotification('Error creating event. Please try again.', 'error');
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
            case 'feed':
                this.loadFeed();
                break;
            case 'groups':
                this.loadGroups();
                break;
            case 'events':
                this.loadEvents();
                break;
            case 'profile':
                this.loadUserProfile();
                break;
        }
    }
    
    filterByCategory(category) {
        this.selectedCategory = category;
        this.currentPage = 1;
        this.loadFeed();
        
        // Update category filter UI
        document.querySelectorAll('.category-filter').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }
    
    showAuthModal(type) {
        // Redirect to community login page instead of showing modal
        window.location.href = 'community_login.php';
    }
    
    showCreatePostModal() {
        document.getElementById('create-post-modal').style.display = 'block';
    }
    
    showCreateGroupModal() {
        document.getElementById('create-group-modal').style.display = 'block';
    }
    
    showCreateEventModal() {
        document.getElementById('create-event-modal').style.display = 'block';
    }
    
    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    loadMorePosts() {
        this.currentPage++;
        this.loadFeed();
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
        // Create post form
        document.getElementById('create-post-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleCreatePost();
        });
        
        // Create group form
        document.getElementById('create-group-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleCreateGroup();
        });
        
        // Create event form
        document.getElementById('create-event-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleCreateEvent();
        });
        
        // Character counters
        document.getElementById('post-content')?.addEventListener('input', (e) => {
            const count = e.target.value.length;
            document.getElementById('content-char-count').textContent = count;
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    }
    
    handleCreatePost() {
        const formData = {
            title: document.getElementById('post-title').value,
            content: document.getElementById('post-content').value,
            category_id: document.getElementById('post-category').value,
            image_url: document.getElementById('post-image').value || null
        };
        
        this.createPost(formData);
    }
    
    handleCreateGroup() {
        const formData = {
            name: document.getElementById('group-name').value,
            description: document.getElementById('group-description').value,
            privacy: document.getElementById('group-privacy').value
        };
        
        this.createGroup(formData);
    }
    
    handleCreateEvent() {
        const formData = {
            title: document.getElementById('event-title').value,
            description: document.getElementById('event-description').value,
            event_date: document.getElementById('event-date').value,
            event_time: document.getElementById('event-time').value,
            location: document.getElementById('event-location').value,
            max_participants: document.getElementById('event-max-participants').value || null
        };
        
        this.createEvent(formData);
    }
}

// Initialize Pet Community when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.petCommunity = new PetCommunity();
});

// Global functions for onclick handlers
function showAuthModal(type) {
    // Redirect to community login page
    window.location.href = 'community_login.php';
}

function showCreatePostModal() {
    window.petCommunity.showCreatePostModal();
}

function showCreateGroupModal() {
    window.petCommunity.showCreateGroupModal();
}

function showCreateEventModal() {
    window.petCommunity.showCreateEventModal();
}

function closeModal(modalId) {
    window.petCommunity.closeModal(modalId);
}

function switchTab(tabName) {
    window.petCommunity.switchTab(tabName);
}

function filterByCategory(category) {
    window.petCommunity.filterByCategory(category);
}

function loadMorePosts() {
    window.petCommunity.loadMorePosts();
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