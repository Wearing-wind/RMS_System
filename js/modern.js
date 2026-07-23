// QR Restaurant - Modern JavaScript
// Advanced Features

// ==================== CUSTOMIZATION MODAL ====================
let currentCustomItem = {
    id: null,
    name: '',
    price: 0,
    description: '',
    preparationTime: 15,
    quantity: 1
};

function openCustomModal(itemId, itemName, itemPrice, description, prepTime) {
    currentCustomItem = {
        id: itemId,
        name: itemName,
        price: parseFloat(itemPrice),
        description: description || '',
        preparationTime: prepTime || 15,
        quantity: 1
    };
    
    // Update modal content
    document.getElementById('customModalTitle').textContent = itemName;
    document.getElementById('customModalDesc').textContent = description || '';
    document.getElementById('customModalPrice').textContent = formatPrice(itemPrice);
    document.getElementById('customModalQty').textContent = '1';
    document.getElementById('customModalTotal').textContent = formatPrice(itemPrice);
    
    // Reset customization options
    document.querySelector('input[name="spice_level"][value="mild"]').checked = true;
    document.getElementById('extraCheese').checked = false;
    document.getElementById('extraFries').checked = false;
    document.getElementById('extraSauce').checked = false;
    
    // Show modal
    document.getElementById('customModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCustomModal() {
    document.getElementById('customModal').classList.remove('active');
    document.body.style.overflow = '';
}

function customModalChangeQty(change) {
    currentCustomItem.quantity += change;
    if (currentCustomItem.quantity < 1) currentCustomItem.quantity = 1;
    if (currentCustomItem.quantity > 10) currentCustomItem.quantity = 10;
    
    document.getElementById('customModalQty').textContent = currentCustomItem.quantity;
    updateCustomModalTotal();
}

function updateCustomModalTotal() {
    let total = currentCustomItem.price * currentCustomItem.quantity;
    
    // Add extra prices
    if (document.getElementById('extraCheese').checked) {
        total += parseInt(document.getElementById('extraCheese').dataset.price) * currentCustomItem.quantity;
    }
    if (document.getElementById('extraFries').checked) {
        total += parseInt(document.getElementById('extraFries').dataset.price) * currentCustomItem.quantity;
    }
    if (document.getElementById('extraSauce').checked) {
        total += parseInt(document.getElementById('extraSauce').dataset.price) * currentCustomItem.quantity;
    }
    
    document.getElementById('customModalTotal').textContent = formatPrice(total);
}

function addCustomToCart() {
    // Get customization options
    const spiceLevel = document.querySelector('input[name="spice_level"]:checked').value;
    const extras = [];
    
    if (document.getElementById('extraCheese').checked) {
        extras.push({ name: 'Extra Cheese', price: 50 });
    }
    if (document.getElementById('extraFries').checked) {
        extras.push({ name: 'Extra Fries', price: 80 });
    }
    if (document.getElementById('extraSauce').checked) {
        extras.push({ name: 'Extra Sauce', price: 20 });
    }
    
    // Calculate total price with extras
    let itemPrice = currentCustomItem.price;
    extras.forEach(extra => {
        itemPrice += extra.price;
    });
    
    const customizations = {
        spice_level: spiceLevel,
        extras: extras
    };
    
    addToCart(currentCustomItem.id, currentCustomItem.name, itemPrice, customizations);
    closeCustomModal();
}

// Update total when extras change
document.addEventListener('change', function(e) {
    if (e.target.id === 'extraCheese' || e.target.id === 'extraFries' || e.target.id === 'extraSauce') {
        updateCustomModalTotal();
    }
});

// ==================== CART MANAGEMENT ====================
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    updateCartPanel();
}

function updateCartCount() {
    const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const badges = document.querySelectorAll('.cart-badge');
    badges.forEach(badge => {
        badge.textContent = cartCount;
        badge.style.display = cartCount > 0 ? 'flex' : 'none';
    });
    
    // Update sticky cart button
    updateStickyCart();
}

function updateStickyCart() {
    const stickyCartBtn = document.getElementById('stickyCartBtn');
    if (!stickyCartBtn) return;
    
    const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartTotal = getCartTotal();
    
    const stickyCartCount = document.getElementById('stickyCartCount');
    const stickyCartTotal = document.getElementById('stickyCartTotal');
    
    if (stickyCartCount) stickyCartCount.textContent = cartCount;
    if (stickyCartTotal) stickyCartTotal.textContent = formatPrice(cartTotal);
    
    // Show/hide sticky cart based on cart contents
    stickyCartBtn.style.display = cartCount > 0 ? 'flex' : 'none';
}

function addToCart(itemId, itemName, itemPrice, customizations = {}) {
    const existingItem = cart.find(item => item.id === itemId && JSON.stringify(item.customizations) === JSON.stringify(customizations));
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            id: itemId,
            name: itemName,
            price: itemPrice,
            quantity: 1,
            customizations: customizations
        });
    }
    
    saveCart();
    showToast(itemName + ' added to cart!', 'success');
    
    // Open cart panel
    openCartPanel();
}

