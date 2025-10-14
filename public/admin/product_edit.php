<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Security: Only admins can edit
if ($_SESSION['role'] !== 'admin') {
    header('Location: product_management.php');
    exit();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: product_management.php');
    exit();
}

// Fetch product data
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $product = $result->fetch_assoc();
} else {
    $_SESSION['message'] = "Product not found.";
    $_SESSION['msg_type'] = "danger";
    header('Location: product_management.php');
    exit();
}
$stmt->close();
?>

<style> /* Re-using form styles */
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-muted); }
    .form-group input, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box; }
    .form-actions { margin-top: 20px; display: flex; gap: 10px; }
    .btn-secondary { background-color: #f1f1f1; color: var(--text-color); }
    .current-image-preview { max-width: 100px; max-height: 100px; border-radius: 8px; margin-top: 10px; }
</style>

<div class="content-header">
    <h2>Edit Product</h2>
</div>

<div class="card">
    <form action="product_handle_edit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
        <input type="hidden" name="current_image" value="<?php echo $product['image_path']; ?>">

        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="member_price">Member Price (₱)</label>
                <input type="number" step="0.01" id="member_price" name="member_price" value="<?php echo htmlspecialchars($product['member_price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="srp">Suggested Retail Price (₱)</label>
                <input type="number" step="0.01" id="srp" name="srp" value="<?php echo htmlspecialchars($product['srp']); ?>" required>
            </div>
            <div class="form-group">
                <label for="points_value">Points Value (PV)</label>
                <input type="number" id="points_value" name="points_value" value="<?php echo htmlspecialchars($product['points_value']); ?>" required>
            </div>
            <div class="form-group">
    <label for="unilevel_bonus">Unilevel Bonus Amount (₱)</label>
    <input type="number" step="0.01" id="unilevel_bonus" name="unilevel_bonus" value="<?php echo htmlspecialchars($product['unilevel_bonus']); ?>" required>
</div>
             <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="manufacturer">Manufacturer</label>
                <input type="text" id="manufacturer" name="manufacturer" value="<?php echo htmlspecialchars($product['manufacturer']); ?>">
            </div>
            <div class="form-group">
                <label for="barcode">Barcode</label>
                <input type="text" id="barcode" name="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="product_image">Change Product Image</label>
            <input type="file" id="product_image" name="product_image" accept="image/png, image/jpeg">
            <?php if (!empty($product['image_path'])): ?>
                <p style="margin-top: 10px; color: var(--text-muted);">Current Image:</p>
                <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" alt="Current Image" class="current-image-preview">
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="product_management.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>