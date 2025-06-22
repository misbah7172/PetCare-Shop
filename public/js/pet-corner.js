// Pet Corner JavaScript - Complete functionality
class PetCorner {    constructor() {
        this.apiBase = 'get_pets.php'; // Use simple direct database API in public folder
        this.currentPets = [];
        this.currentActivities = [];
        this.currentPhotos = [];
        this.init();
    }async init() {
        console.log('Pet Corner initializing...');
        
        // Setup event listeners first
        this.setupEventListeners();
        
        // Don't force authentication check, let the original page flow work
        // Only try to load data if we can, but don't break the page if we can't
        setTimeout(() => {
            this.tryLoadUserData();
        }, 2000); // Give time for session manager to load
    }    async tryLoadUserData() {
        try {
            // Try to load pets directly since authentication is bypassed
            console.log('Loading pet data...');
            await this.loadUserPets();
            await this.loadRecentActivities();
            await this.loadPhotos();
        } catch (error) {
            console.log('Could not load data from API:', error);
            // Don't show errors, just let the page work normally
        }
    }

    showAuthPrompt() {
        const container = document.getElementById('pets-container');
        if (container) {
            container.innerHTML = `
                <div class="card text-center" style="padding: 3rem;">
                    <i class="fas fa-lock" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h3>Authentication Required</h3>
                    <p>Please log in to access Pet Corner</p>
                    <button class="btn btn-primary" onclick="window.location.href='login.html'">
                        <i class="fas fa-sign-in-alt"></i> Log In
                    </button>
                </div>
            `;
        }
    }

