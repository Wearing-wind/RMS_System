/* ==========================================================================
   QR RESTAURANT - MODERN SPATIAL INTERACTIVE JAVASCRIPT
   ========================================================================== */

// Global Cart State
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Save Cart to LocalStorage and update all UI elements
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    updateCartPanel();
    if (typeof renderCartPage === 'function') {
        renderCartPage();
    }
}

// Format Price (Nepali Rupees)
function formatPrice(price) {
    const num = parseFloat(price) || 0;
    if (num === Math.floor(num)) {
        return 'Rs. ' + num.toLocaleString('en-IN');
    }
    return 'Rs. ' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Update Cart Count Badges & Sticky Mobile Button
function updateCartCount() {
    const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const badges = document.querySelectorAll('.cart-badge');
    badges.forEach(badge => {
        badge.textContent = totalCount;
        badge.style.display = totalCount > 0 ? 'flex' : 'none';
    });

    const stickyBtn = document.getElementById('stickyCartBtn');
    if (stickyBtn) {
        const stickyCount = document.getElementById('stickyCartCount');
        const stickyTotal = document.getElementById('stickyCartTotal');
        if (stickyCount) stickyCount.textContent = totalCount;
        if (stickyTotal) stickyTotal.textContent = formatPrice(getCartTotal());
        stickyBtn.style.display = totalCount > 0 ? 'flex' : 'none';
    }
}

// Calculate Cart Total Amount
function getCartTotal() {
    return cart.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
}

// Add Item to Cart
function addToCart(itemId, itemName, itemPrice, customizations = {}) {
    const existingIndex = cart.findIndex(item => item.id === itemId && JSON.stringify(item.customizations || {}) === JSON.stringify(customizations));

    if (existingIndex > -1) {
        cart[existingIndex].quantity++;
    } else {
        cart.push({
            id: itemId,
            name: itemName,
            price: parseFloat(itemPrice),
            quantity: 1,
            customizations: customizations
        });
    }

    saveCart();
    showToast(`Added ${itemName} to cart!`, 'success');
    openCartPanel();
}

// Update Item Quantity in Cart
function updateQuantity(itemId, change, customizations = {}) {
    const index = cart.findIndex(item => item.id === itemId && JSON.stringify(item.customizations || {}) === JSON.stringify(customizations));

    if (index > -1) {
        cart[index].quantity += change;
        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }
        saveCart();
    }
}

// Remove Item from Cart
function removeFromCart(itemId, customizations = {}) {
    cart = cart.filter(item => !(item.id === itemId && JSON.stringify(item.customizations || {}) === JSON.stringify(customizations)));
    saveCart();
    showToast('Item removed from cart', 'info');
}

