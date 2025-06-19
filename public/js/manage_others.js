// Manage Others Features - JavaScript
// This handles manual content management for the Others page

// Global variables
let currentTab = 'features';
let editingItem = null;
let itemsData = {
    features: [],
    quicklinks: [],
    info: []
};

// Sample data for demonstration (will be replaced with database later)
const sampleData = {
    features: [
        {
            id: 1,
            title: 'Pet Events',
            description: 'Stay updated with upcoming pet events, adoption drives, training sessions, and community meetups in your area.',
            link: 'pet_events.html',
            icon: 'fas fa-calendar-alt',
            status: 'active',
            display_order: 1
        },
        {
            id: 2,
            title: 'Pet Training',
            description: 'Access professional training resources, tips, and guides to help train your pet effectively and build a strong bond.',
            link: 'pet_training.html',
            icon: 'fas fa-graduation-cap',
            status: 'active',
            display_order: 2
        },
        {
            id: 3,
            title: 'Pet Health Tips',
            description: 'Get expert advice on pet nutrition, exercise, grooming, and preventive healthcare to keep your pet healthy and happy.',
            link: 'pet_health.html',
            icon: 'fas fa-heartbeat',
            status: 'active',
            display_order: 3
        },
        {
            id: 4,
            title: 'Pet Services Map',
            description: 'Find nearby pet services including groomers, trainers, pet sitters, and emergency veterinary clinics on an interactive map.',
            link: 'pet_services_map.html',
            icon: 'fas fa-map-marker-alt',
            status: 'active',
            display_order: 4
        },
        {
            id: 5,
            title: 'Pet Care Blog',
            description: 'Read informative articles, expert advice, and heartwarming stories about pet care, adoption, and the human-animal bond.',
            link: 'pet_blog.html',
            icon: 'fas fa-book',
            status: 'active',
            display_order: 5
        },
        {
            id: 6,
            title: 'Pet Contests',
            description: 'Participate in monthly pet photo contests, talent shows, and competitions to showcase your pet\'s unique personality.',
            link: 'pet_contests.html',
            icon: 'fas fa-trophy',
            status: 'active',
            display_order: 6
        }
    ],
    quicklinks: [
        {
            id: 1,
            title: 'Terms of Service',
            description: 'Read our terms and conditions',
            link: 'terms_of_service.html',
            icon: 'fas fa-file-alt',
            status: 'active',
            display_order: 1
        },
        {
            id: 2,
            title: 'Privacy Policy',
            description: 'View our privacy policy',
            link: 'privacy_policy.html',
            icon: 'fas fa-shield-alt',
            status: 'active',
            display_order: 2
        },
        {
            id: 3,
            title: 'FAQ',
            description: 'Frequently asked questions',
            link: 'faq.html',
            icon: 'fas fa-question-circle',
            status: 'active',
            display_order: 3
        },
        {
            id: 4,
            title: 'Contact Us',
            description: 'Get in touch with us',
            link: 'contact_us.html',
            icon: 'fas fa-envelope',
            status: 'active',
            display_order: 4
        },
        {
            id: 5,
            title: 'About Us',
            description: 'Learn more about PawConnect',
            link: 'about_us.html',
            icon: 'fas fa-info-circle',
            status: 'active',
            display_order: 5
        },
        {
            id: 6,
            title: 'Report Issue',
            description: 'Report bugs or issues',
            link: 'report_issue.html',
            icon: 'fas fa-bug',
            status: 'active',
            display_order: 6
        }
    ],
    info: [
        {
            id: 1,
            title: 'Mobile App',
            description: 'Download our mobile app for on-the-go access to all PawConnect features. Available on iOS and Android.',
            icon: 'fas fa-mobile-alt',
            status: 'active',
            display_order: 1
        },
        {
            id: 2,
            title: 'Notifications',
            description: 'Enable push notifications to stay updated with new pets, events, and important announcements.',
            icon: 'fas fa-bell',
            status: 'active',
            display_order: 2
        },
        {
            id: 3,
            title: 'Language Support',
            description: 'PawConnect is available in multiple languages. Change your language preference in settings.',
            icon: 'fas fa-language',
            status: 'active',
            display_order: 3
        },
        {
            id: 4,
            title: 'Downloads',
            description: 'Access downloadable resources including pet care guides, training manuals, and health checklists.',
            icon: 'fas fa-download',
            status: 'active',
            display_order: 4
        },
        {
            id: 5,
            title: 'Partnerships',
            description: 'Learn about our partnerships with local shelters, veterinary clinics, and pet service providers.',
            icon: 'fas fa-users',
            status: 'active',
            display_order: 5
        },
        {
            id: 6,
            title: 'Reviews',
            description: 'Read reviews from other pet owners and share your own experiences with PawConnect services.',
            icon: 'fas fa-star',
            status: 'active',
            display_order: 6
        }
    ]
};

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    loadPageSettings();
});

// Load data for all sections
function loadData() {
    itemsData = {
        features: [...sampleData.features],
        quicklinks: [...sampleData.quicklinks],
        info: [...sampleData.info]
    };
    
    displayItems('features');
    displayItems('quicklinks');
    displayItems('info');
}

// Show tab content
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    currentTab = tabName;
}

