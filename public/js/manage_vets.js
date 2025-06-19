// Veterinarian Management System
class VetManager {
    constructor() {
        this.vets = [];
        this.currentVet = null;
        this.isEditing = false;
        
        this.initializeEventListeners();
        this.loadVets();
        this.updateStatistics();
    }

    initializeEventListeners() {
        // Search functionality
        document.getElementById('vet-search').addEventListener('input', (e) => {
            this.filterVets();
        });

        // Filter functionality
        document.getElementById('specialty-filter').addEventListener('change', () => {
            this.filterVets();
        });

        document.getElementById('status-filter').addEventListener('change', () => {
            this.filterVets();
        });

        // Form submission
        document.getElementById('vet-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveVet();
        });
    }

    loadVets() {
        // Sample data - in real app, this would come from database
        this.vets = [
            {
                id: 1,
                name: "Dr. Sarah Ahmed",
                email: "sarah.ahmed@vetclinic.com",
                phone: "+880-1712-345678",
                specialty: "General Practice",
                experience: 8,
                status: "Active",
                address: "House 123, Road 12, Dhanmondi, Dhaka",
                bio: "Experienced veterinarian specializing in general pet care and preventive medicine.",
                consultation_fee: 1500,
                availability: "Full Time",
                photo: "images/vet1.jpg",
                rating: 4.8,
                appointments: 156
            },
            {
                id: 2,
                name: "Dr. Mohammad Rahman",
                email: "m.rahman@vetclinic.com",
                phone: "+880-1812-345679",
                specialty: "Surgery",
                experience: 12,
                status: "Active",
                address: "House 456, Road 8, Gulshan-2, Dhaka",
                bio: "Specialized in surgical procedures and emergency care for pets.",
                consultation_fee: 2500,
                availability: "Full Time",
                photo: "images/vet2.jpg",
                rating: 4.9,
                appointments: 203
            },
            {
                id: 3,
                name: "Dr. Fatima Khan",
                email: "fatima.khan@vetclinic.com",
                phone: "+880-1912-345680",
                specialty: "Dental Care",
                experience: 6,
                status: "Active",
                address: "House 789, Road 15, Banani, Dhaka",
                bio: "Dental specialist with expertise in pet oral health and dental procedures.",
                consultation_fee: 1800,
                availability: "Part Time",
                photo: "images/vet3.jpg",
                rating: 4.7,
                appointments: 89
            }
        ];

        this.renderVets();
    }

    renderVets() {
        const grid = document.getElementById('vets-grid');
        grid.innerHTML = '';

        this.vets.forEach(vet => {
            const card = this.createVetCard(vet);
            grid.appendChild(card);
        });
    }

    createVetCard(vet) {
        const card = document.createElement('div');
        card.className = 'vet-card';
        
        const statusClass = vet.status.toLowerCase().replace(' ', '-');
        
        card.innerHTML = `
            <div class="vet-header">
                <div class="vet-photo">
                    <img src="${vet.photo || 'images/default-vet.jpg'}" alt="${vet.name}">
                </div>
                <div class="vet-info">
                    <h3>${vet.name}</h3>
                    <p class="vet-specialty">${vet.specialty}</p>
                    <span class="status-badge ${statusClass}">${vet.status}</span>
                </div>
            </div>
            <div class="vet-details">
                <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <span>${vet.email}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-phone"></i>
                    <span>${vet.phone}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${vet.address}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-clock"></i>
                    <span>${vet.availability}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-star"></i>
                    <span>${vet.rating} (${vet.appointments} appointments)</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-money-bill"></i>
                    <span>৳${vet.consultation_fee} consultation fee</span>
                </div>
            </div>
            <div class="vet-actions">
                <button class="btn-secondary" onclick="vetManager.editVet(${vet.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn-danger" onclick="vetManager.deleteVet(${vet.id})">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        `;

        return card;
    }

    filterVets() {
        const searchTerm = document.getElementById('vet-search').value.toLowerCase();
        const specialtyFilter = document.getElementById('specialty-filter').value;
        const statusFilter = document.getElementById('status-filter').value;

        const filteredVets = this.vets.filter(vet => {
            const matchesSearch = vet.name.toLowerCase().includes(searchTerm) ||
                                vet.email.toLowerCase().includes(searchTerm) ||
                                vet.specialty.toLowerCase().includes(searchTerm);
            
            const matchesSpecialty = !specialtyFilter || vet.specialty === specialtyFilter;
            const matchesStatus = !statusFilter || vet.status === statusFilter;

            return matchesSearch && matchesSpecialty && matchesStatus;
        });

        this.renderFilteredVets(filteredVets);
    }

    renderFilteredVets(vets) {
        const grid = document.getElementById('vets-grid');
        grid.innerHTML = '';

        vets.forEach(vet => {
            const card = this.createVetCard(vet);
            grid.appendChild(card);
        });
    }

    openAddVetModal() {
        this.isEditing = false;
        this.currentVet = null;
        document.getElementById('modal-title').textContent = 'Add New Veterinarian';
        document.getElementById('vet-form').reset();
        document.getElementById('vet-modal').style.display = 'block';
    }

    editVet(vetId) {
        this.isEditing = true;
        this.currentVet = this.vets.find(vet => vet.id === vetId);
        
        if (this.currentVet) {
            document.getElementById('modal-title').textContent = 'Edit Veterinarian';
            this.populateForm(this.currentVet);
            document.getElementById('vet-modal').style.display = 'block';
        }
    }

    populateForm(vet) {
        document.getElementById('vet-name').value = vet.name;
        document.getElementById('vet-email').value = vet.email;
        document.getElementById('vet-phone').value = vet.phone;
        document.getElementById('vet-specialty').value = vet.specialty;
        document.getElementById('vet-experience').value = vet.experience;
        document.getElementById('vet-status').value = vet.status;
        document.getElementById('vet-address').value = vet.address;
        document.getElementById('vet-bio').value = vet.bio;
        document.getElementById('vet-consultation-fee').value = vet.consultation_fee;
        document.getElementById('vet-availability').value = vet.availability;
    }

    saveVet() {
        const formData = new FormData(document.getElementById('vet-form'));
        const vetData = {
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            specialty: formData.get('specialty'),
            experience: parseInt(formData.get('experience')),
            status: formData.get('status'),
            address: formData.get('address'),
            bio: formData.get('bio'),
            consultation_fee: parseInt(formData.get('consultation_fee')),
            availability: formData.get('availability'),
            rating: 0,
            appointments: 0
        };

        if (this.isEditing && this.currentVet) {
            // Update existing vet
            Object.assign(this.currentVet, vetData);
        } else {
            // Add new vet
            vetData.id = this.vets.length + 1;
            this.vets.push(vetData);
        }

        this.closeVetModal();
        this.renderVets();
        this.updateStatistics();
        this.showNotification('Veterinarian saved successfully!', 'success');
    }

    deleteVet(vetId) {
        const vet = this.vets.find(v => v.id === vetId);
        if (vet) {
            document.getElementById('delete-vet-name').textContent = vet.name;
            document.getElementById('delete-modal').style.display = 'block';
            this.vetToDelete = vetId;
        }
    }

    confirmDeleteVet() {
        if (this.vetToDelete) {
            this.vets = this.vets.filter(vet => vet.id !== this.vetToDelete);
            this.closeDeleteModal();
            this.renderVets();
            this.updateStatistics();
            this.showNotification('Veterinarian deleted successfully!', 'success');
        }
    }

    closeVetModal() {
        document.getElementById('vet-modal').style.display = 'none';
        this.currentVet = null;
        this.isEditing = false;
    }

    closeDeleteModal() {
        document.getElementById('delete-modal').style.display = 'none';
        this.vetToDelete = null;
    }

    updateStatistics() {
        const totalVets = this.vets.length;
        const activeVets = this.vets.filter(vet => vet.status === 'Active').length;
        const totalAppointments = this.vets.reduce((sum, vet) => sum + vet.appointments, 0);
        const avgRating = this.vets.length > 0 
            ? (this.vets.reduce((sum, vet) => sum + vet.rating, 0) / this.vets.length).toFixed(1)
            : 0;

        document.getElementById('total-vets').textContent = totalVets;
        document.getElementById('active-vets').textContent = activeVets;
        document.getElementById('total-appointments').textContent = totalAppointments;
        document.getElementById('avg-rating').textContent = avgRating;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Global functions for modal operations
function openAddVetModal() {
    vetManager.openAddVetModal();
}

function closeVetModal() {
    vetManager.closeVetModal();
}

function closeDeleteModal() {
    vetManager.closeDeleteModal();
}

function confirmDeleteVet() {
    vetManager.confirmDeleteVet();
}

// Initialize when DOM is loaded
let vetManager;
document.addEventListener('DOMContentLoaded', () => {
    vetManager = new VetManager();
});

// Close modals when clicking outside
window.onclick = function(event) {
    const vetModal = document.getElementById('vet-modal');
    const deleteModal = document.getElementById('delete-modal');
    
    if (event.target === vetModal) {
        closeVetModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
} 