// ==================== SLIDE-OUT SPATIAL CART PANEL ====================
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
    const container = document.querySelector('.cart-items');
    const totalEl = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                <div style="font-size: 3.5rem; margin-bottom: 12px;">🛒</div>
                <p style="font-size: 1rem; font-weight: 600;">Your cart is empty</p>
            </div>
        `;
        if (totalEl) totalEl.textContent = 'Rs. 0.00';
        if (checkoutBtn) checkoutBtn.disabled = true;
        return;
    }

    if (checkoutBtn) checkoutBtn.disabled = false;

    container.innerHTML = cart.map(item => {
        let customText = '';
        if (item.customizations) {
            if (item.customizations.spice_level) customText += `🌶️ ${item.customizations.spice_level}`;
            if (item.customizations.extras && item.customizations.extras.length > 0) {
                const extraNames = item.customizations.extras.map(e => e.name).join(', ');
                customText += ` | + ${extraNames}`;
            }
        }
        return `
            <div class="cart-item">
                <div class="cart-item-image">🍽️</div>
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    ${customText ? `<div class="cart-item-customizations">${customText}</div>` : ''}
                    <div class="cart-item-price">${formatPrice(item.price * item.quantity)}</div>
                </div>
                <div class="cart-item-actions">
                    <div class="quantity-control">
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1, ${JSON.stringify(item.customizations || {}).replace(/"/g, '&quot;')})">−</button>
                        <span class="quantity-value">${item.quantity}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1, ${JSON.stringify(item.customizations || {}).replace(/"/g, '&quot;')})">+</button>
                    </div>
                    <button class="remove-btn" onclick="removeFromCart(${item.id}, ${JSON.stringify(item.customizations || {}).replace(/"/g, '&quot;')})">Remove</button>
                </div>
            </div>
        `;
    }).join('');

    if (totalEl) totalEl.textContent = formatPrice(getCartTotal());
}

// ==================== ITEM CUSTOMIZATION MODAL ====================
let currentCustomItem = null;

function openCustomModal(itemId, itemName, itemPrice, description, prepTime) {
    currentCustomItem = {
        id: itemId,
        name: itemName,
        price: parseFloat(itemPrice),
        description: description || '',
        preparationTime: prepTime || 15,
        quantity: 1
    };

    const titleEl = document.getElementById('customModalTitle');
    const descEl = document.getElementById('customModalDesc');
    const priceEl = document.getElementById('customModalPrice');
    const qtyEl = document.getElementById('customModalQty');
    const totalEl = document.getElementById('customModalTotal');

    if (titleEl) titleEl.textContent = itemName;
    if (descEl) descEl.textContent = description || '';
    if (priceEl) priceEl.textContent = formatPrice(itemPrice);
    if (qtyEl) qtyEl.textContent = '1';

    // Reset options
    const mildRadio = document.querySelector('input[name="spice_level"][value="mild"]');
    if (mildRadio) mildRadio.checked = true;

    ['extraCheese', 'extraFries', 'extraSauce'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.checked = false;
    });

    updateCustomModalTotal();

    const modal = document.getElementById('customModal');
    if (modal) modal.classList.add('active');
}

function closeCustomModal() {
    const modal = document.getElementById('customModal');
    if (modal) modal.classList.remove('active');
}

function customModalChangeQty(change) {
    if (!currentCustomItem) return;
    currentCustomItem.quantity += change;
    if (currentCustomItem.quantity < 1) currentCustomItem.quantity = 1;
    if (currentCustomItem.quantity > 10) currentCustomItem.quantity = 10;

    const qtyEl = document.getElementById('customModalQty');
    if (qtyEl) qtyEl.textContent = currentCustomItem.quantity;
    updateCustomModalTotal();
}

function updateCustomModalTotal() {
    if (!currentCustomItem) return;
    let total = currentCustomItem.price;

    ['extraCheese', 'extraFries', 'extraSauce'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.checked) {
            total += parseFloat(el.dataset.price || 0);
        }
    });

    total *= currentCustomItem.quantity;
    const totalEl = document.getElementById('customModalTotal');
    if (totalEl) totalEl.textContent = formatPrice(total);
}

function addCustomToCart() {
    if (!currentCustomItem) return;

    const spiceEl = document.querySelector('input[name="spice_level"]:checked');
    const spiceLevel = spiceEl ? spiceEl.value : 'mild';
    const extras = [];

    const extraMap = [
        { id: 'extraCheese', name: 'Extra Cheese', price: 50 },
        { id: 'extraFries', name: 'Extra Fries', price: 80 },
        { id: 'extraSauce', name: 'Extra Sauce', price: 20 }
    ];

    extraMap.forEach(e => {
        const el = document.getElementById(e.id);
        if (el && el.checked) {
            extras.push({ name: e.name, price: e.price });
        }
    });

    let unitPrice = currentCustomItem.price;
    extras.forEach(e => { unitPrice += e.price; });

    const customizations = {
        spice_level: spiceLevel,
        extras: extras
    };

    for (let i = 0; i < currentCustomItem.quantity; i++) {
        addToCart(currentCustomItem.id, currentCustomItem.name, unitPrice, customizations);
    }

    closeCustomModal();
}

// ==================== ANIMATED ORDER PLACED TICK MODAL ====================
function showOrderPlacedTickModal(orderData, redirectUrl) {
    // Play payment success chime
    playSuccessChime();

    // Create tick modal element
    const modal = document.createElement('div');
    modal.className = 'order-tick-modal active';
    modal.id = 'orderPlacedTickModal';

    const itemsSummary = (orderData.items || []).map(i => `
        <div class="order-tick-row">
            <span>${i.quantity}x ${i.name}</span>
            <span>${formatPrice(i.price * i.quantity)}</span>
        </div>
    `).join('');

    modal.innerHTML = `
        <div class="order-tick-card">
            <div class="tick-wrapper">
                <svg class="checkmark-svg" viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h2 class="order-tick-title">Order Placed Successfully!</h2>
            <p class="order-tick-subtitle">Order #${orderData.id} • Table ${orderData.table_number}</p>
            
            <div class="order-tick-details">
                ${itemsSummary}
                <div class="order-tick-row total">
                    <span>Total Amount Paid</span>
                    <span>${formatPrice(orderData.total)}</span>
                </div>
            </div>
            
            <button class="order-tick-btn" id="viewTrackerBtn">Track Order Progress →</button>
        </div>
    `;

    document.body.appendChild(modal);

    // Clear cart in localStorage
    localStorage.removeItem('cart');
    cart = [];
    updateCartCount();

    const redirectAction = () => {
        window.location.href = redirectUrl;
    };

    document.getElementById('viewTrackerBtn').addEventListener('click', redirectAction);

    // Auto-redirect after 3.5 seconds
    setTimeout(redirectAction, 3500);
}

// Web Audio API Success Chime
function playSuccessChime() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioCtx.currentTime;

        const playNote = (freq, startTime, duration) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, startTime);

            gain.gain.setValueAtTime(0.01, startTime);
            gain.gain.exponentialRampToValueAtTime(0.3, startTime + 0.05);
            gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

            osc.connect(gain);
            gain.connect(audioCtx.destination);

            osc.start(startTime);
            osc.stop(startTime + duration);
        };

        // Apple Pay style double chime (E5 -> A5)
        playNote(659.25, now, 0.25);
        playNote(880.00, now + 0.15, 0.5);
    } catch (e) {
        console.log('Audio chime not supported');
    }
}

// Call Waiter API
function callWaiter() {
    const tableNumber = new URLSearchParams(window.location.search).get('table') || '1';

    fetch('api/call-waiter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'table_number=' + encodeURIComponent(tableNumber)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('🔔 Waiter has been notified to your table!', 'success');
        } else {
            showToast(data.message || 'Error calling waiter', 'warning');
        }
    })
    .catch(() => showToast('Error notifying waiter', 'error'));
}

// Toast Notifications
function showToast(message, type = 'success') {
    const existingToasts = document.querySelectorAll('.spatial-toast');
    existingToasts.forEach(t => t.remove());

    const toast = document.createElement('div');
    toast.className = `spatial-toast ${type}`;
    const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠️' : 'ℹ️';
    toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Safe Cross-Platform Date Helper (Fixes Safari/macOS NaN bug)
function parseMySQLDate(dateStr) {
    if (!dateStr) return new Date();
    // Replace space with 'T' for standard ISO 8601 parsing
    const isoStr = dateStr.replace(' ', 'T');
    const parsed = new Date(isoStr);
    return isNaN(parsed.getTime()) ? new Date(dateStr) : parsed;
}

function getTimeAgo(dateStr) {
    const date = parseMySQLDate(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return date.toLocaleDateString();
}

// Global Event Listeners Setup
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();

    // Click handler for cart overlay & buttons
    const cartOverlay = document.querySelector('.cart-overlay');
    if (cartOverlay) cartOverlay.addEventListener('click', closeCartPanel);

    const cartClose = document.querySelector('.cart-close');
    if (cartClose) cartClose.addEventListener('click', closeCartPanel);

    document.addEventListener('click', function(e) {
        if (e.target.closest('#cartBtn') || e.target.closest('.cart-btn')) {
            openCartPanel();
        }
    });
});
