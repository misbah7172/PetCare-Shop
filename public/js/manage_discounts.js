// Discount Management System
class DiscountManager {
    constructor() {
        this.discounts = [];
        this.currentDiscount = null;
        this.isEditing = false;
        
        this.initializeEventListeners();
        this.loadDiscounts();
        this.updateStatistics();
    }

    initializeEventListeners() {
        // Search functionality
        document.getElementById('discount-search').addEventListener('input', (e) => {
            this.filterDiscounts();
        });

        // Filter functionality
        document.getElementById('discount-type-filter').addEventListener('change', () => {
            this.filterDiscounts();
        });

        document.getElementById('status-filter').addEventListener('change', () => {
            this.filterDiscounts();
        });

        // Form submission
        document.getElementById('discount-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveDiscount();
        });
    }

    loadDiscounts() {
        // Sample data - in real app, this would come from database
        this.discounts = [
            {
                id: 1,
                name: "Summer Sale 20% Off",
                code: "SUMMER20",
                type: "Percentage",
                value: 20,
                min_amount: 1000,
                max_amount: 500,
                start_date: "2024-06-01T00:00",
                end_date: "2024-08-31T23:59",
                usage_limit: 1,
                total_limit: 1000,
                categories: ["food", "toys"],
                description: "Get 20% off on pet food and toys this summer!",
                status: "Active",
                times_used: 156,
                total_savings: 45000
            },
            {
                id: 2,
                name: "New Customer Discount",
                code: "NEWCUST",
                type: "Fixed",
                value: 500,
                min_amount: 2000,
                max_amount: 500,
                start_date: "2024-01-01T00:00",
                end_date: "2024-12-31T23:59",
                usage_limit: 1,
                total_limit: 500,
                categories: ["all"],
                description: "৳500 off for new customers on first purchase",
                status: "Active",
                times_used: 89,
                total_savings: 44500
            },
            {
                id: 3,
                name: "Free Shipping Weekend",
                code: "FREESHIP",
                type: "Free Shipping",
                value: 0,
                min_amount: 1500,
                max_amount: 0,
                start_date: "2024-07-15T00:00",
                end_date: "2024-07-16T23:59",
                usage_limit: 1,
                total_limit: 200,
                categories: ["all"],
                description: "Free shipping on all orders above ৳1500 this weekend",
                status: "Scheduled",
                times_used: 0,
                total_savings: 0
            }
        ];

        this.renderDiscounts();
    }

    renderDiscounts() {
        const grid = document.getElementById('discounts-grid');
        grid.innerHTML = '';

        this.discounts.forEach(discount => {
            const card = this.createDiscountCard(discount);
            grid.appendChild(card);
        });
    }

    createDiscountCard(discount) {
        const card = document.createElement('div');
        card.className = 'discount-card';
        
        const statusClass = discount.status.toLowerCase().replace(' ', '-');
        const isExpired = new Date(discount.end_date) < new Date();
        const isActive = discount.status === 'Active' && !isExpired;
        
        card.innerHTML = `
            <div class="discount-header">
                <div class="discount-info">
                    <h3>${discount.name}</h3>
                    <p class="discount-code">${discount.code ? `Code: ${discount.code}` : 'No code required'}</p>
                    <span class="status-badge ${statusClass} ${isExpired ? 'expired' : ''}">${isExpired ? 'Expired' : discount.status}</span>
                </div>
                <div class="discount-value">
                    <span class="value-display">
                        ${this.formatDiscountValue(discount)}
                    </span>
                </div>
            </div>
            <div class="discount-details">
                <div class="detail-item">
                    <i class="fas fa-calendar"></i>
                    <span>${this.formatDateRange(discount.start_date, discount.end_date)}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-tag"></i>
                    <span>${discount.type}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Min: ৳${discount.min_amount || 0}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-users"></i>
                    <span>Used ${discount.times_used} times</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Total savings: ৳${discount.total_savings.toLocaleString()}</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-list"></i>
                    <span>Categories: ${this.formatCategories(discount.categories)}</span>
                </div>
            </div>
            <div class="discount-description">
                <p>${discount.description}</p>
            </div>
            <div class="discount-actions">
                <button class="btn-secondary" onclick="discountManager.editDiscount(${discount.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn-danger" onclick="discountManager.deleteDiscount(${discount.id})">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        `;

        return card;
    }

    formatDiscountValue(discount) {
        switch (discount.type) {
            case 'Percentage':
                return `${discount.value}% OFF`;
            case 'Fixed':
                return `৳${discount.value} OFF`;
            case 'Buy One Get One':
                return 'BOGO';
            case 'Free Shipping':
                return 'FREE SHIPPING';
            default:
                return discount.value;
        }
    }

    formatDateRange(startDate, endDate) {
        const start = new Date(startDate).toLocaleDateString();
        const end = new Date(endDate).toLocaleDateString();
        return `${start} - ${end}`;
    }

    formatCategories(categories) {
        if (categories.includes('all')) return 'All Products';
        return categories.map(cat => cat.charAt(0).toUpperCase() + cat.slice(1)).join(', ');
    }

    filterDiscounts() {
        const searchTerm = document.getElementById('discount-search').value.toLowerCase();
        const typeFilter = document.getElementById('discount-type-filter').value;
        const statusFilter = document.getElementById('status-filter').value;

        const filteredDiscounts = this.discounts.filter(discount => {
            const matchesSearch = discount.name.toLowerCase().includes(searchTerm) ||
                                (discount.code && discount.code.toLowerCase().includes(searchTerm));
            
            const matchesType = !typeFilter || discount.type === typeFilter;
            const matchesStatus = !statusFilter || discount.status === statusFilter;

            return matchesSearch && matchesType && matchesStatus;
        });

        this.renderFilteredDiscounts(filteredDiscounts);
    }

    renderFilteredDiscounts(discounts) {
        const grid = document.getElementById('discounts-grid');
        grid.innerHTML = '';

        discounts.forEach(discount => {
            const card = this.createDiscountCard(discount);
            grid.appendChild(card);
        });
    }

    openAddDiscountModal() {
        this.isEditing = false;
        this.currentDiscount = null;
        document.getElementById('modal-title').textContent = 'Add New Discount';
        document.getElementById('discount-form').reset();
        document.getElementById('value-symbol').textContent = '%';
        document.getElementById('discount-modal').style.display = 'block';
    }

    editDiscount(discountId) {
        this.isEditing = true;
        this.currentDiscount = this.discounts.find(discount => discount.id === discountId);
        
        if (this.currentDiscount) {
            document.getElementById('modal-title').textContent = 'Edit Discount';
            this.populateForm(this.currentDiscount);
            document.getElementById('discount-modal').style.display = 'block';
        }
    }

    populateForm(discount) {
        document.getElementById('discount-name').value = discount.name;
        document.getElementById('discount-code').value = discount.code || '';
        document.getElementById('discount-type').value = discount.type;
        document.getElementById('discount-value').value = discount.value;
        document.getElementById('discount-min-amount').value = discount.min_amount || '';
        document.getElementById('discount-max-amount').value = discount.max_amount || '';
        document.getElementById('discount-start-date').value = discount.start_date;
        document.getElementById('discount-end-date').value = discount.end_date;
        document.getElementById('discount-usage-limit').value = discount.usage_limit || '';
        document.getElementById('discount-total-limit').value = discount.total_limit || '';
        document.getElementById('discount-description').value = discount.description;
        document.getElementById('discount-status').value = discount.status;

        // Set categories
        const categoryCheckboxes = document.querySelectorAll('input[name="categories[]"]');
        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked = discount.categories.includes(checkbox.value);
        });

        this.updateDiscountFields();
    }

    saveDiscount() {
        const formData = new FormData(document.getElementById('discount-form'));
        const categories = Array.from(document.querySelectorAll('input[name="categories[]"]:checked'))
            .map(checkbox => checkbox.value);

        const discountData = {
            name: formData.get('name'),
            code: formData.get('code') || null,
            type: formData.get('type'),
            value: parseFloat(formData.get('value')),
            min_amount: parseFloat(formData.get('min_amount')) || 0,
            max_amount: parseFloat(formData.get('max_amount')) || 0,
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date'),
            usage_limit: parseInt(formData.get('usage_limit')) || 0,
            total_limit: parseInt(formData.get('total_limit')) || 0,
            categories: categories,
            description: formData.get('description'),
            status: formData.get('status'),
            times_used: 0,
            total_savings: 0
        };

        if (this.isEditing && this.currentDiscount) {
            // Update existing discount
            Object.assign(this.currentDiscount, discountData);
        } else {
            // Add new discount
            discountData.id = this.discounts.length + 1;
            this.discounts.push(discountData);
        }

        this.closeDiscountModal();
        this.renderDiscounts();
        this.updateStatistics();
        this.showNotification('Discount saved successfully!', 'success');
    }

    deleteDiscount(discountId) {
        const discount = this.discounts.find(d => d.id === discountId);
        if (discount) {
            document.getElementById('delete-discount-name').textContent = discount.name;
            document.getElementById('delete-modal').style.display = 'block';
            this.discountToDelete = discountId;
        }
    }

    confirmDeleteDiscount() {
        if (this.discountToDelete) {
            this.discounts = this.discounts.filter(discount => discount.id !== this.discountToDelete);
            this.closeDeleteModal();
            this.renderDiscounts();
            this.updateStatistics();
            this.showNotification('Discount deleted successfully!', 'success');
        }
    }

    closeDiscountModal() {
        document.getElementById('discount-modal').style.display = 'none';
        this.currentDiscount = null;
        this.isEditing = false;
    }

    closeDeleteModal() {
        document.getElementById('delete-modal').style.display = 'none';
        this.discountToDelete = null;
    }

    updateStatistics() {
        const totalDiscounts = this.discounts.length;
        const activeDiscounts = this.discounts.filter(discount => 
            discount.status === 'Active' && new Date(discount.end_date) > new Date()
        ).length;
        const totalSavings = this.discounts.reduce((sum, discount) => sum + discount.total_savings, 0);
        const totalUsage = this.discounts.reduce((sum, discount) => sum + discount.times_used, 0);

        document.getElementById('total-discounts').textContent = totalDiscounts;
        document.getElementById('active-discounts').textContent = activeDiscounts;
        document.getElementById('total-savings').textContent = `৳${totalSavings.toLocaleString()}`;
        document.getElementById('total-usage').textContent = totalUsage;
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
function openAddDiscountModal() {
    discountManager.openAddDiscountModal();
}

function closeDiscountModal() {
    discountManager.closeDiscountModal();
}

function closeDeleteModal() {
    discountManager.closeDeleteModal();
}

function confirmDeleteDiscount() {
    discountManager.confirmDeleteDiscount();
}

function updateDiscountFields() {
    const type = document.getElementById('discount-type').value;
    const valueSymbol = document.getElementById('value-symbol');
    const valueInput = document.getElementById('discount-value');
    const maxAmountGroup = document.querySelector('.form-row:nth-child(3)');

    switch (type) {
        case 'Percentage':
            valueSymbol.textContent = '%';
            valueInput.placeholder = 'Enter percentage (e.g., 20)';
            maxAmountGroup.style.display = 'flex';
            break;
        case 'Fixed':
            valueSymbol.textContent = '৳';
            valueInput.placeholder = 'Enter amount (e.g., 500)';
            maxAmountGroup.style.display = 'flex';
            break;
        case 'Buy One Get One':
            valueSymbol.textContent = '';
            valueInput.placeholder = 'Not applicable';
            valueInput.value = '';
            valueInput.disabled = true;
            maxAmountGroup.style.display = 'none';
            break;
        case 'Free Shipping':
            valueSymbol.textContent = '';
            valueInput.placeholder = 'Not applicable';
            valueInput.value = '';
            valueInput.disabled = true;
            maxAmountGroup.style.display = 'none';
            break;
        default:
            valueSymbol.textContent = '%';
            valueInput.disabled = false;
            maxAmountGroup.style.display = 'flex';
    }
}

// Initialize when DOM is loaded
let discountManager;
document.addEventListener('DOMContentLoaded', () => {
    discountManager = new DiscountManager();
});

// Close modals when clicking outside
window.onclick = function(event) {
    const discountModal = document.getElementById('discount-modal');
    const deleteModal = document.getElementById('delete-modal');
    
    if (event.target === discountModal) {
        closeDiscountModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
} 