document.addEventListener('DOMContentLoaded', function() {
    // Add to Cart Button
    const addToCartBtn = document.querySelector('.btn-primary');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const quantity = document.getElementById('quantity').value;
            const productId = new URLSearchParams(window.location.search).get('id');
            window.location.href = `add_to_cart.php?product_id=${productId}&quantity=${quantity}`;
        });
    }
    // Read More Functionality
    const readMoreBtn = document.getElementById('read-more-btn');
    const readLessBtn = document.getElementById('read-less-btn');
    const shortDescription = document.getElementById('short-description');
    const fullDescription = document.getElementById('full-description');

    if (readMoreBtn && readLessBtn && shortDescription && fullDescription) {
        readMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            shortDescription.style.display = 'none';
            fullDescription.style.display = 'block';
        });

        readLessBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fullDescription.style.display = 'none';
            shortDescription.style.display = 'block';
        });
    }
    // Image Gallery
    const mainImage = document.getElementById('main-image');
    const thumbnails = document.querySelectorAll('.thumbnail');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            // Update main image
            mainImage.src = this.src;
            mainImage.classList.add('fade');
            
            // Update active state
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Remove fade class after animation
            setTimeout(() => {
                mainImage.classList.remove('fade');
            }, 300);
        });
    });

    // Quantity Input
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        const maxStock = parseInt(quantityInput.getAttribute('max'));
        
        quantityInput.addEventListener('change', function() {
            let value = parseInt(this.value);
            if (value > maxStock) {
                this.value = maxStock;
                showToast('Maximum available stock is ' + maxStock);
            } else if (value < 1) {
                this.value = 1;
            }
        });
    }

    // Toast Notification
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }, 100);
    }
});
