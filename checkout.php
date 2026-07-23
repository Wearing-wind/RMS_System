<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Checkout - QR Cafe</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              amber: {
                500: '#f59e0b',
                600: '#d97706',
              }
            }
          }
        }
      }
    </script>
    <style>
        body { overscroll-behavior-y: contain; -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="min-h-full pb-24 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <?php
    $table_num = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1';
    ?>

    <!-- Sticky Mobile Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-3">
            <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="flex items-center gap-2 text-lg font-black text-white">
                <span>☕</span> QR Cafe & Dining
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs">
                📍 Table <?php echo $table_num; ?>
            </span>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-4">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-2xl">
            <h1 class="text-xl font-black text-white mb-4">Confirm & Place Order</h1>

            <!-- Empty Cart Warning -->
            <div id="emptyCheckoutAlert" style="display: none;" class="text-center py-10 space-y-3">
                <div class="text-5xl">🛒</div>
                <h3 class="text-base font-extrabold text-white">Your cart is empty</h3>
                <p class="text-xs text-zinc-400">Please add items to your cart before proceeding.</p>
                <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                    Browse Menu
                </a>
            </div>

            <!-- Checkout Form -->
            <form id="spatialCheckoutForm" class="space-y-4">
                <input type="hidden" id="table_number" value="<?php echo $table_num; ?>">

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1.5">Table Number</label>
                    <div class="w-full bg-zinc-950 border border-zinc-800 rounded-2xl p-3.5 font-black text-sm text-amber-400">
                        📍 Table <?php echo $table_num; ?>
                    </div>
                </div>

                <div>
                    <label for="customer_name" class="block text-xs font-bold text-zinc-300 mb-1.5">Customer Name (Optional)</label>
                    <input type="text" id="customer_name" placeholder="Enter your name" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 transition-all">
                </div>

                <div>
                    <label for="notes" class="block text-xs font-bold text-zinc-300 mb-1.5">Kitchen Instructions (Optional)</label>
                    <textarea id="notes" placeholder="e.g. Less sugar, extra napkin" rows="2" class="w-full bg-zinc-950 border border-zinc-800 rounded-2xl p-3.5 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 transition-all resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1.5">Order Summary</label>
                    <div id="checkoutOrderSummary" class="bg-zinc-950/80 border border-zinc-800 rounded-2xl p-4 space-y-2">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <button type="submit" id="placeOrderBtn" class="h-12 w-full rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-sm active:scale-95 shadow-lg shadow-amber-500/20">
                    Place Order • <span id="checkoutTotalText">Rs. 0</span>
                </button>
            </form>
        </div>
    </main>

    <!-- Fixed Bottom Tab Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">🍽️</span>
            <span>Menu</span>
        </a>
        <a href="cart.php?table=<?php echo urlencode($table_num); ?>" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-[10px]">
            <span class="text-lg">🛒</span>
            <span>Cart</span>
            <span class="cart-badge absolute top-1 right-2 bg-amber-500 text-zinc-950 font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center" style="display: none;">0</span>
        </a>
        <button onclick="callWaiter()" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">🔔</span>
            <span>Waiter</span>
        </button>
        <a href="order-success.php?table=<?php echo urlencode($table_num); ?>" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📋</span>
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

            let itemsHTML = '<div class="space-y-2 mb-3 pb-3 border-b border-zinc-800">';
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
                    <div class="flex justify-between items-center text-xs">
                        <div>
                            <div class="font-extrabold text-white">${item.name} x ${item.quantity}</div>
                            ${customText ? `<div class="text-[10px] text-zinc-400">${customText}</div>` : ''}
                        </div>
                        <div class="font-black text-amber-400">${formatPrice(item.price * item.quantity)}</div>
                    </div>
                `;
            });
            itemsHTML += '</div>';
            itemsHTML += `
                <div class="flex justify-between items-center font-black text-sm text-white pt-1">
                    <span>Total:</span>
                    <span class="text-amber-400 text-base">${formatPrice(getCartTotal())}</span>
                </div>
            `;

            summaryContainer.innerHTML = itemsHTML;
            if (totalText) totalText.textContent = formatPrice(getCartTotal());

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const placeBtn = document.getElementById('placeOrderBtn');
                placeBtn.disabled = true;
                placeBtn.textContent = 'Processing Order...';

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
                    headers: { 'Content-Type': 'application/json' },
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