function updateQuantity(itemId, change, customizations = {}) {
    const item = cart.find(i => i.id === itemId && JSON.stringify(i.customizations) === JSON.stringify(customizations));
    
    if (item) {
        item.quantity += change;
        
        if (item.quantity <= 0) {
            removeFromCart(itemId, customizations);
        } else {
            saveCart();
        }
    }
}

function removeFromCart(itemId, customizations = {}) {
    cart = cart.filter(item => !(item.id === itemId && JSON.stringify(item.customizations) === JSON.stringify(customizations)));
    saveCart();
}

function getCartTotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

// Format price - Nepali Rupees with thousand separators
function formatPrice(price) {
    const num = parseFloat(price);
    // If whole number, don't show decimals
    if (num === Math.floor(num)) {
        return 'Rs. ' + num.toLocaleString('en-IN');
    }
    // Otherwise show 2 decimal places
    return 'Rs. ' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ==================== SLIDE-OUT CART PANEL ====================
function openCartPanel() {
    const overlay = document.querySelector('.cart-overlay');
    const panel = document.querySelector('.cart-panel');
    if (overlay) overlay.classList.add('active');
    if (panel) panel.classList.add('active');
    updateCartPanel();
}

function closeCartPanel() {
    const overlay = document.querySelector('.cart-overlay');
    const panel = document.querySelector('.cart-panel');
    if (overlay) overlay.classList.remove('active');
    if (panel) panel.classList.remove('active');
}

function updateCartPanel() {
    const cartItemsContainer = document.querySelector('.cart-items');
    const cartTotalElement = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const emptyCart = document.querySelector('.empty-cart');
    
    if (!cartItemsContainer) return;
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <p>Your cart is empty</p>
            </div>
        `;
        if (cartTotalElement) cartTotalElement.textContent = 'Rs. 0.00';
        if (checkoutBtn) checkoutBtn.disabled = true;
        if (emptyCart) emptyCart.style.display = 'block';
        return;
    }
    
    if (emptyCart) emptyCart.style.display = 'none';
    if (checkoutBtn) checkoutBtn.disabled = false;
    
    cartItemsContainer.innerHTML = cart.map((item, index) => `
        <div class="cart-item">
            <div class="cart-item-image">🍽️</div>
            <div class="cart-item-details">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">${formatPrice(item.price * item.quantity)}</div>
            </div>
            <div class="cart-item-actions">
                <div class="quantity-control">
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1, ${JSON.stringify(item.customizations || {})})">−</button>
                    <span class="quantity-value">${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1, ${JSON.stringify(item.customizations || {})})">+</button>
                </div>
                <button class="remove-btn" onclick="removeFromCart(${item.id}, ${JSON.stringify(item.customizations || {})})">✕</button>
            </div>
        </div>
    `).join('');
    
    if (cartTotalElement) cartTotalElement.textContent = formatPrice(getCartTotal());
}

// ==================== SEARCH ====================
function searchMenu(query) {
    const menuItems = document.querySelectorAll('.menu-item');
    const lowerQuery = query.toLowerCase();
    
    menuItems.forEach(item => {
        const name = item.querySelector('.menu-item-name')?.textContent.toLowerCase() || '';
        const description = item.querySelector('.menu-item-description')?.textContent.toLowerCase() || '';
        
        if (name.includes(lowerQuery) || description.includes(lowerQuery)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// ==================== CATEGORY FILTER ====================
function filterByCategory(categoryId) {
    const buttons = document.querySelectorAll('.category-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    const activeBtn = document.querySelector(`[data-category="${categoryId || 'all'}"]`);
    if (activeBtn) activeBtn.classList.add('active');
    
    // Reload page with category filter
    const url = new URL(window.location.href);
    if (categoryId > 0) {
        url.searchParams.set('category', categoryId);
    } else {
        url.searchParams.delete('category');
    }
    window.location.href = url.toString();
}

// ==================== CALL WAITER ====================
function callWaiter() {
    const tableNumber = new URLSearchParams(window.location.search).get('table') || '1';
    
    fetch('api/call-waiter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'table_number=' + encodeURIComponent(tableNumber)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('🔔 Waiter has been notified!', 'success');
        } else {
            showToast('Error calling waiter', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error calling waiter', 'error');
    });
}

// ==================== ORDER TIMER ====================
function startOrderTimer(orderId, startTime) {
    const timerElement = document.getElementById(`timer-${orderId}`);
    if (!timerElement) return;
    
    setInterval(() => {
        const now = new Date();
        const start = new Date(startTime);
        const diff = Math.floor((now - start) / 1000);
        
        const minutes = Math.floor(diff / 60);
        const seconds = diff % 60;
        
        timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

// ==================== KITCHEN DASHBOARD ====================
let lastOrderCount = 0;

function loadKitchenOrders() {
    fetch('api/orders.php?status=all')
        .then(response => response.json())
        .then(data => {
            renderKitchenOrders(data.orders);
            
            // Play sound for new orders
            if (lastOrderCount > 0 && data.orders.length > lastOrderCount) {
                const newOrders = data.orders.filter(o => o.status === 'new');
                if (newOrders.length > 0) {
                    playNotificationSound();
                }
            }
            lastOrderCount = data.orders.length;
        })
        .catch(error => console.error('Error:', error));
}

function renderKitchenOrders(orders) {
    const ordersGrid = document.getElementById('ordersGrid');
    if (!ordersGrid) return;
    
    if (orders.length === 0) {
        ordersGrid.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📋</div><p>No orders yet</p></div>';
        return;
    }
    
    ordersGrid.innerHTML = orders.map(order => {
        const isNew = order.status === 'new';
        const timeAgo = getTimeAgo(order.created_at);
        
        return `
            <div class="order-card ${isNew ? 'new-order' : ''}">
                <div class="order-card-header ${order.status}">
                    <span class="order-id">#${order.id}</span>
                    <span class="order-table">Table ${order.table_number}</span>
                </div>
                <div class="order-card-body">
                    <div class="order-items-list">
                        ${order.items.map(item => `
                            <div class="order-item-row">
                                <span><span class="order-item-qty">${item.quantity}x</span> ${item.name}</span>
                                <span>${formatPrice(item.price * item.quantity)}</span>
                            </div>
                        `).join('')}
                    </div>
                    ${order.notes ? `<div class="order-notes"><strong>📝 Note:</strong> ${order.notes}</div>` : ''}
                    <div class="order-time">
                        <span>🕐 Ordered: ${timeAgo}</span>
                        <span class="order-timer" id="timer-${order.id}">0:00</span>
                    </div>
                    <div class="order-actions">
                        <button class="status-btn new ${order.status === 'new' ? 'active' : ''}" 
                                onclick="updateOrderStatus(${order.id}, 'new')" 
                                ${order.status === 'new' ? 'disabled' : ''}>New</button>
                        <button class="status-btn preparing ${order.status === 'preparing' ? 'active' : ''}" 
                                onclick="updateOrderStatus(${order.id}, 'preparing')" 
                                ${order.status === 'preparing' ? 'disabled' : ''}>Preparing</button>
                        <button class="status-btn ready ${order.status === 'ready' ? 'active' : ''}" 
                                onclick="updateOrderStatus(${order.id}, 'ready')" 
                                ${order.status === 'ready' ? 'disabled' : ''}>Ready</button>
                        <button class="status-btn completed ${order.status === 'completed' ? 'active' : ''}" 
                                onclick="updateOrderStatus(${order.id}, 'completed')" 
                                ${order.status === 'completed' ? 'disabled' : ''}>Done</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Start timers for all orders
    orders.forEach(order => {
        startOrderTimer(order.id, order.created_at);
    });
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return date.toLocaleDateString();
}

