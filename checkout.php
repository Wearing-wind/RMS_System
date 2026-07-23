<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - QR Restaurant</title>
    <link rel="stylesheet" href="css/modern.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f6fa; }
        .header { background: linear-gradient(135deg, #ff6b35, #ff8c5a); padding: 15px 0; }
        .header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .header .logo { color: white; font-size: 1.4rem; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .table-info { background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; }
        .checkout-page { max-width: 600px; margin: 30px auto; padding: 0 20px; }
        .back-btn { display: inline-block; color: #ff6b35; text-decoration: none; margin-bottom: 15px; font-weight: 600; }
        .back-btn:hover { text-decoration: underline; }
        h1 { color: #2d3436; margin-bottom: 25px; }
        .alert { padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #ffeaa7; color: #d63031; }
        .checkout-form { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2d3436; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 14px; border: 2px solid #dfe6e9; border-radius: 10px; font-size: 1rem; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #ff6b35; }
        .table-display { background: #f8f9fa; padding: 14px; border-radius: 10px; font-weight: 600; color: #2d3436; }
        .cart-items { margin-bottom: 15px; }
        .cart-item { display: flex; align-items: center; padding: 12px; background: #f8f9fa; border-radius: 10px; margin-bottom: 10px; }
        .cart-item-image { font-size: 2rem; margin-right: 15px; }
        .cart-item-details { flex: 1; }
        .cart-item-name { font-weight: 600; color: #2d3436; }
        .cart-item-price { color: #ff6b35; font-weight: 700; }
        .cart-total { display: flex; justify-content: space-between; padding: 15px; background: linear-gradient(135deg, #ff6b35, #ff8c5a); border-radius: 10px; color: white; font-size: 1.2rem; font-weight: 700; margin-top: 15px; }
        .checkout-btn { width: 100%; background: linear-gradient(135deg, #ff6b35, #ff8c5a); color: white; border: none; padding: 16px; border-radius: 30px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-top: 20px; transition: transform 0.3s, box-shadow 0.3s; }
        .checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,107,53,0.4); }
        .btn-primary { display: inline-block; background: #ff6b35; color: white; padding: 12px 24px; border-radius: 25px; text-decoration: none; font-weight: 600; margin-top: 10px; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="logo">🍽️ QR Restaurant</a>
            <span class="table-info">Table <?php echo isset($_GET['table']) ? intval($_GET['table']) : 'N/A'; ?></span>
        </div>
    </header>

    <!-- Checkout Page -->
    <section class="checkout-page">
        <a href="cart.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="back-btn">← Back to Cart</a>
        <h1>Checkout</h1>
        
        <!-- Empty Cart Message -->
        <div id="emptyCart" class="alert alert-error" style="display: none;">
            Your cart is empty. Please add items to your cart first.
            <br><br>
            <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="btn-primary">Browse Menu</a>
        </div>
        
        <div class="checkout-form" id="checkoutFormContainer" style="display: none;">
            <form id="checkoutForm" method="POST" action="place-order.php">
                <input type="hidden" name="table_number" value="<?php echo isset($_GET['table']) ? intval($_GET['table']) : ''; ?>">
                
                <div class="form-group">
                    <label>Table Number</label>
                    <div class="table-display">Table <?php echo isset($_GET['table']) ? intval($_GET['table']) : 'N/A'; ?></div>
                </div>
                
                <div class="form-group">
                    <label for="customer_name">Customer Name (Optional)</label>
                    <input type="text" id="customer_name" name="customer_name" placeholder="Enter your name">
                </div>
                
                <div class="form-group">
                    <label for="notes">Order Notes (Optional)</label>
                    <textarea id="notes" name="notes" placeholder="Any special instructions?" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Order Summary</label>
                    <div id="orderSummary"></div>
                </div>
                
                <button type="submit" class="checkout-btn">Place Order - Rs. <span id="totalAmount">0</span></button>
            </form>
        </div>
    </section>

    <script>
        // Cart data - load from localStorage
        var cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        // Format price function (Nepalese Rupee)
        function formatPrice(price) {
            return 'Rs. ' + price.toLocaleString('en-IN');
        }
        
        // Calculate cart total
        function getCartTotal() {
            return cart.reduce(function(sum, item) {
                return sum + (item.price * item.quantity);
            }, 0);
        }
        
        // Display order summary
        document.addEventListener('DOMContentLoaded', function() {
            var summaryContainer = document.getElementById('orderSummary');
            var emptyCart = document.getElementById('emptyCart');
            var checkoutFormContainer = document.getElementById('checkoutFormContainer');
            var totalAmount = document.getElementById('totalAmount');
            
            if (cart.length === 0) {
                if (emptyCart) emptyCart.style.display = 'block';
                if (checkoutFormContainer) checkoutFormContainer.style.display = 'none';
                return;
            }
            
            if (emptyCart) emptyCart.style.display = 'none';
            if (checkoutFormContainer) checkoutFormContainer.style.display = 'block';
            
            var summaryHTML = '<div class="cart-items">';
            cart.forEach(function(item) {
                summaryHTML += 
                    '<div class="cart-item">' +
                        '<div class="cart-item-image">🍽️</div>' +
                        '<div class="cart-item-details">' +
                            '<div class="cart-item-name">' + item.name + ' x ' + item.quantity + '</div>' +
                            '<div class="cart-item-price">' + formatPrice(item.price * item.quantity) + '</div>' +
                        '</div>' +
                    '</div>';
            });
            summaryHTML += '</div>';
            summaryHTML += '<div class="cart-total"><span>Total:</span><span>' + formatPrice(getCartTotal()) + '</span></div>';
            
            summaryContainer.innerHTML = summaryHTML;
            totalAmount.textContent = getCartTotal().toLocaleString('en-IN');
        });
        
        // Handle form submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            var cartInput = document.createElement('input');
            cartInput.type = 'hidden';
            cartInput.name = 'cart_data';
            cartInput.value = JSON.stringify(cart);
            this.appendChild(cartInput);
        });
    </script>
</body>
</html>