    setupEventListeners() {
        // Pet form submission
        const petForm = document.getElementById('pet-upload-form');
        if (petForm) {
            petForm.addEventListener('submit', (e) => this.handlePetSubmission(e));
        }

        // Tab switching
        document.querySelectorAll('.pet-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.handleTabSwitch(e));
        });
    }    async makeApiCall(action, method = 'GET', data = null) {
        try {
            const url = `${this.apiBase}?action=${action}`;
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            };

            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(url, options);
            const responseText = await response.text();
            
            // Check if response is JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('API returned non-JSON response:', responseText);
                throw new Error('Server error - please try again later');
            }            if (!response.ok) {
                // Handle authentication errors specially
                if (response.status === 401) {
                    const errorMsg = result.error || 'Please log in to access Pet Corner features';
                    throw new Error(errorMsg);
                }
                throw new Error(result.error || `HTTP ${response.status}`);
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            // Don't show error to user unless it's a form submission
            throw error;
        }
    }    async loadUserPets() {
        try {
            console.log('Loading pets from database...');
            const response = await fetch(this.apiBase);
            const result = await response.json();
            
            if (result.success) {
                this.currentPets = result.pets || [];
                console.log('Loaded pets:', this.currentPets);
                this.renderPets();
            } else {
                throw new Error(result.error || 'Failed to load pets');
            }        } catch (error) {
            console.log('Could not load pets from database:', error);
            this.currentPets = [];
            this.renderPets(); // Always render, even with empty pets to show "Add your first pet"
        }
    }

    async loadRecentActivities() {
        try {
            const result = await this.makeApiCall('activities');
            this.currentActivities = result.activities || [];
            this.renderActivities();
        } catch (error) {
            console.log('Could not load activities from database, using demo mode');
            // Don't break the page, just use empty data
            this.currentActivities = [];
            // Don't call renderActivities() to preserve original content
        }
    }

    async loadPhotos() {
        try {
            const result = await this.makeApiCall('photos');
            this.currentPhotos = result.photos || [];
            this.renderGallery();
        } catch (error) {
            console.log('Could not load photos from database, using demo mode');
            // Don't break the page, just use empty data
            this.currentPhotos = [];
            // Don't call renderGallery() to preserve original content
        }
    }

    renderPets() {
        const container = document.getElementById('pets-container');
        if (!container) return;

        if (this.currentPets.length === 0) {
            container.innerHTML = `
                <div class="pet-card">
                    <div class="pet-image">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="pet-info">
                        <div class="pet-name">Add Your First Pet</div>
                        <div class="pet-details">Click "Add Pet" to get started</div>
                        <div class="pet-actions">
                            <button class="btn btn-primary btn-sm" onclick="petCorner.showTab('upload')">
                                <i class="fas fa-plus"></i> Add Pet
                            </button>
                        </div>
                    </div>
                </div>
            `;
            return;
        }        container.innerHTML = this.currentPets.map(pet => `
            <div class="pet-card" data-pet-id="${pet.id}">
                <div class="pet-image">
                    ${pet.photo_path ? 
                        `<img src="${pet.photo_path}" alt="${pet.name}" style="width: 100%; height: 100%; object-fit: cover;">` :
                        `<i class="fas fa-${this.getPetIcon(pet.type)}"></i>`
                    }
                </div>
                <div class="pet-info">
                    <div class="pet-name">${this.escapeHtml(pet.name)}</div>
                    <div class="pet-details">
                        ${pet.type.charAt(0).toUpperCase() + pet.type.slice(1)}
                        ${pet.breed ? ` • ${pet.breed}` : ''}
                        ${pet.age ? ` • ${pet.age}` : ''}
                    </div>
                    <div class="pet-description">
                        ${pet.description ? this.escapeHtml(pet.description).substring(0, 100) + '...' : ''}
                    </div>                    ${pet.is_for_adoption ? `
                        <div class="adoption-status ${pet.adoption_status === 'pending' ? 'status-pending' : 'status-available'}">
                            💝 Available for Adoption
                        </div>
                    ` : ''}
                    <div class="pet-actions">
                        <button class="btn btn-sm btn-outline" onclick="petCorner.editPet(${pet.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        ${!pet.is_for_adoption ? `
                            <button class="btn btn-sm btn-adopt" onclick="petCorner.markForAdoption(${pet.id})">
                                <i class="fas fa-heart"></i> Mark for Adoption
                            </button>
                        ` : `
                            <button class="btn btn-sm btn-secondary" onclick="petCorner.removeFromAdoption(${pet.id})">
                                <i class="fas fa-times"></i> Remove from Adoption
                            </button>
                        `}
                        <button class="btn btn-sm btn-danger" onclick="petCorner.deletePet(${pet.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderActivities() {
        const container = document.querySelector('#activities .activity-feed');
        if (!container) return;

        if (this.currentActivities.length === 0) {
            container.innerHTML = `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <div class="activity-content">
                        <div><strong>Welcome to Pet Corner!</strong></div>
                        <div class="activity-time">Start by adding your first pet</div>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = this.currentActivities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-${this.getActivityIcon(activity.activity_type)}"></i>
                </div>
                <div class="activity-content">
                    <div><strong>${activity.pet_name}</strong> - ${this.escapeHtml(activity.description)}</div>
                    <div class="activity-time">${this.formatDate(activity.activity_date)}</div>
                </div>
            </div>
        `).join('');
    }

    renderGallery() {
        const container = document.getElementById('gallery-container');
        if (!container) return;

        if (this.currentPhotos.length === 0) {
            container.innerHTML = `
                <div class="card text-center" style="padding: 3rem;">
                    <i class="fas fa-images" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <h3>No Photos Yet</h3>
                    <p>Start by adding your first pet to see photos here</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.currentPhotos.map(photo => `
            <div class="pet-card">
                <div class="pet-image">
                    <img src="${photo.photo_path}" alt="${photo.pet_name}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="pet-info">
                    <div class="pet-name">${this.escapeHtml(photo.pet_name)}</div>
                    <div class="pet-details">${photo.caption || 'No caption'}</div>
                    <div class="pet-actions">
                        <button class="btn btn-sm btn-danger" onclick="petCorner.deletePhoto(${photo.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }    async handlePetSubmission(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        // Validate required fields
        const petName = formData.get('pet_name');
        const petType = formData.get('pet_type');
        const markForAdoption = formData.get('mark_for_adoption');
        
        if (!petName || !petType) {
            alert('Pet name and type are required');
            return;
        }        // Validate adoption fields if marked for adoption
        if (markForAdoption) {
            console.log('Pet marked for adoption, checking adoption fields...');
            console.log('Adoption reason:', formData.get('adoption_reason'));
            console.log('Adoption notes:', formData.get('adoption_notes'));
            console.log('Adoption fee:', formData.get('adoption_fee'));
            
            // Note: adoption_reason is not required for the API anymore, 
            // but we need at least adoption notes for the description
        }

        try {
            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Pet...';
            submitBtn.disabled = true;
              // Try to add to database using FormData directly
            const response = await fetch('add_pet.php', {
                method: 'POST',
                body: formData, // Send FormData directly for file upload
                credentials: 'same-origin'
            });
            
            const responseText = await response.text();
            let result;
            
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('API returned non-JSON response:', responseText);
                throw new Error('Server error - please try again later');
            }
            
            if (!response.ok) {
                throw new Error(result.error || `HTTP ${response.status}`);
            }            // If marked for adoption, create adoption post
            if (markForAdoption && result.pet_id) {
                console.log('Pet created successfully with adoption post!');
                // No need to create adoption post separately, add_pet.php handles it
            }
            
            // Success!
            alert(markForAdoption ? 'Pet added and posted for adoption successfully!' : 'Pet added successfully!');
            this.resetForm();
            this.showTab('my-pets');
            
            // Reload data
            await this.loadUserPets();
            await this.loadRecentActivities();
            
            // Restore button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;        } catch (error) {
            console.error('Error adding pet:', error);
            alert('Failed to add pet: ' + error.message);
            
            // Restore button
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Pet';
                submitBtn.disabled = false;
            }
        }
    }    async createAdoptionPost(petId, formData) {
        try {
            console.log('Creating adoption post for pet ID:', petId);
            
            const pet = this.currentPets.find(p => p.id == petId);
            const petName = formData.get('pet_name') || (pet ? pet.name : 'Pet');
            
            const adoptionData = {
                pet_id: petId,
                title: `${petName} - Looking for a new home`,
                description: formData.get('adoption_notes') || `${petName} is looking for a loving new home.`,
                adoption_fee: formData.get('adoption_fee') || 0,
                location: '', // Could be added to form later
                contact_phone: '', // Could be added to form later  
                contact_email: '' // Could be added to form later
            };

            console.log('Adoption data:', adoptionData);

            const response = await fetch('../src/adoption_api.php?action=create_post', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(adoptionData),
                credentials: 'same-origin'
            });

            const responseText = await response.text();
            console.log('Adoption API response:', responseText);

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse adoption API response:', responseText);
                throw new Error('Invalid response from adoption API');
            }
            
            if (!response.ok) {
                throw new Error(result.error || 'Failed to create adoption post');
            }

            console.log('Adoption post created successfully:', result);
            return result;
        } catch (error) {
            console.error('Error creating adoption post:', error);
            // Don't throw here, as the pet was already added successfully
            alert('Pet added successfully, but there was an issue creating the adoption post: ' + error.message + '. You can mark it for adoption later.');
        }
    }

    async editPet(petId) {
        // For now, just show an alert - you could implement a modal for editing
        const pet = this.currentPets.find(p => p.id == petId);
        if (pet) {
            alert(`Edit functionality for ${pet.name} would be implemented here`);
        }
    }

    async deletePet(petId) {
        const pet = this.currentPets.find(p => p.id == petId);
        if (!pet) return;

        if (!confirm(`Are you sure you want to delete ${pet.name}? This action cannot be undone.`)) {
            return;
        }

        try {
            this.showLoading('Deleting pet...');
            await this.makeApiCall(`delete_pet&id=${petId}`, 'DELETE');
            
            this.showSuccess('Pet deleted successfully');
            await this.loadUserPets();
            await this.loadRecentActivities();
            
        } catch (error) {
            this.showError('Failed to delete pet: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    async deletePhoto(photoId) {
        if (!confirm('Are you sure you want to delete this photo?')) {
            return;
        }

        try {
            await this.makeApiCall(`delete_photo&id=${photoId}`, 'DELETE');
            this.showSuccess('Photo deleted successfully');
            await this.loadPhotos();
        } catch (error) {
            this.showError('Failed to delete photo: ' + error.message);
        }
    }    async markForAdoption(petId) {
        const pet = this.currentPets.find(p => p.id == petId);
        if (!pet) return;

        // Show adoption form in a simple prompt for now
        const title = prompt(`Title for ${pet.name}'s adoption post:`, `${pet.name} - Looking for a new home`);
        if (!title) return;
        
        const description = prompt('Description for potential adopters:', `${pet.name} is looking for a loving new home.`);
        const feeStr = prompt('Adoption fee (leave blank for free):');
        const fee = feeStr ? parseFloat(feeStr) : 0;

        try {
            this.showLoading('Creating adoption post...');
            
            const adoptionData = {
                pet_id: petId,
                title: title,
                description: description || '',
                adoption_fee: fee,
                location: '', // Could be added to form later
                contact_phone: '', // Could be added to form later
                contact_email: '' // Could be added to form later
            };

            const response = await fetch('../src/adoption_api.php?action=create_post', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(adoptionData),
                credentials: 'same-origin'
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.error || 'Failed to create adoption post');
            }

            this.showSuccess(`${pet.name} has been posted for adoption!`);
            await this.loadUserPets();
            
        } catch (error) {
            this.showError('Failed to mark pet for adoption: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    async removeFromAdoption(petId) {
        const pet = this.currentPets.find(p => p.id == petId);
        if (!pet) return;

        if (!confirm(`Are you sure you want to remove ${pet.name} from adoption?`)) {
            return;
        }

        try {
            this.showLoading('Removing from adoption...');
            
            const response = await fetch(`../src/adoption_api.php?action=delete_post&pet_id=${petId}`, {
                method: 'DELETE',
                credentials: 'same-origin'
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.error || 'Failed to remove from adoption');
            }

            this.showSuccess(`${pet.name} has been removed from adoption`);
            await this.loadUserPets();
            
        } catch (error) {
            this.showError('Failed to remove from adoption: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    handleTabSwitch(e) {
        const tab = e.currentTarget;
        const tabName = tab.getAttribute('onclick').match(/showTab\('(.+?)'\)/)[1];
        this.showTab(tabName);
    }

    showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.pet-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab content
        const tabContent = document.getElementById(tabName);
        if (tabContent) {
            tabContent.classList.add('active');
        }
        
        // Add active class to corresponding tab button
        const tabButton = document.querySelector(`[onclick*="showTab('${tabName}')"]`);
        if (tabButton) {
            tabButton.classList.add('active');
        }
    }

    resetForm() {
        const form = document.getElementById('pet-upload-form');
        if (form) {
            form.reset();
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.style.display = 'none';
            }
        }
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

    getActivityIcon(type) {
        const icons = {
            feeding: 'utensils',
            exercise: 'running',
            vet_visit: 'stethoscope',
            grooming: 'cut',
            photo_upload: 'camera',
            other: 'paw'
        };
        return icons[type] || 'paw';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (minutes < 60) return `${minutes} minutes ago`;
        if (hours < 24) return `${hours} hours ago`;
        if (days < 7) return `${days} days ago`;
        
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }    showLoading(message = 'Loading...') {
        console.log('Loading:', message);
        // Don't show loading overlay to preserve original design
    }

    hideLoading() {
        console.log('Loading complete');
        // Don't need to hide anything
    }

    showSuccess(message) {
        alert(message);
    }

    showError(message) {
        alert('Error: ' + message);
    }
}

// Image preview functionality (standalone function)
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize Pet Corner when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Wait for session manager to be ready
    if (window.sessionManager) {
        window.petCorner = new PetCorner();
    } else {
        // Wait for session manager to load
        setTimeout(() => {
            window.petCorner = new PetCorner();
        }, 1000);
    }
});

// Make showTab function globally available for onclick handlers
window.showTab = function(tabName) {
    if (window.petCorner) {
        window.petCorner.showTab(tabName);
    }
};
