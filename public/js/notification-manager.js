// Notification Manager for PawConnect
class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.isOpen = false;
        this.init();
    }
    
    async init() {
        await this.loadUnreadCount();
        this.createNotificationElements();
        this.setupEventListeners();
        
        // Update unread count every 30 seconds
        setInterval(() => this.loadUnreadCount(), 30000);
    }
    
    async loadUnreadCount() {
        try {
            const response = await fetch('../src/notifications_api.php?action=count');
            const data = await response.json();
            
            if (data.success) {
                this.unreadCount = data.unread_count;
                this.updateNotificationBadge();
            }
        } catch (error) {
            console.error('Error loading unread count:', error);
        }
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('../src/notifications_api.php?action=list&limit=20');
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.renderNotifications();
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }
    
    createNotificationElements() {
        // Create notification bell icon and dropdown
        const notificationHTML = `
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
        `;
        
        // Add CSS for notifications
        const notificationCSS = `
            <style id="notification-styles">
                .notification-container {
                    position: relative;
                    display: inline-block;
                }
                
                .notification-bell {
                    position: relative;
                    cursor: pointer;
                    padding: 8px;
                    border-radius: 50%;
                    transition: background-color 0.3s ease;
                }
                
                .notification-bell:hover {
                    background-color: rgba(255, 255, 255, 0.1);
                }
                
                .notification-bell i {
                    font-size: 1.2em;
                    color: #fff;
                }
                
                .notification-badge {
                    position: absolute;
                    top: 0;
                    right: 0;
                    background: #ff4444;
                    color: white;
                    border-radius: 50%;
                    padding: 2px 6px;
                    font-size: 0.8em;
                    font-weight: bold;
                    min-width: 18px;
                    height: 18px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 2px solid #fff;
                }
                
                .notification-dropdown {
                    position: absolute;
                    top: 100%;
                    right: 0;
                    width: 350px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    border: 1px solid rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                    max-height: 400px;
                    overflow: hidden;
                }
                
                .notification-header {
                    padding: 16px;
                    border-bottom: 1px solid #eee;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .notification-header h3 {
                    margin: 0;
                    color: #333;
                    font-size: 1.1em;
                }
                
                .mark-all-read-btn {
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 0.85em;
                    transition: background-color 0.3s ease;
                }
                
                .mark-all-read-btn:hover {
                    background: #0056b3;
                }
                
                .notification-list {
                    max-height: 300px;
                    overflow-y: auto;
                }
                
                .notification-item {
                    padding: 12px 16px;
                    border-bottom: 1px solid #f0f0f0;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                    position: relative;
                }
                
                .notification-item:hover {
                    background-color: #f8f9fa;
                }
                
                .notification-item.unread {
                    background-color: #f0f8ff;
                    border-left: 3px solid #007bff;
                }
                
                .notification-item.unread::before {
                    content: '';
                    position: absolute;
                    left: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 8px;
                    height: 8px;
                    background: #007bff;
                    border-radius: 50%;
                }
                
                .notification-title {
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 4px;
                }
                
                .notification-message {
                    color: #666;
                    font-size: 0.9em;
                    margin-bottom: 4px;
                    line-height: 1.4;
                }
                
                .notification-time {
                    color: #999;
                    font-size: 0.8em;
                }
                
                .loading {
                    text-align: center;
                    padding: 20px;
                    color: #666;
                }
                
                .no-notifications {
                    text-align: center;
                    padding: 20px;
                    color: #999;
                }
            </style>
        `;
        
        // Add CSS to head
        if (!document.getElementById('notification-styles')) {
            document.head.insertAdjacentHTML('beforeend', notificationCSS);
        }
        
        return notificationHTML;
    }
    
    updateNotificationBadge() {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    async toggleNotifications() {
        const dropdown = document.getElementById('notification-dropdown');
        
        if (!dropdown) return;
        
        if (this.isOpen) {
            dropdown.style.display = 'none';
            this.isOpen = false;
        } else {
            dropdown.style.display = 'block';
            this.isOpen = true;
            await this.loadNotifications();
        }
    }
    
    renderNotifications() {
        const list = document.getElementById('notification-list');
        if (!list) return;
        
        if (this.notifications.length === 0) {
            list.innerHTML = '<div class="no-notifications">No notifications yet</div>';
            return;
        }
        
        const notificationsHTML = this.notifications.map(notification => `
            <div class="notification-item ${!notification.is_read ? 'unread' : ''}" 
                 onclick="notificationManager.handleNotificationClick(${notification.id}, '${notification.type}', ${notification.related_id})">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${notification.time_ago}</div>
            </div>
        `).join('');
        
        list.innerHTML = notificationsHTML;
    }
    
    async handleNotificationClick(notificationId, type, relatedId) {
        // Mark notification as read
        await this.markAsRead(notificationId);
        
        // Navigate based on notification type
        switch (type) {
            case 'adoption_interest':
            case 'adoption_approved':
            case 'adoption_rejected':
                window.location.href = `chat.html?type=adoption&id=${relatedId}`;
                break;
            case 'shop_interest':
                window.location.href = `chat.html?type=shop&id=${relatedId}`;
                break;
            case 'comment':
            case 'like':
                window.location.href = `pet_community.html#post-${relatedId}`;
                break;
            default:
                // Close dropdown
                this.toggleNotifications();
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch('../src/notifications_api.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            });
            
            const data = await response.json();
            if (data.success) {
                // Update local state
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.is_read = true;
                }
                
                // Update unread count
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateNotificationBadge();
                this.renderNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('../src/notifications_api.php?action=mark_all_read', {
                method: 'POST'
            });
            
            const data = await response.json();
            if (data.success) {
                // Update local state
                this.notifications.forEach(n => n.is_read = true);
                this.unreadCount = 0;
                this.updateNotificationBadge();
                this.renderNotifications();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    setupEventListeners() {
        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            const notificationContainer = event.target.closest('.notification-container');
            if (!notificationContainer && this.isOpen) {
                this.toggleNotifications();
            }
        });
    }
}

// Global notification manager instance
window.notificationManager = new NotificationManager();
