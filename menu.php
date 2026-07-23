<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gourmet Menu - QR Cafe</title>
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

    <div class="mobile-app-shell">
        <!-- Search Bar -->
        <div style="margin: 14px 0 10px;">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search coffee, burgers, desserts...">
                <span class="search-icon">🔍</span>
            </div>
        </div>

        <?php
        require_once 'config.php';
        $conn = getDBConnection();
        $db_error = ($conn === null);
        ?>
        
        <!-- Category Navigation -->
        <nav class="category-nav">
            <div class="category-nav-scroll">
                <?php if (!$db_error): ?>
                    <?php
                    $categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
                    $categories = [];
                    while ($cat = $categories_result->fetch_assoc()) {
                        $categories[] = $cat;
                    }
                    $current_cat = isset($_GET['category']) ? intval($_GET['category']) : 0;
                    ?>
                    <button class="category-btn <?php echo $current_cat === 0 ? 'active' : ''; ?>" onclick="filterByCategory(0)">🍽️ All Dishes</button>
                    <?php foreach ($categories as $cat): ?>
                        <button class="category-btn <?php echo $current_cat === $cat['id'] ? 'active' : ''; ?>" onclick="filterByCategory(<?php echo $cat['id']; ?>)">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Menu Section -->
        <section style="margin-bottom: 20px;">
            <?php if ($db_error): ?>
                <div class="spatial-card" style="padding: 24px; text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 6px;">⚠️</div>
                    <h3>Database Error</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Unable to connect to MySQL database.</p>
                </div>
            <?php else: ?>
                <div id="menuGrid" class="menu-grid">
                    <?php
                    $category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
                    if ($category_filter > 0) {
                        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE status != 'inactive' AND category_id = ? ORDER BY name");
                        $stmt->bind_param("i", $category_filter);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        $result = $conn->query("SELECT * FROM menu_items WHERE status != 'inactive' ORDER BY category_id, name");
                    }
                    
                    if ($result && $result->num_rows > 0) {
                        while ($item = $result->fetch_assoc()) {
                            $badge = '';
                            $sold_out = ($item['status'] === 'sold_out');
                            if (!empty($item['is_popular'])) {
                                $badge = '<span class="popular-badge">⭐ Top</span>';
                            }
                            
                            echo '<div class="menu-item" data-name="' . strtolower(htmlspecialchars($item['name'])) . '" data-description="' . strtolower(htmlspecialchars($item['description'])) . '">';
                            echo '<div class="menu-item-image">';
                            if (!empty($item['image'])) {
                                echo '<img src="images/' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" loading="lazy" onerror="this.parentElement.innerHTML=\'🍽️\'">';
                            } else {
                                echo '🍽️';
                            }
                            echo $badge;
                            echo '</div>';
                            echo '<div class="menu-item-content">';
                            echo '<h3 class="menu-item-name">' . htmlspecialchars($item['name']) . '</h3>';
                            echo '<p class="menu-item-description">' . htmlspecialchars($item['description']) . '</p>';
                            echo '<div class="menu-item-footer">';
                            echo '<span class="menu-item-price">Rs. ' . number_format($item['price'], 0) . '</span>';
                            if (!$sold_out) {
                                $prepTime = isset($item['preparation_time']) ? intval($item['preparation_time']) : 15;
                                echo '<button class="add-to-cart-btn" onclick="openCustomModal(' . $item['id'] . ', \'' . addslashes(htmlspecialchars($item['name'])) . '\', ' . $item['price'] . ', \'' . addslashes(htmlspecialchars($item['description'])) . '\', ' . $prepTime . ')">+ Add</button>';
                            } else {
                                echo '<button class="add-to-cart-btn" disabled>Sold Out</button>';
                            }
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="spatial-card" style="padding: 30px; text-align: center; color: var(--text-muted);">';
                        echo '<div style="font-size: 2.5rem; margin-bottom: 6px;">🍽️</div>';
                        echo '<h3>No dishes found</h3>';
                        echo '</div>';
                    }
                    
                    $conn->close();
                    ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Bottom Sheet Item Customization Modal -->
    <div class="custom-modal" id="customModal">
        <div class="custom-modal-overlay" onclick="closeCustomModal()"></div>
        <div class="custom-modal-content">
            <button class="custom-modal-close" onclick="closeCustomModal()">✕</button>
            <div style="font-size: 2.5rem; text-align: center; margin-bottom: 4px;" id="customModalImage">🍽️</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; text-align: center;" id="customModalTitle">Dish Name</h3>
            <p style="color: var(--text-muted); text-align: center; font-size: 0.8rem; margin-bottom: 10px;" id="customModalDesc">Description</p>
            <div style="text-align: center; font-size: 1.15rem; font-weight: 800; color: var(--primary); margin-bottom: 14px;" id="customModalPrice">Rs. 0</div>
            
            <div class="customization-section" style="margin: 12px 0; padding: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: var(--radius-md);">
                <h4 style="font-size: 0.85rem; margin-bottom: 6px;">🌶️ Spice Level</h4>
                <div class="custom-option-group">
                    <label class="custom-radio">
                        <input type="radio" name="spice_level" value="mild" checked>
                        <span class="radio-label">Mild</span>
                    </label>
                    <label class="custom-radio">
                        <input type="radio" name="spice_level" value="medium">
                        <span class="radio-label">Medium 🌶️</span>
                    </label>
                    <label class="custom-radio">
                        <input type="radio" name="spice_level" value="hot">
                        <span class="radio-label">Spicy 🔥</span>
                    </label>
                </div>
                
                <h4 style="font-size: 0.85rem; margin: 10px 0 6px;">🧀 Extra Add-ons</h4>
                <div class="custom-option-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="extraCheese" data-price="50">
                        <span class="checkbox-label">Extra Cheese (+Rs. 50)</span>
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="extraFries" data-price="80">
                        <span class="checkbox-label">Crispy Fries (+Rs. 80)</span>
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="extraSauce" data-price="20">
                        <span class="checkbox-label">Special Sauce (+Rs. 20)</span>
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center; margin-top: 16px;">
                <div class="quantity-control">
                    <button class="quantity-btn" onclick="customModalChangeQty(-1)">−</button>
                    <span class="quantity-value" id="customModalQty">1</span>
                    <button class="quantity-btn" onclick="customModalChangeQty(1)">+</button>
                </div>
                <button class="add-to-cart-custom" style="flex: 1; padding: 12px;" onclick="addCustomToCart()">
                    Add to Cart • <span id="customModalTotal">Rs. 0</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Slide-out Spatial Cart Panel -->
    <div class="cart-overlay"></div>
    <div class="cart-panel">
        <div class="cart-header">
            <h3>🛒 Your Cart</h3>
            <button class="cart-close">✕</button>
        </div>
        <div class="cart-items"></div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cartTotal">Rs. 0.00</span>
            </div>
            <a id="checkoutBtn" class="checkout-btn" href="checkout.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>">
                Proceed to Checkout →
            </a>
        </div>
    </div>

    <!-- PINNED MOBILE BOTTOM NAVIGATION BAR -->
    <nav class="mobile-nav-bar">
        <a href="menu.php<?php echo isset($_GET['table']) ? '?table=' . urlencode($_GET['table']) : ''; ?>" class="mobile-nav-item active">
            <span class="mobile-nav-icon">🍽️</span>
            <span>Menu</span>
        </a>
        <button class="mobile-nav-item" onclick="openCartPanel()">
            <span class="mobile-nav-icon">🛒</span>
            <span>Cart</span>
            <span class="mobile-nav-badge cart-badge" style="display: none;">0</span>
        </button>
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
        function filterByCategory(catId) {
            const url = new URL(window.location.href);
            if (catId > 0) {
                url.searchParams.set('category', catId);
            } else {
                url.searchParams.delete('category');
            }
            window.location.href = url.toString();
        }

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const items = document.querySelectorAll('.menu-item');
            items.forEach(item => {
                const name = item.dataset.name || '';
                const desc = item.dataset.description || '';
                if (name.includes(query) || desc.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
