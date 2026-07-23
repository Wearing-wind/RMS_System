<?php
// Admin Categories Management
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
            
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = 'Category added successfully';
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success'] = 'Category updated successfully';
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

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - QR Restaurant Admin</title>
    <link rel="stylesheet" href="../css/modern.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f6fa; }
        .admin-header { background: linear-gradient(135deg, #ff6b35, #ff8c5a); padding: 15px 0; }
        .admin-header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-logo { color: white; font-size: 1.5rem; font-weight: bold; text-decoration: none; }
        .admin-nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-nav a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 10px 18px; border-radius: 25px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); color: white; }
        .admin-content { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .admin-content h1 { color: #2d3436; margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-primary { background: #ff6b35; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-card h2 { margin-top: 0; color: #2d3436; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #2d3436; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 2px solid #dfe6e9; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #ff6b35; }
        .admin-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .admin-table table { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #ff6b35; color: white; padding: 15px; text-align: left; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #dfe6e9; }
        .admin-table tr:hover { background: #f8f9fa; }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <a href="index.php" class="admin-logo">🍽️ Admin Panel</a>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="menu-items.php">Menu Items</a>
                <a href="categories.php" class="active">Categories</a>
                <a href="tables.php">Tables & QR</a>
                <a href="orders.php">Orders</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Admin Content -->
    <section class="admin-content">
        <h1>Categories Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <!-- Add New Button -->
        <button class="btn btn-primary" onclick="document.getElementById('addForm').style.display='block'">
            + Add Category
        </button>
        
        <!-- Add Form -->
        <div id="addForm" class="form-card" style="display: none; margin-top: 20px;">
            <h2>Add New Category</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Add Category</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
            </form>
        </div>
        
        <!-- Categories Table -->
        <div class="admin-table" style="margin-top: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td><?php echo htmlspecialchars($cat['description'] ?: '-'); ?></td>
                        <td class="actions">
                            <button type="button" class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo addslashes($cat['description']); ?>')">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $cat['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Edit Form (Hidden) -->
        <div id="editForm" class="form-card" style="display: none; margin-top: 30px;">
            <h2>Edit Category</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="editName" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editDescription" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Update Category</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('editForm').style.display='none'">Cancel</button>
            </form>
        </div>
    </section>
    
    <script>
        function editCategory(id, name, description) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editForm').style.display = 'block';
            window.scrollTo({top: document.getElementById('editForm').offsetTop, behavior: 'smooth'});
        }
        
        function deleteCategory(id) {
            if (confirm('Are you sure? This will also delete all menu items in this category.')) {
                window.location.href = 'categories.php?delete=' + id;
            }
        }
    </script>
</body>
</html>
