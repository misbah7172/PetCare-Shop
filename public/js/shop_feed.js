// Product details data
const productDetails = {
    "Premium Dog Food": {
        image: "images/dog_food.jpg",
        brand: "Royal Canin",
        price: "৳1,200",
        description: "High-quality dog food formulated with essential nutrients for your dog's health and vitality. Made with premium ingredients and balanced nutrition.",
        specs: [
            "Weight: 2kg",
            "Age: All ages",
            "Flavor: Chicken & Rice",
            "Suitable for: All dog breeds"
        ]
    },
    "Premium Cat Food": {
        image: "images/cat_food.jpg",
        brand: "Whiskas",
        price: "৳950",
        description: "Nutritious cat food made with real meat and essential vitamins. Perfect for maintaining your cat's health and energy levels.",
        specs: [
            "Weight: 1.5kg",
            "Age: Adult cats",
            "Flavor: Tuna & Salmon",
            "Suitable for: All cat breeds"
        ]
    }
    // Add more product details as needed
};

// Function to show product details
function showProductDetails(productName) {
    const modal = document.getElementById('productDetailsModal');
    const details = productDetails[productName];

    if (details) {
        document.getElementById('modalProductImage').src = details.image;
        document.getElementById('modalProductName').textContent = productName;
        document.getElementById('modalProductBrand').textContent = details.brand;
        document.getElementById('modalProductPrice').textContent = details.price;
        document.getElementById('modalProductDescription').textContent = details.description;
        
        const specsList = document.getElementById('modalProductSpecs');
        specsList.innerHTML = '';
        details.specs.forEach(spec => {
            const li = document.createElement('li');
            li.textContent = spec;
            specsList.appendChild(li);
        });

        modal.style.display = 'block';
    }
}

// Close modal when clicking the X
document.querySelector('.close-modal').onclick = function() {
    document.getElementById('productDetailsModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('productDetailsModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Quantity selector functions
function decreaseQuantity() {
    const input = document.getElementById('quantity');
    if (input.value > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function increaseQuantity() {
    const input = document.getElementById('quantity');
    if (input.value < 10) {
        input.value = parseInt(input.value) + 1;
    }
}

// Update all details buttons to use the new function
document.querySelectorAll('.details-btn').forEach(btn => {
    btn.onclick = function() {
        const productName = this.closest('.product-card').querySelector('h3').textContent;
        showProductDetails(productName);
    }
});

// Search and Filter functionality
const searchInput = document.querySelector('.search-bar input');
const categorySelect = document.getElementById('categorySelect');
const petTypeSelect = document.getElementById('petTypeSelect');
const priceRangeSelect = document.getElementById('priceRangeSelect');

// Add event listeners
searchInput.addEventListener('input', filterProducts);
categorySelect.addEventListener('change', filterProducts);
petTypeSelect.addEventListener('change', filterProducts);
priceRangeSelect.addEventListener('change', filterProducts);

function filterProducts() {
    const searchTerm = searchInput.value.toLowerCase();
    const category = categorySelect.value;
    const petType = petTypeSelect.value;
    const priceRange = priceRangeSelect.value;

    document.querySelectorAll('.product-card').forEach(card => {
        const productName = card.querySelector('h3').textContent.toLowerCase();
        const productBrand = card.querySelector('.brand').textContent.toLowerCase();
        const productPrice = parseInt(card.querySelector('.price').textContent.replace('৳', ''));
        
        let show = true;

        // Search term filter
        if (searchTerm && !productName.includes(searchTerm) && !productBrand.includes(searchTerm)) {
            show = false;
        }

        // Category filter
        if (category && card.dataset.category !== category) {
            show = false;
        }

        // Pet type filter
        if (petType && card.dataset.petType !== petType) {
            show = false;
        }

        // Price range filter
        if (priceRange) {
            const [min, max] = priceRange.split('-').map(Number);
            if (max) {
                if (productPrice < min || productPrice > max) {
                    show = false;
                }
            } else {
                if (productPrice < min) {
                    show = false;
                }
            }
        }

        // Show/hide the card
        card.style.display = show ? 'block' : 'none';
    });
}

// Add to cart functionality
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.onclick = function() {
        const productName = this.closest('.product-card').querySelector('h3').textContent;
        alert(`${productName} has been added to your cart!`);
    }
});

// Wishlist functionality
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.onclick = function() {
        const icon = this.querySelector('i');
        if (icon.classList.contains('far')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.style.color = '#e74c3c';
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            icon.style.color = '';
        }
    }
}); 