// Display items for a specific section
function displayItems(section) {
    const grid = document.getElementById(`${section}-grid`);
    const items = itemsData[section];
    
    if (items.length === 0) {
        grid.innerHTML = '<p style="text-align: center; color: #718096; padding: 40px;">No items found. Click "Add" to create your first item.</p>';
        return;
    }
    
    // Sort by display order
    const sortedItems = items.sort((a, b) => a.display_order - b.display_order);
    
    grid.innerHTML = sortedItems.map(item => `
        <div class="item-card">
            <div class="item-header">
                <div class="item-title">
                    <i class="${item.icon}"></i> ${item.title}
                </div>
                <div class="item-actions">
                    <button class="edit-btn" onclick="editItem('${section}', ${item.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="delete-btn" onclick="deleteItem('${section}', ${item.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
            <div class="item-content">${item.description}</div>
            ${item.link ? `<div class="item-meta"><strong>Link:</strong> ${item.link}</div>` : ''}
            <div class="item-meta">
                <span>Order: ${item.display_order}</span>
                <span class="item-status status-${item.status}">${item.status}</span>
            </div>
        </div>
    `).join('');
}

// Open modal for adding/editing items
function openModal(type, itemId = null) {
    const modal = document.getElementById('itemModal');
    const modalTitle = document.getElementById('modal-title');
    const form = document.getElementById('itemForm');
    
    // Clear form
    form.reset();
    
    if (itemId) {
        // Editing existing item
        editingItem = { type, id: itemId };
        const item = itemsData[type].find(i => i.id === itemId);
        if (item) {
            modalTitle.textContent = `Edit ${type.charAt(0).toUpperCase() + type.slice(1)}`;
            document.getElementById('item-title').value = item.title;
            document.getElementById('item-description').value = item.description;
            document.getElementById('item-link').value = item.link || '';
            document.getElementById('item-icon').value = item.icon;
            document.getElementById('item-status').value = item.status;
            document.getElementById('item-order').value = item.display_order;
        }
    } else {
        // Adding new item
        editingItem = null;
        modalTitle.textContent = `Add New ${type.charAt(0).toUpperCase() + type.slice(1)}`;
        document.getElementById('item-order').value = itemsData[type].length + 1;
    }
    
    modal.style.display = 'block';
}

// Close modal
function closeModal() {
    document.getElementById('itemModal').style.display = 'none';
    editingItem = null;
}

// Save item (add or edit)
function saveItem(event) {
    event.preventDefault();
    
    const formData = {
        title: document.getElementById('item-title').value,
        description: document.getElementById('item-description').value,
        link: document.getElementById('item-link').value,
        icon: document.getElementById('item-icon').value,
        status: document.getElementById('item-status').value,
        display_order: parseInt(document.getElementById('item-order').value)
    };
    
    if (editingItem) {
        // Update existing item
        const itemIndex = itemsData[currentTab].findIndex(i => i.id === editingItem.id);
        if (itemIndex !== -1) {
            itemsData[currentTab][itemIndex] = { ...itemsData[currentTab][itemIndex], ...formData };
            showSuccess('Item updated successfully!');
        }
    } else {
        // Add new item
        const newItem = {
            id: Date.now(), // Simple ID generation
            ...formData
        };
        itemsData[currentTab].push(newItem);
        showSuccess('Item added successfully!');
    }
    
    displayItems(currentTab);
    closeModal();
}

// Edit item
function editItem(type, itemId) {
    openModal(type, itemId);
}

// Delete item
function deleteItem(type, itemId) {
    if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
        itemsData[type] = itemsData[type].filter(item => item.id !== itemId);
        displayItems(type);
        showSuccess('Item deleted successfully!');
    }
}

// Load page settings
function loadPageSettings() {
    // For now, use default values (will be loaded from database later)
    document.getElementById('page-title').value = 'Others';
    document.getElementById('page-subtitle').value = 'Discover additional features and resources for your pet care journey';
    document.getElementById('header-color').value = '#667eea';
    document.getElementById('enable-notifications').value = 'true';
}

// Save page settings
function savePageSettings() {
    const settings = {
        page_title: document.getElementById('page-title').value,
        page_subtitle: document.getElementById('page-subtitle').value,
        header_color: document.getElementById('header-color').value,
        enable_notifications: document.getElementById('enable-notifications').value
    };
    
    // For now, just show success message (will save to database later)
    showSuccess('Page settings saved successfully!');
    console.log('Settings to save:', settings);
}

// Show success message
function showSuccess(message) {
    const successDiv = document.getElementById('success-message');
    successDiv.textContent = message;
    successDiv.style.display = 'block';
    setTimeout(() => {
        successDiv.style.display = 'none';
    }, 3000);
}

// Show error message
function showError(message) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('itemModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Form submission
document.getElementById('itemForm').addEventListener('submit', saveItem);

// Export data for database (will be used later)
function exportData() {
    return {
        items: itemsData,
        settings: {
            page_title: document.getElementById('page-title').value,
            page_subtitle: document.getElementById('page-subtitle').value,
            header_color: document.getElementById('header-color').value,
            enable_notifications: document.getElementById('enable-notifications').value
        }
    };
} 