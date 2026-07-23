<?php
// Admin Menu Items Management
require_once '../config.php';
requireAdminLogin();
require_once 'header.php';

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $status = isset($_POST['status']) ? 'active' : 'inactive';
            
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../images/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image = $file_name;
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO menu_items (name, description, price, image, category_id, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsis", $name, $description, $price, $image, $category_id, $status);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = 'Menu item added successfully';
            header('Location: menu-items.php');
            exit;
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $status = isset($_POST['status']) ? 'active' : 'inactive';
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../images/';
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $stmt = $conn->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, image = ?, category_id = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("ssdsisi", $name, $description, $price, $file_name, $category_id, $status, $id);
                    $stmt->execute();
                    $stmt->close();
                    
                    $_SESSION['success'] = 'Menu item updated successfully';
                    header('Location: menu-items.php');
                    exit;
                }
            }
            
            $stmt = $conn->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssdisi", $name, $description, $price, $category_id, $status, $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = 'Menu item updated successfully';
            header('Location: menu-items.php');
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM menu_items WHERE id = $id");
    $_SESSION['success'] = 'Menu item deleted successfully';
    header('Location: menu-items.php');
    exit;
}

// Handle toggle popular
if (isset($_GET['toggle_popular'])) {
    $id = intval($_GET['toggle_popular']);
    $status = intval($_GET['status']);
    $conn->query("UPDATE menu_items SET is_popular = $status WHERE id = $id");
    $_SESSION['success'] = $status ? 'Item marked as popular' : 'Item removed from popular';
    header('Location: menu-items.php');
    exit;
}

// Handle toggle sold out
if (isset($_GET['toggle_sold_out'])) {
    $id = intval($_GET['toggle_sold_out']);
    $status = sanitize($_GET['status']);
    $conn->query("UPDATE menu_items SET status = '$status' WHERE id = $id");
    $_SESSION['success'] = $status === 'sold_out' ? 'Item marked as sold out' : 'Item is now in stock';
    header('Location: menu-items.php');
    exit;
}

// Fetch categories & menu items
$categories_res = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($c = $categories_res->fetch_assoc()) $categories[] = $c;

$menu_items = $conn->query("
    SELECT mi.*, c.name as category_name 
    FROM menu_items mi 
    LEFT JOIN categories c ON mi.category_id = c.id 
    ORDER BY c.name, mi.name
");

renderAdminHeader('menu-items', 'Menu Items Management');
?>

<?php if (isset($_SESSION['success'])): ?>
    <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
        ✓ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Add New Item Form Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px; color: var(--primary);">+ Add New Dish / Drink</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Item Name</label>
                <input type="text" name="name" required placeholder="e.g. Espresso Single" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;">
            </div>
            
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Category</label>
                <select name="category_id" required style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Price (Rs.)</label>
                <input type="number" step="0.01" name="price" required placeholder="e.g. 250" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;">
            </div>
            
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Description</label>
                <textarea name="description" rows="2" placeholder="Brief description..." style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;"></textarea>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Dish Image</label>
                <input type="file" name="image" accept="image/*" style="width: 100%; color: var(--text-muted); font-size: 0.85rem;">
            </div>
            
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="status" name="status" value="active" checked style="accent-color: var(--primary);">
                <label for="status" style="font-weight: 700; font-size: 0.88rem; color: var(--text-primary);">Active in Menu</label>
            </div>
            
            <button type="submit" class="checkout-btn">Add Dish to Menu</button>
        </form>
    </div>

    <!-- Menu Items Table Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px;">☕ All Menu Items</h3>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($menu_items && $menu_items->num_rows > 0): ?>
                    <?php while ($item = $menu_items->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="../images/<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            ☕
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong style="color: var(--text-primary); font-size: 0.9rem;"><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <?php if ($item['is_popular']): ?>
                                            <span style="font-size: 0.68rem; background: var(--primary); color: black; font-weight: 800; padding: 1px 6px; border-radius: var(--radius-pill); margin-left: 4px;">⭐ Popular</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?: 'General'); ?></td>
                            <td style="color: var(--primary); font-weight: 800;">Rs. <?php echo number_format($item['price'], 0); ?></td>
                            <td>
                                <?php if ($item['status'] === 'sold_out'): ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; color: var(--danger); background: rgba(244,63,94,0.15); padding: 2px 8px; border-radius: var(--radius-pill); border: 1px solid var(--danger);">SOLD OUT</span>
                                <?php elseif ($item['status'] === 'active'): ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #4ade80; background: rgba(34,197,94,0.15); padding: 2px 8px; border-radius: var(--radius-pill); border: 1px solid var(--success);">ACTIVE</span>
                                <?php else: ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: var(--radius-pill);">INACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <?php if ($item['status'] === 'sold_out'): ?>
                                        <a href="menu-items.php?toggle_sold_out=<?php echo $item['id']; ?>&status=active" class="add-to-cart-btn" style="padding: 3px 8px; font-size: 0.72rem; background: var(--success); color: black; text-decoration: none;">In Stock</a>
                                    <?php else: ?>
                                        <a href="menu-items.php?toggle_sold_out=<?php echo $item['id']; ?>&status=sold_out" class="add-to-cart-btn" style="padding: 3px 8px; font-size: 0.72rem; background: rgba(244,63,94,0.2); color: #fda4af; text-decoration: none;">Sold Out</a>
                                    <?php endif; ?>

                                    <a href="menu-items.php?toggle_popular=<?php echo $item['id']; ?>&status=<?php echo $item['is_popular'] ? 0 : 1; ?>" class="add-to-cart-btn" style="padding: 3px 8px; font-size: 0.72rem; background: rgba(255,255,255,0.1); color: white; text-decoration: none;">
                                        <?php echo $item['is_popular'] ? 'Unmark ⭐' : 'Mark ⭐'; ?>
                                    </a>

                                    <a href="menu-items.php?delete=<?php echo $item['id']; ?>" onclick="return confirm('Delete this menu item?')" class="add-to-cart-btn" style="padding: 3px 8px; font-size: 0.72rem; background: var(--danger); color: white; text-decoration: none;">✕</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No menu items created yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
renderAdminFooter();
?>
