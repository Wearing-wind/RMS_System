<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - QR Restaurant</title>
    <link rel="stylesheet" href="css/modern.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . intval($_GET['table']) : ''; ?>" class="logo">
                <span class="logo-icon">🍽️</span>
                QR Restaurant
            </a>
            <div class="header-right">
                <span class="table-badge">Table <?php echo isset($_GET['table']) ? intval($_GET['table']) : 'N/A'; ?></span>
                <button class="cart-btn" id="cartBtn">
                    🛒 Cart
                    <span class="cart-badge" style="display: none;">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Search Bar -->
    <div class="search-container">
        <div class="container">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search menu items...">
                <span class="search-icon">🔍</span>
            </div>
        </div>
    </div>

    <?php
    require_once 'config.php';
    $conn = getDBConnection();
    $db_error = false;
    
    if ($conn === null) {
        $db_error = true;
    }
    ?>
    
    <!-- Category Navigation -->
    <nav class="category-nav">
        <div class="container">
            <?php if (!$db_error): ?>
                <?php
                $categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
                $categories = [];
                while ($cat = $categories_result->fetch_assoc()) {
                    $categories[] = $cat;
                }
                ?>
                <button class="category-btn active" data-category="all" onclick="filterByCategory(0)">🍽️ All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="category-btn" data-category="<?php echo $cat['id']; ?>" onclick="filterByCategory(<?php echo $cat['id']; ?>)"><?php echo htmlspecialchars($cat['name']); ?></button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Menu Section -->
    <section class="menu-section">
        <div class="container">
            <?php if ($db_error): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Database Connection Error</h3>
                    <p>Unable to connect to the database. Please contact the administrator.</p>
                </div>
            <?php else: ?>
                <div id="menuGrid" class="menu-grid">
                    <?php
                    $category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
                    if ($category_filter > 0) {
                        $result = $conn->query("SELECT * FROM menu_items WHERE status = 'active' AND category_id = $category_filter ORDER BY name");
                    } else {
                        $result = $conn->query("SELECT * FROM menu_items WHERE status = 'active' ORDER BY category_id, name");
                    }
                    
                    if ($result && $result->num_rows > 0) {
                        while ($item = $result->fetch_assoc()) {
                            // Check for popular or sold out status
                            $badge = '';
                            $sold_out_class = '';
                            if (!empty($item['is_popular'])) {
                                $badge = '<span class="popular-badge">⭐ Popular</span>';
                            }
                            if ($item['status'] === 'sold_out') {
                                $sold_out_class = ' sold-out';
                            }
                            
                            echo '<div class="menu-item' . $sold_out_class . '" data-name="' . strtolower(htmlspecialchars($item['name'])) . '" data-description="' . strtolower(htmlspecialchars($item['description'])) . '">';
                            echo '<div class="menu-item-image">';
                            if (!empty($item['image'])) {
                                echo '<img src="images/' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" loading="lazy">';
                            } else {
                                echo '🍽️';
                            }
                            echo $badge;
                            if ($item['status'] === 'sold_out') {
                                echo '<div class="sold-out-overlay">Sold Out</div>';
                            }
                            echo '</div>';
                            echo '<div class="menu-item-content">';
                            echo '<h3 class="menu-item-name">' . htmlspecialchars($item['name']) . '</h3>';
                            echo '<p class="menu-item-description">' . htmlspecialchars($item['description']) . '</p>';
                            echo '<div class="menu-item-footer">';
                            echo '<span class="menu-item-price">Rs. ' . number_format($item['price'], 2) . '</span>';
                            if ($item['status'] !== 'sold_out') {
                                $prepTime = isset($item['preparation_time']) ? $item['preparation_time'] : 15;
                                echo '<button class="add-to-cart-btn" onclick="openCustomModal(' . $item['id'] . ', \'' . addslashes($item['name']) . '\', ' . $item['price'] . ', \'' . addslashes($item['description']) . '\', ' . $prepTime . ')">Add to Cart</button>';
                            } else {
                                echo '<button class="add-to-cart-btn" disabled>Sold Out</button>';
                            }
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state"><div class="empty-state-icon">🍽️</div><p>No menu items available</p></div>';
                    }
                    
                    $conn->close();
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call Waiter Button -->
    <button class="call-waiter-btn" onclick="callWaiter()">
        🔔 Call Waiter
    </button>

    <!-- Sticky Cart Button for Mobile -->
    <button class="sticky-cart-btn" id="stickyCartBtn" onclick="openCartPanel()">
        🛒 View Cart
        <span class="sticky-cart-count" id="stickyCartCount">0</span>
        <span class="sticky-cart-total" id="stickyCartTotal">Rs. 0</span>
    </button>

    <!-- Item Customisation Modal -->
    <div class="custom-modal" id="customModal">
        <div class="custom-modal-overlay" onclick="closeCustomModal()"></div>
        <div class="custom-modal-content">
            <button class="custom-modal-close" onclick="closeCustomModal()">✕</button>
            <div class="custom-modal-image" id="customModalImage">🍽️</div>
            <h3 class="custom-modal-title" id="customModalTitle">Item Name</h3>
            <p class="custom-modal-desc" id="customModalDesc">Description</p>
            <p class="custom-modal-price" id="customModalPrice">Rs. 0</p>
            
            <!-- Customization Options -->
            <div class="customization-section" id="customizationSection">
                <h4>🧂 Spice Level</h4>
                <div class="custom-option-group">
                    <label class="custom-radio">
                        <input type="radio" name="spice_level" value="mild" checked>
                        <span class="radio-label">🌶️ Mild</span>
                    </label>
                    <label class="custom-radio">
                        <input type="radio" name="spice_level" value="medium">
                        <span class="radio-label">🌶️🌶️ Medium</span>
                    </label>
                    <label class="custom-radio">
                        <input type="radio" name="spice_level" value="hot">
                        <span class="radio-label">🌶️🌶️🌶️ Hot</span>
                    </label>
                </div>
                
                <h4>🧀 Extras</h4>
                <div class="custom-option-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="extraCheese" data-price="50">
                        <span class="checkbox-label">Extra Cheese (+Rs.50)</span>
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="extraFries" data-price="80">
                        <span class="checkbox-label">Extra Fries (+Rs.80)</span>
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="extraSauce" data-price="20">
                        <span class="checkbox-label">Extra Sauce (+Rs.20)</span>
                    </label>
                </div>
            </div>
            
            <div class="custom-modal-footer">
                <div class="quantity-selector">
                    <button onclick="customModalChangeQty(-1)">−</button>
                    <span id="customModalQty">1</span>
                    <button onclick="customModalChangeQty(1)">+</button>
                </div>
                <button class="add-to-cart-custom" onclick="addCustomToCart()">
                    Add to Cart - <span id="customModalTotal">Rs. 0</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Slide-out Cart Panel -->
    <div class="cart-overlay"></div>
    <div class="cart-panel">
        <div class="cart-header">
            <h3>🛒 Your Cart</h3>
            <button class="cart-close">✕</button>
        </div>
        <div class="cart-items">
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <p>Your cart is empty</p>
            </div>
        </div>
        <div class="cart-footer">
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

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Processing your order...</p>
        </div>
    </div>

    <script src="js/modern.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const name = item.dataset.name || '';
                const description = item.dataset.description || '';
                
                if (name.includes(query) || description.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Render cart quantities on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            // Update menu items to show quantities in cart
            const menuGrid = document.getElementById('menuGrid');
            if (!menuGrid) return;
            
            const items = menuGrid.querySelectorAll('.menu-item');
            
            items.forEach(item => {
                const addBtn = item.querySelector('.add-to-cart-btn');
                if (addBtn && cart.length > 0) {
                    const btnText = addBtn.getAttribute('onclick');
                    const match = btnText.match(/addToCart\((\d+)/);
                    if (match) {
                        const itemId = parseInt(match[1]);
                        const cartItem = cart.find(c => c.id === itemId);
                        if (cartItem && cartItem.quantity > 0) {
                            const footer = item.querySelector('.menu-item-footer');
                            footer.innerHTML = `
                                <span class="menu-item-price">${formatPrice(cartItem.price)}</span>
                                <div class="quantity-control">
                                    <button class="quantity-btn" onclick="updateQuantity(${itemId}, -1)">−</button>
                                    <span class="quantity-value">${cartItem.quantity}</span>
                                    <button class="quantity-btn" onclick="updateQuantity(${itemId}, 1)">+</button>
                                </div>
                            `;
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
