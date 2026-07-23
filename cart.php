<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cart - QR Cafe</title>
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

    <!-- Cart Mobile Shell Content -->
    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">
        <div class="spatial-card" style="padding: 20px 16px;">
            <h1 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-family: var(--font-serif);">
                <span>🛒</span> Your Cart Order
            </h1>

            <div id="cartItemsContainer" class="cart-items" style="padding: 0; margin-bottom: 16px;">
                <!-- Rendered dynamically by JavaScript -->
            </div>

            <div id="emptyCartView" style="display: none; text-align: center; padding: 30px 10px;">
                <div style="font-size: 3.5rem; margin-bottom: 10px;">🛒</div>
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px;">Your cart is empty</h3>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 18px;">Add some delicious dishes from our menu to get started.</p>
                <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" class="checkout-btn" style="display: block; width: 100%; padding: 12px;">
                    Browse Menu
                </a>
            </div>

            <div id="cartSummaryView" style="border-top: 1px solid var(--glass-border); padding-top: 14px;">
                <div class="cart-total" style="font-size: 1.25rem; margin-bottom: 16px;">
                    <span>Total Amount:</span>
                    <span id="pageCartTotal" style="color: var(--primary);">Rs. 0.00</span>
                </div>

                <a href="checkout.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" id="pageCheckoutBtn" class="checkout-btn">
                    Proceed to Checkout →
                </a>
            </div>
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
        function renderCartPage() {
            const container = document.getElementById('cartItemsContainer');
            const emptyView = document.getElementById('emptyCartView');
            const summaryView = document.getElementById('cartSummaryView');
            const totalEl = document.getElementById('pageCartTotal');

            if (!container) return;

            if (cart.length === 0) {
                container.innerHTML = '';
                if (emptyView) emptyView.style.display = 'block';
                if (summaryView) summaryView.style.display = 'none';
                return;
            }

            if (emptyView) emptyView.style.display = 'none';
            if (summaryView) summaryView.style.display = 'block';

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

        document.addEventListener('DOMContentLoaded', function() {
            renderCartPage();
        });
    </script>
</body>
</html>
