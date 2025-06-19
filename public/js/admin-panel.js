// Admin Panel JavaScript
class AdminPanel {
    constructor() {
        this.baseURL = '../src/api/admin.php';
        this.currentSection = 'overview';
        this.currentData = {};
    }

    async init() {
        await this.loadStatistics();
        await this.loadSectionData();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.addEventListener('click', () => {
                const section = item.dataset.section;
                this.showSection(section);
            });
        });

        // Search and filters
        document.getElementById('user-search')?.addEventListener('input', debounce(() => this.filterUsers(), 300));
        document.getElementById('user-role-filter')?.addEventListener('change', () => this.filterUsers());
        document.getElementById('user-status-filter')?.addEventListener('change', () => this.filterUsers());
        
        document.getElementById('pet-search')?.addEventListener('input', debounce(() => this.filterPets(), 300));
        document.getElementById('pet-type-filter')?.addEventListener('change', () => this.filterPets());
        document.getElementById('pet-status-filter')?.addEventListener('change', () => this.filterPets());

        // Forms
        document.getElementById('user-form')?.addEventListener('submit', (e) => this.handleUserSubmit(e));
        document.getElementById('pet-form')?.addEventListener('submit', (e) => this.handlePetSubmit(e));
    }

    async apiCall(action, method = 'GET', data = null) {
        try {
            const config = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            if (data && method !== 'GET') {
                config.body = JSON.stringify(data);
            }

            let url = `${this.baseURL}?action=${action}`;
            if (method === 'GET' && data) {
                const params = new URLSearchParams(data);
                url += '&' + params.toString();
            }

            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API call failed for ${action}:`, error);
            this.showNotification(`API Error: ${error.message}`, 'error');
            throw error;
        }
    }

    async loadStatistics() {
        try {
            const response = await this.apiCall('stats');
            if (response.success) {
                this.updateStatistics(response.data);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    updateStatistics(stats) {
        document.getElementById('total-users').textContent = stats.users || 0;
        document.getElementById('total-pets').textContent = stats.pets || 0;
        document.getElementById('total-adoptions').textContent = stats.adoptions || 0;
        document.getElementById('total-orders').textContent = stats.products || 0;
        document.getElementById('support-tickets').textContent = stats.support_tickets || 0;
        document.getElementById('revenue').textContent = '$' + (stats.revenue || 0).toLocaleString();

        // Update recent activity if available
        if (stats.recent_activity) {
            this.updateRecentActivity(stats.recent_activity);
        }
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity');
        if (!container || !activities.length) return;

        container.innerHTML = activities.map(activity => {
            const icon = this.getActivityIcon(activity.type);
            const time = this.formatTimeAgo(activity.timestamp);
            
            return `
                <div class="activity-item" style="padding: 1rem; background: var(--hover-bg); border-radius: 8px; margin-bottom: 0.5rem;">
                    <i class="${icon}" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                    ${this.getActivityMessage(activity)}
                    <span style="float: right; color: var(--text-secondary); font-size: 0.9rem;">${time}</span>
                </div>
            `;
        }).join('');
    }

    getActivityIcon(type) {
        const icons = {
            'user_registration': 'fas fa-user-plus',
            'adoption_application': 'fas fa-heart',
            'order_placed': 'fas fa-shopping-cart',
            'support_ticket': 'fas fa-support'
        };
        return icons[type] || 'fas fa-info-circle';
    }

    getActivityMessage(activity) {
        const messages = {
            'user_registration': `New user registration: ${activity.data}`,
            'adoption_application': `New adoption application for: ${activity.data}`,
            'order_placed': `New order placed: ${activity.data}`,
            'support_ticket': `New support ticket: ${activity.data}`
        };
        return messages[activity.type] || activity.data;
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInMinutes = Math.floor((now - time) / (1000 * 60));

        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} hours ago`;
        return `${Math.floor(diffInMinutes / 1440)} days ago`;
    }

    async showSection(sectionName) {
        // Update UI
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });

        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.classList.remove('active');
        });

        const section = document.getElementById(sectionName + '-section');
        if (section) {
            section.classList.add('active');
        }

        const navItem = document.querySelector(`[data-section="${sectionName}"]`);
        if (navItem) {
            navItem.classList.add('active');
        }

        this.currentSection = sectionName;

        // Load section-specific data
        await this.loadSectionData(sectionName);
    }

    async loadSectionData(section = this.currentSection) {
        switch (section) {
            case 'users':
                await this.loadUsers();
                break;
            case 'pets':
                await this.loadPets();
                break;
            case 'adoptions':
                await this.loadAdoptions();
                break;
            case 'vets':
                await this.loadVets();
                break;
            case 'products':
                await this.loadProducts();
                break;
            case 'support':
                await this.loadSupportTickets();
                break;
        }
    }

    async loadUsers(filters = {}) {
        try {
            const response = await this.apiCall('users', 'GET', filters);
            if (response.success) {
                this.currentData.users = response.data;
                this.renderUsersTable(response.data);
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    renderUsersTable(users) {
        const tbody = document.getElementById('users-table-body');
        if (!tbody) return;

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td><span class="status-badge status-${user.role}">${user.role}</span></td>
                <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                <td>${new Date(user.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="action-btn" onclick="adminPanel.editUser(${user.id})">Edit</button>
                    <button class="action-btn ${user.status === 'active' ? 'danger' : 'success'}" 
                            onclick="adminPanel.toggleUserStatus(${user.id}, '${user.status}')">
                        ${user.status === 'active' ? 'Suspend' : 'Activate'}
                    </button>
                    <button class="action-btn danger" onclick="adminPanel.deleteUser(${user.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    async loadPets(filters = {}) {
        try {
            const response = await this.apiCall('pets', 'GET', filters);
            if (response.success) {
                this.currentData.pets = response.data;
                this.renderPetsTable(response.data);
            }
        } catch (error) {
            console.error('Error loading pets:', error);
        }
    }

    renderPetsTable(pets) {
        const tbody = document.getElementById('pets-table-body');
        if (!tbody) return;

        tbody.innerHTML = pets.map(pet => `
            <tr>
                <td>${pet.id}</td>
                <td>${pet.name}</td>
                <td>${pet.type}</td>
                <td>${pet.breed || 'Mixed'}</td>
                <td>${pet.age} years</td>
                <td>${pet.owner_username || 'None'}</td>
                <td><span class="status-badge status-${pet.status === 'available' ? 'pending' : 'active'}">${pet.status}</span></td>
                <td>
                    <button class="action-btn" onclick="adminPanel.editPet(${pet.id})">Edit</button>
                    <button class="action-btn danger" onclick="adminPanel.deletePet(${pet.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    async loadAdoptions() {
        try {
            const response = await this.apiCall('adoptions');
            if (response.success) {
                this.currentData.adoptions = response.data;
                // Render adoptions table (similar to users/pets)
            }
        } catch (error) {
            console.error('Error loading adoptions:', error);
        }
    }

    async loadVets() {
        try {
            const response = await this.apiCall('vets');
            if (response.success) {
                this.currentData.vets = response.data;
                // Render vets table (similar to users)
            }
        } catch (error) {
            console.error('Error loading vets:', error);
        }
    }

    async loadProducts() {
        try {
            const response = await this.apiCall('products');
            if (response.success) {
                this.currentData.products = response.data;
                // Render products table
            }
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    async loadSupportTickets() {
        try {
            const response = await this.apiCall('support');
            if (response.success) {
                this.currentData.support = response.data;
                // Render support tickets table
            }
        } catch (error) {
            console.error('Error loading support tickets:', error);
        }
    }

    async handleUserSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const userData = Object.fromEntries(formData);

        try {
            const response = await this.apiCall('users', 'POST', userData);
            if (response.success) {
                this.showNotification('User created successfully!');
                this.closeModal('user-modal');
                await this.loadUsers();
                e.target.reset();
            }
        } catch (error) {
            console.error('Error creating user:', error);
        }
    }

    async handlePetSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const petData = Object.fromEntries(formData);

        try {
            const response = await this.apiCall('pets', 'POST', petData);
            if (response.success) {
                this.showNotification('Pet added successfully!');
                this.closeModal('pet-modal');
                await this.loadPets();
                e.target.reset();
            }
        } catch (error) {
            console.error('Error creating pet:', error);
        }
    }

    async filterUsers() {
        const search = document.getElementById('user-search')?.value || '';
        const role = document.getElementById('user-role-filter')?.value || '';
        const status = document.getElementById('user-status-filter')?.value || '';

        const filters = {};
        if (search) filters.search = search;
        if (role) filters.role = role;
        if (status) filters.status = status;

        await this.loadUsers(filters);
    }

    async filterPets() {
        const search = document.getElementById('pet-search')?.value || '';
        const type = document.getElementById('pet-type-filter')?.value || '';
        const status = document.getElementById('pet-status-filter')?.value || '';

        const filters = {};
        if (search) filters.search = search;
        if (type) filters.type = type;
        if (status) filters.status = status;

        await this.loadPets(filters);
    }

    async editUser(userId) {
        const user = this.currentData.users?.find(u => u.id === userId);
        if (!user) {
            this.showNotification('User not found', 'error');
            return;
        }

        // Populate edit form (you'd need to create an edit modal)
        this.showNotification('Edit functionality will be implemented');
    }

    async toggleUserStatus(userId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
        
        try {
            const response = await this.apiCall('users', 'PUT', {
                id: userId,
                status: newStatus
            });
            
            if (response.success) {
                this.showNotification(`User status changed to ${newStatus}`);
                await this.loadUsers();
            }
        } catch (error) {
            console.error('Error updating user status:', error);
        }
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }

        try {
            const response = await this.apiCall('users', 'DELETE', { id: userId });
            if (response.success) {
                this.showNotification('User deleted successfully');
                await this.loadUsers();
            }
        } catch (error) {
            console.error('Error deleting user:', error);
        }
    }

    async editPet(petId) {
        const pet = this.currentData.pets?.find(p => p.id === petId);
        if (!pet) {
            this.showNotification('Pet not found', 'error');
            return;
        }

        this.showNotification('Edit functionality will be implemented');
    }

    async deletePet(petId) {
        if (!confirm('Are you sure you want to delete this pet?')) {
            return;
        }

        try {
            const response = await this.apiCall('pets', 'DELETE', { id: petId });
            if (response.success) {
                this.showNotification('Pet deleted successfully');
                await this.loadPets();
            }
        } catch (error) {
            console.error('Error deleting pet:', error);
        }
    }

    async generateReport(type) {
        try {
            const response = await this.apiCall('reports', 'GET', { type: type });
            if (response.success) {
                this.downloadReport(response.data, response.filename || `${type}_report.csv`);
                this.showNotification(`${type} report generated successfully`);
            }
        } catch (error) {
            console.error('Error generating report:', error);
        }
    }

    downloadReport(data, filename) {
        if (!data || data.length === 0) {
            this.showNotification('No data to export', 'warning');
            return;
        }

        // Convert to CSV
        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => headers.map(header => 
                JSON.stringify(row[header] || '')
            ).join(','))
        ].join('\n');

        // Download file
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    }

    openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    showNotification(message, type = 'success') {
        const notificationBar = document.getElementById('notification-bar');
        notificationBar.textContent = message;
        notificationBar.className = `notification-bar ${type}`;
        notificationBar.style.display = 'block';

        setTimeout(() => {
            notificationBar.style.display = 'none';
        }, 5000);
    }
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Global admin panel instance
let adminPanel;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    adminPanel = new AdminPanel();
});

// Global functions for inline onclick handlers
function openModal(modalId) {
    if (adminPanel) adminPanel.openModal(modalId);
}

function closeModal(modalId) {
    if (adminPanel) adminPanel.closeModal(modalId);
}

function showSection(section) {
    if (adminPanel) adminPanel.showSection(section);
}

function generateReport(type) {
    if (adminPanel) adminPanel.generateReport(type);
}
