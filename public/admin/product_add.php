<?php
session_start();
require_once '../../src/templates/admin_header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2></header><div class='card'><p>You do not have permission.</p></div>";
    require_once '../../src/templates/admin_footer.php';
    exit();
}
?>

<style> /* Re-using form styles */
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-muted); }
    .form-group input, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box; }
    .form-actions { margin-top: 20px; display: flex; gap: 10px; }
    .btn-secondary { background-color: #f1f1f1; color: var(--text-color); }
</style>

<div class="content-header">
    <h2>Add New Product</h2>
</div>

<div class="card">
    <!-- IMPORTANT: enctype is required for file uploads -->
    <form action="product_handle_add.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="member_price">Member Price (₱)</label>
                <input type="number" step="0.01" id="member_price" name="member_price" required>
            </div>
            <div class="form-group">
                <label for="srp">Suggested Retail Price (₱)</label>
                <input type="number" step="0.01" id="srp" name="srp" required>
            </div>
            <div class="form-group">
                <label for="points_value">Points Value (PV)</label>
                <input type="number" id="points_value" name="points_value" required>
            </div>
            <div class="form-group">
    <label for="unilevel_bonus">Unilevel Bonus Amount (₱)</label>
    <input type="number" step="0.01" id="unilevel_bonus" name="unilevel_bonus" value="0.00" required>
</div>
             <div class="form-group">
                <label for="stock_quantity">Initial Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="0" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="manufacturer">Manufacturer</label>
                <input type="text" id="manufacturer" name="manufacturer">
            </div>
            <div class="form-group">
                <label for="barcode">Barcode</label>
                <input type="text" id="barcode" name="barcode">
            </div>
        </div>

        <div class="form-group">
            <label for="product_image">Product Image</label>
            <input type="file" id="product_image" name="product_image" accept="image/png, image/jpeg">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <a href="product_management.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
?>