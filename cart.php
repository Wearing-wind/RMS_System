<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - QR Restaurant</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="logo">🍽️ QR Restaurant</a>
            <div class="header-right">
                <span class="table-info">Table <?php echo isset($_GET['table']) ? intval($_GET['table']) : 'N/A'; ?></span>
                <button class="cart-btn">
                    🛒 Cart
                    <span class="cart-badge" style="display: none;">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Cart Page -->
    <section class="cart-page">
        <div class="container">
            <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="back-btn">← Back to Menu</a>
            <h1>Your Cart</h1>
            
            <div id="emptyCart" class="empty-state" style="display: none;">
                <div class="empty-state-icon">🛒</div>
                <p>Your cart is empty</p>
                <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="btn btn-primary">Browse Menu</a>
            </div>
            
            <div id="cartItems" class="cart-items">
                <!-- Cart items will be rendered by JavaScript -->
            </div>
            
            <div class="cart-summary" id="cartSummary" style="display: none;">
                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cartTotal">Rs. 0.00</span>
                </div>
                <button id="checkoutBtn" class="checkout-btn" onclick="window.location.href='checkout.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>'" disabled>
                    Proceed to Checkout
                </button>
                <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="continue-shopping">Continue Shopping</a>
            </div>
        </div>
    </section>

    <script src="js/script.js"></script>
    <script>
        // Render cart on page load
        document.addEventListener('DOMContentLoaded', function() {
            renderCart();
        });
    </script>
</body>
</html>
