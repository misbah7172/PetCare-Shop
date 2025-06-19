// PawConnect API Manager
// This file handles all API calls and data management

class PawConnectAPI {
    constructor() {
        this.baseURL = '../src/api/data.php';
    }

    // Generic API call method
    async apiCall(endpoint, method = 'GET', data = null) {
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

            const response = await fetch(`${this.baseURL}?endpoint=${endpoint}`, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API call failed for ${endpoint}:`, error);
            throw error;
        }
    }

    // Get all pets
    async getPets() {
        return await this.apiCall('pets');
    }

    // Get adoption listings
    async getAdoptions() {
        return await this.apiCall('adoptions');
    }

    // Get shop products
    async getProducts() {
        return await this.apiCall('products');
    }

    // Get vets
    async getVets() {
        return await this.apiCall('vets');
    }

    // Get community posts
    async getCommunityPosts() {
        return await this.apiCall('community_posts');
    }
}

// Create global API instance
const pawAPI = new PawConnectAPI();

// Data display functions
function displayPets(pets, containerId) {
    // Handle multiple possible container IDs
    const possibleIds = [containerId, 'pets-container', 'gallery-content'];
    let container = null;
    
    for (const id of possibleIds) {
        container = document.getElementById(id);
        if (container) break;
    }
    
    if (!container) return;

    if (!pets || pets.length === 0) {
        container.innerHTML = '<p>No pets available at the moment.</p>';
        return;
    }

    container.innerHTML = pets.map(pet => `
        <div class="pet-card">
            <img src="${pet.photo_url || 'assets/allpet.png'}" alt="${pet.name}" class="pet-image">
            <div class="pet-info">
                <h3>${pet.name}</h3>
                <p><strong>Type:</strong> ${pet.type}</p>
                <p><strong>Breed:</strong> ${pet.breed || 'Mixed'}</p>
                <p><strong>Age:</strong> ${pet.age} ${pet.age_unit}</p>
                <p><strong>Gender:</strong> ${pet.gender}</p>
                <p><strong>Owner:</strong> ${pet.owner_name}</p>
                <p class="pet-description">${pet.description || 'No description available'}</p>
                ${pet.adoption_status ? `<span class="adoption-status ${pet.adoption_status}">${pet.adoption_status}</span>` : ''}
            </div>
        </div>
    `).join('');
}

function displayAdoptions(adoptions, containerId) {
    // Handle multiple possible container IDs
    const possibleIds = [containerId, 'adoptions-container', 'content'];
    let container = null;
    
    for (const id of possibleIds) {
        container = document.getElementById(id);
        if (container) break;
    }
    
    if (!container) return;

    if (!adoptions || adoptions.length === 0) {
        container.innerHTML = '<div class="error">No pets available for adoption at the moment.</div>';
        return;
    }

    container.innerHTML = adoptions.map(adoption => `
        <div class="adoption-card">
            <div class="pet-image-container">
                <img src="${adoption.photo_url || 'assets/allpet.png'}" alt="${adoption.pet_name}" class="pet-image">
                <div class="adoption-status-badge">${adoption.status || 'Available'}</div>
            </div>
            <div class="adoption-info">
                <h3>${adoption.pet_name}</h3>
                <div class="pet-details">
                    <span class="detail-item"><i class="fas fa-paw"></i> ${adoption.type}</span>
                    <span class="detail-item"><i class="fas fa-dna"></i> ${adoption.breed || 'Mixed'}</span>
                    <span class="detail-item"><i class="fas fa-birthday-cake"></i> ${adoption.age} ${adoption.age_unit}</span>
                    <span class="detail-item"><i class="fas fa-venus-mars"></i> ${adoption.gender}</span>
                </div>
                <p class="listed-by"><i class="fas fa-user"></i> Listed by: ${adoption.listed_by_name}</p>
                <div class="pet-description">
                    <p>${adoption.pet_description || 'No description available'}</p>
                    ${adoption.description ? `<p class="adoption-notes">${adoption.description}</p>` : ''}
                </div>
                <div class="pet-actions">
                    <button class="btn-adopt" onclick="showAdoptionForm(${adoption.id})">
                        <i class="fas fa-heart"></i> Apply for Adoption
                    </button>
                    <button class="btn-details" onclick="showAdoptionDetails(${adoption.id})">
                        <i class="fas fa-info-circle"></i> More Details
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function displayProducts(products, containerId) {
    // Handle multiple possible container IDs
    const possibleIds = [containerId, 'products-container', 'product-grid'];
    let container = null;
    
    for (const id of possibleIds) {
        container = document.getElementById(id);
        if (container) break;
    }
    
    if (!container) return;

    if (!products || products.length === 0) {
        container.innerHTML = '<div class="error">No products available at the moment.</div>';
        return;
    }

    container.innerHTML = products.map(product => `
        <div class="product-card" data-category="${product.category}">
            <div class="product-image-container">
                <img src="${product.image_url || 'assets/cat_food.jpg'}" alt="${product.name}" class="product-image">
                <button class="wishlist-btn" onclick="toggleWishlist(${product.id})">
                    <i class="far fa-heart"></i>
                </button>
                ${product.stock <= 5 ? '<div class="stock-badge low-stock">Low Stock</div>' : ''}
            </div>
            <div class="product-info">
                <h3>${product.name}</h3>
                <div class="product-meta">
                    <span class="category-tag">${product.category.charAt(0).toUpperCase() + product.category.slice(1)}</span>
                    <span class="stock-info">${product.stock} in stock</span>
                </div>
                <p class="product-description">${product.description || 'Premium quality product for your beloved pet.'}</p>
                <div class="price-section">
                    <span class="price">৳${parseFloat(product.price).toFixed(2)}</span>
                </div>
                <div class="product-actions">
                    <button class="btn-cart" onclick="addToCart(${product.id})">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="btn-details" onclick="showProductDetails(${product.id})">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function displayVets(vets, containerId) {
    // Handle multiple possible container IDs
    const possibleIds = [containerId, 'vets-container', 'vet-profiles'];
    let container = null;
    
    for (const id of possibleIds) {
        container = document.getElementById(id);
        if (container) break;
    }
    
    if (!container) return;

    if (!vets || vets.length === 0) {
        container.innerHTML = '<div class="error">No veterinarians available at the moment.</div>';
        return;
    }

    container.innerHTML = vets.map(vet => `
        <div class="vet-card">
            <div class="vet-header">
                <div class="vet-avatar">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="vet-title">
                    <h3>Dr. ${vet.name}</h3>
                    <p class="specialization">${vet.specialization}</p>
                </div>
            </div>
            <div class="vet-info">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>${vet.email}</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>${vet.contact_info}</span>
                    </div>
                </div>
                <div class="vet-stats">
                    <div class="stat-item">
                        <i class="fas fa-star"></i>
                        <span>4.8/5 Rating</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Available</span>
                    </div>
                </div>
                <div class="vet-actions">
                    <button class="btn-appointment" onclick="bookAppointment(${vet.id})">
                        <i class="fas fa-calendar-plus"></i> Book Appointment
                    </button>
                    <button class="btn-contact" onclick="contactVet(${vet.id})">
                        <i class="fas fa-phone"></i> Contact
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function displayCommunityPosts(posts, containerId) {
    // Handle multiple possible container IDs
    const possibleIds = [containerId, 'community-posts-container', 'feed-content'];
    let container = null;
    
    for (const id of possibleIds) {
        container = document.getElementById(id);
        if (container) break;
    }
    
    if (!container) return;

    if (!posts || posts.length === 0) {
        container.innerHTML = '<p>No community posts available.</p>';
        return;
    }

    container.innerHTML = posts.map(post => `
        <div class="community-post post-card">
            <div class="post-header">
                <strong>${post.first_name} ${post.last_name}</strong> (@${post.username})
                ${post.group_name ? `<span class="group-name">in ${post.group_name}</span>` : ''}
                <span class="post-date">${new Date(post.created_at).toLocaleDateString()}</span>
            </div>
            <div class="post-content">
                <p>${post.content}</p>
                ${post.media_url ? `<img src="${post.media_url}" alt="Post media" class="post-media">` : ''}
            </div>
            <div class="post-actions">
                <button class="btn-like" onclick="likePost(${post.id})">👍 Like</button>
                <button class="btn-comment" onclick="showComments(${post.id})">💬 Comment</button>
            </div>
        </div>
    `).join('');
}

// Page-specific data loading functions
async function loadPetAdoptionData() {
    try {
        // Hide loading indicator
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        const adoptions = await pawAPI.getAdoptions();
        displayAdoptions(adoptions, 'adoptions-container');
    } catch (error) {
        console.error('Failed to load adoption data:', error);
        const container = document.getElementById('adoptions-container');
        if (container) {
            container.innerHTML = '<p class="error">Failed to load adoption data. Please try again later.</p>';
        }
        // Hide loading indicator even on error
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
    }
}

async function loadShopData() {
    try {
        // Hide loading indicator
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        const products = await pawAPI.getProducts();
        displayProducts(products, 'products-container');
    } catch (error) {
        console.error('Failed to load shop data:', error);
        const container = document.getElementById('products-container');
        if (container) {
            container.innerHTML = '<p class="error">Failed to load products. Please try again later.</p>';
        }
        // Hide loading indicator even on error
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
    }
}

async function loadVetData() {
    try {
        // Hide loading indicator
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        const vets = await pawAPI.getVets();
        displayVets(vets, 'vets-container');
    } catch (error) {
        console.error('Failed to load vet data:', error);
        const container = document.getElementById('vets-container');
        if (container) {
            container.innerHTML = '<p class="error">Failed to load veterinarian data. Please try again later.</p>';
        }
        // Hide loading indicator even on error
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'none';
    }
}

async function loadCommunityData() {
    try {
        const posts = await pawAPI.getCommunityPosts();
        displayCommunityPosts(posts, 'community-posts-container');
    } catch (error) {
        console.error('Failed to load community data:', error);
        const container = document.getElementById('community-posts-container') || document.getElementById('feed-content');
        if (container) {
            container.innerHTML = '<p class="error">Failed to load community posts. Please try again later.</p>';
        }
    }
}

async function loadPetCornerData() {
    try {
        const pets = await pawAPI.getPets();
        displayPets(pets, 'pets-container');
    } catch (error) {
        console.error('Failed to load pet data:', error);
        const container = document.getElementById('pets-container') || document.getElementById('gallery-content');
        if (container) {
            container.innerHTML = '<p class="error">Failed to load pet data. Please try again later.</p>';
        }
    }
}

// Utility functions for actions
function showAdoptionForm(adoptionId) {
    alert(`Adoption form for ID: ${adoptionId} - This feature will be implemented next!`);
}

function addToCart(productId) {
    alert(`Product ${productId} added to cart - Cart feature will be implemented next!`);
}

function bookAppointment(vetId) {
    alert(`Booking appointment with vet ID: ${vetId} - Appointment booking will be implemented next!`);
}

function likePost(postId) {
    alert(`Liked post ${postId} - Like system will be implemented next!`);
}

function showComments(postId) {
    alert(`Showing comments for post ${postId} - Comment system will be implemented next!`);
}

// Auto-load data based on page
document.addEventListener('DOMContentLoaded', function() {
    // Detect which page we're on and load appropriate data
    const path = window.location.pathname;
    
    if (path.includes('pet_adoption_feed.html')) {
        loadPetAdoptionData();
    } else if (path.includes('shop_feed.html')) {
        loadShopData();
    } else if (path.includes('vet_appointment.html')) {
        loadVetData();
    } else if (path.includes('pet_community.html')) {
        loadCommunityData();
    } else if (path.includes('pet_corner.html')) {
        loadPetCornerData();
    }
});

// Export for use in other scripts
window.PawConnectAPI = PawConnectAPI;
window.pawAPI = pawAPI;
