<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Checkout - QR Cafe</title>
    <link rel="stylesheet" href="css/spatial.css">
</head>
<body>
    <!-- Mobile Header Bar -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" class="logo">
                <span>☕</span> QR Cafe & Dining
            </a>
            <span class="table-badge">📍 Table <?php echo isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1'; ?></span>
        </div>
    </header>

    <!-- Checkout Mobile Shell Container -->
    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">
        <div class="spatial-card" style="padding: 20px 16px;">
            <h1 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 16px; font-family: var(--font-serif);">Confirm & Place Order</h1>

            <!-- Empty Cart Alert -->
            <div id="emptyCheckoutAlert" style="display: none; text-align: center; padding: 30px 10px;">
                <div style="font-size: 3.5rem; margin-bottom: 10px;">🛒</div>
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px;">Your cart is empty</h3>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 18px;">Please add items to your cart before proceeding to checkout.</p>
                <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" class="checkout-btn" style="display: block; width: 100%; padding: 12px;">
                    Browse Menu
                </a>
            </div>

            <!-- Form Container -->
            <form id="spatialCheckoutForm">
                <input type="hidden" id="table_number" value="<?php echo isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1'; ?>">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Table Number</label>
                    <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; font-weight: 800; font-size: 0.95rem; color: var(--primary);">
                        📍 Table <?php echo isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1'; ?>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label for="customer_name" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Customer Name (Optional)</label>
                    <input type="text" id="customer_name" placeholder="Enter your name" style="width: 100%; background: rgba(14, 11, 8, 0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; font-family: inherit; font-size: 0.9rem; outline: none;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="notes" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Kitchen Instructions (Optional)</label>
                    <textarea id="notes" placeholder="e.g. Less sugar, extra napkin" rows="2" style="width: 100%; background: rgba(14, 11, 8, 0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; font-family: inherit; font-size: 0.88rem; outline: none; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px;">Order Summary</label>
                    <div id="checkoutOrderSummary" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 12px;">
                        <!-- Items rendered by JS -->
                    </div>
                </div>

                <button type="submit" id="placeOrderBtn" class="checkout-btn">
                    Place Order • <span id="checkoutTotalText">Rs. 0</span>
                </button>
            </form>
        </div>
    </main>

    <!-- PINNED MOBILE BOTTOM NAVIGATION BAR -->
    <nav class="mobile-nav-bar">
        <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" class="mobile-nav-item">
            <span class="mobile-nav-icon">🍽️</span>
            <span>Menu</span>
        </a>
        <a href="cart.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" class="mobile-nav-item active">
            <span class="mobile-nav-icon">🛒</span>
            <span>Cart</span>
            <span class="mobile-nav-badge cart-badge" style="display: none;">0</span>
        </a>
        <button class="mobile-nav-item" onclick="callWaiter()">
            <span class="mobile-nav-icon">🔔</span>
            <span>Waiter</span>
        </button>
        <a href="order-success.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" class="mobile-nav-item">
            <span class="mobile-nav-icon">📋</span>
            <span>Tracker</span>
        </a>
    </nav>

    <script src="js/modern.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('spatialCheckoutForm');
            const alertView = document.getElementById('emptyCheckoutAlert');
            const summaryContainer = document.getElementById('checkoutOrderSummary');
            const totalText = document.getElementById('checkoutTotalText');

            if (cart.length === 0) {
                if (form) form.style.display = 'none';
                if (alertView) alertView.style.display = 'block';
                return;
            }

            if (form) form.style.display = 'block';
            if (alertView) alertView.style.display = 'none';

            let itemsHTML = '<div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px;">';
            cart.forEach(item => {
                let customText = '';
                if (item.customizations) {
                    if (item.customizations.spice_level) customText += `🌶️ ${item.customizations.spice_level}`;
                    if (item.customizations.extras && item.customizations.extras.length > 0) {
                        const extraNames = item.customizations.extras.map(e => e.name).join(', ');
                        customText += ` | + ${extraNames}`;
                    }
                }

                itemsHTML += `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 6px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div>
                            <div style="font-weight: 700; color: var(--text-primary); font-size: 0.88rem;">${item.name} x ${item.quantity}</div>
                            ${customText ? `<div style="font-size: 0.72rem; color: var(--text-muted);">${customText}</div>` : ''}
                        </div>
                        <div style="font-weight: 800; color: var(--primary); font-size: 0.88rem;">${formatPrice(item.price * item.quantity)}</div>
                    </div>
                `;
            });
            itemsHTML += '</div>';
            itemsHTML += `
                <div style="display: flex; justify-content: space-between; align-items: center; font-weight: 800; font-size: 1.05rem; color: var(--text-primary); border-top: 1px solid var(--glass-border); padding-top: 8px;">
                    <span>Total:</span>
                    <span style="color: var(--primary);">${formatPrice(getCartTotal())}</span>
                </div>
            `;

            summaryContainer.innerHTML = itemsHTML;
            if (totalText) totalText.textContent = formatPrice(getCartTotal());

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const placeBtn = document.getElementById('placeOrderBtn');
                placeBtn.disabled = true;
                placeBtn.textContent = 'Processing...';

                const tableNum = document.getElementById('table_number').value;
                const custName = document.getElementById('customer_name').value;
                const noteText = document.getElementById('notes').value;

                const payload = {
                    table_number: tableNum,
                    customer_name: custName,
                    notes: noteText,
                    cart: cart
                };

                fetch('place-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showOrderPlacedTickModal(data.order, 'order-success.php?order_id=' + data.order_id);
                    } else {
                        showToast(data.message || 'Failed to place order', 'error');
                        placeBtn.disabled = false;
                        placeBtn.textContent = 'Place Order';
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('An error occurred. Please try again.', 'error');
                    placeBtn.disabled = false;
                    placeBtn.textContent = 'Place Order';
                });
            });
        });
    </script>
</body>
</html>