function updateOrderStatus(orderId, status) {
    fetch('api/update-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadKitchenOrders();
            showToast('Order status updated!', 'success');
        } else {
            showToast('Error updating order', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating order', 'error');
    });
}

function playNotificationSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.3;
        
        oscillator.start();
        
        setTimeout(() => { oscillator.frequency.value = 1000; }, 200);
        setTimeout(() => { oscillator.frequency.value = 800; }, 400);
        setTimeout(() => { oscillator.stop(); }, 600);
    } catch (e) {
        console.log('Audio not supported');
    }
}

// ==================== TOAST NOTIFICATIONS ====================
function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(t => t.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${type === 'success' ? '✓' : '✕'}</span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.4s ease reverse';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// ==================== LAZY LOADING ====================
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => observer.observe(img));
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Setup cart overlay click to close
    const cartOverlay = document.querySelector('.cart-overlay');
    if (cartOverlay) {
        cartOverlay.addEventListener('click', closeCartPanel);
    }
    
    // Setup search functionality
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => searchMenu(e.target.value));
    }
    
    // Setup call waiter button
    const callWaiterBtn = document.querySelector('.call-waiter-btn');
    if (callWaiterBtn) {
        callWaiterBtn.addEventListener('click', callWaiter);
    }
    
    // Setup cart close button
    const cartClose = document.querySelector('.cart-close');
    if (cartClose) {
        cartClose.addEventListener('click', closeCartPanel);
    }
    
    // Initialize lazy loading
    lazyLoadImages();
    
    // Initialize kitchen dashboard
    if (window.location.pathname.includes('kitchen-dashboard')) {
        loadKitchenOrders();
        // Auto-refresh every 5 seconds
        setInterval(loadKitchenOrders, 5000);
    }
});

// Cart button click handler
document.addEventListener('click', function(e) {
    if (e.target.closest('.cart-btn')) {
        openCartPanel();
    }
});
