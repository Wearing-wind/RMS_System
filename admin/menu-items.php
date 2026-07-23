<?php
// Admin Menu Items Management
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected. Please run setup.php first.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $status = isset($_POST['status']) ? 'active' : 'inactive';
            
            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../images/';
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
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $status = isset($_POST['status']) ? 'active' : 'inactive';
            
            // Check if new image uploaded
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

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get menu items
$menu_items = $conn->query("
    SELECT mi.*, c.name as category_name 
    FROM menu_items mi 
    LEFT JOIN categories c ON mi.category_id = c.id 
    ORDER BY c.name, mi.name
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - QR Restaurant Admin</title>
    <link rel="stylesheet" href="../css/modern.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f6fa; }
        .admin-header { background: linear-gradient(135deg, #ff6b35, #ff8c5a); padding: 15px 0; }
        .admin-header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .admin-logo { color: white; font-size: 1.5rem; font-weight: bold; text-decoration: none; }
        .admin-nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-nav a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 10px 18px; border-radius: 25px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); color: white; }
        .admin-content { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .admin-content h1 { color: #2d3436; margin-bottom: 25px; font-size: 2rem; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-primary { background: #ff6b35; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-card h2 { margin-top: 0; color: #2d3436; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #2d3436; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 2px solid #dfe6e9; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #ff6b35; }
        .admin-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .admin-table table { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #ff6b35; color: white; padding: 15px; text-align: left; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #dfe6e9; }
        .admin-table tr:hover { background: #f8f9fa; }
        .status-active { color: #27ae60; font-weight: 600; }
        .status-inactive { color: #e74c3c; font-weight: 600; }
        .status-sold_out { color: #e74c3c; font-weight: 600; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .popular-indicator { margin-left: 5px; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <a href="index.php" class="admin-logo">🍽️ Admin Panel</a>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="menu-items.php" class="active">Menu Items</a>
                <a href="categories.php">Categories</a>
                <a href="tables.php">Tables & QR</a>
                <a href="orders.php">Orders</a>
                <a href="payment-settings.php">Payment Settings</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Admin Content -->
    <section class="admin-content">
        <h1>Menu Items Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <!-- Add New Button -->
        <button class="btn btn-primary" onclick="document.getElementById('addForm').style.display='block'">
            + Add Menu Item
        </button>
        
        <!-- Add Form -->
        <div id="addForm" class="form-card" style="display: none; margin-top: 20px;">
            <h2>Add New Menu Item</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Price (Rs.)</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="status" checked> Active
                    </label>
                </div>
                <button type="submit" class="btn btn-success">Add Item</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
            </form>
        </div>
        
        <!-- Menu Items Table -->
        <div class="admin-table" style="margin-top: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($menu_items && $menu_items->num_rows > 0):
                        while ($item = $menu_items->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>Rs. <?php echo number_format($item['price'], 0); ?></td>
                        <td>
                            <span class="status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span>
                            <?php if (!empty($item['is_popular'])): ?>
                                <span class="popular-indicator">⭐</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn btn-sm btn-primary" onclick="editItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', '<?php echo addslashes($item['description']); ?>', <?php echo $item['price']; ?>, <?php echo $item['category_id']; ?>, '<?php echo $item['status']; ?>')">Edit</button>
                            
                            <a href="menu-items.php?toggle_popular=<?php echo $item['id']; ?>&status=<?php echo $item['is_popular'] ? 0 : 1; ?>" class="btn btn-sm <?php echo $item['is_popular'] ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $item['is_popular'] ? '⭐ Unmark' : '⭐ Popular'; ?>
                            </a>
                            
                            <a href="menu-items.php?toggle_sold_out=<?php echo $item['id']; ?>&status=<?php echo $item['status'] === 'sold_out' ? 'active' : 'sold_out'; ?>" class="btn btn-sm <?php echo $item['status'] === 'sold_out' ? 'btn-success' : 'btn-danger'; ?>">
                                <?php echo $item['status'] === 'sold_out' ? '✓ In Stock' : '❌ Sold Out'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">No menu items found. Add some items to get started.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Edit Form (Hidden) -->
        <div id="editForm" class="form-card" style="display: none; margin-top: 30px;">
            <h2>Edit Menu Item</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="form-row">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="editName" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" id="editCategory" required>
                            <?php 
                            $categories = $conn->query("SELECT * FROM categories ORDER BY name");
                            while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Price (Rs.)</label>
                        <input type="number" name="price" id="editPrice" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Image (leave empty to keep current)</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editDescription" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="status" id="editStatus" checked> Active
                    </label>
                </div>
                <button type="submit" class="btn btn-success">Update Item</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('editForm').style.display='none'">Cancel</button>
            </form>
        </div>
    </section>
    
    <script>
        function editItem(id, name, description, price, category_id, status) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editPrice').value = price;
            document.getElementById('editCategory').value = category_id;
            document.getElementById('editStatus').checked = (status === 'active');
            document.getElementById('editForm').style.display = 'block';
            window.scrollTo({top: document.getElementById('editForm').offsetTop, behavior: 'smooth'});
        }
        
        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = 'menu-items.php?delete=' + id;
            }
        }
    </script>
</body>
</html>
