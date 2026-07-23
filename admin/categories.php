<?php
// Admin Categories Management
require_once '../config.php';
requireAdminLogin();
require_once 'header.php';

$conn = getDBConnection();

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Category added successfully';
            header('Location: categories.php');
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM categories WHERE id = $id");
    $_SESSION['success'] = 'Category deleted successfully';
    header('Location: categories.php');
    exit;
}

$categories = $conn->query("SELECT c.*, COUNT(mi.id) as item_count FROM categories c LEFT JOIN menu_items mi ON c.id = mi.category_id GROUP BY c.id ORDER BY c.name");

renderAdminHeader('categories', 'Categories Management');
?>

<?php if (isset($_SESSION['success'])): ?>
    <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
        ✓ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <!-- Add Category Form Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px; color: var(--primary);">+ Create New Category</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Category Name</label>
                <input type="text" name="name" required placeholder="e.g. Espresso Roasts" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Description</label>
                <textarea name="description" rows="3" placeholder="Category summary..." style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;"></textarea>
            </div>
            
            <button type="submit" class="checkout-btn">Create Category</button>
        </form>
    </div>

    <!-- Category List Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px;">🏷️ Active Categories</h3>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Dishes Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories && $categories->num_rows > 0): ?>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-primary); font-size: 0.95rem;"><?php echo htmlspecialchars($cat['name']); ?></strong>
                                <?php if (!empty($cat['description'])): ?>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($cat['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 800; color: var(--primary);"><?php echo $cat['item_count']; ?> items</td>
                            <td>
                                <a href="categories.php?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Delete this category?')" class="add-to-cart-btn" style="padding: 4px 10px; font-size: 0.75rem; background: var(--danger); color: white; text-decoration: none;">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 30px;">No categories found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
renderAdminFooter();
?>
