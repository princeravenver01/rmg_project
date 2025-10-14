<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    exit('Access Denied');
}

$package_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($package_id <= 0) {
    header('Location: package_management.php');
    exit();
}

// Fetch main package data
$stmt = $mysqli->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package_result = $stmt->get_result();
if ($package_result->num_rows === 0) {
    exit('Package not found.');
}
$package = $package_result->fetch_assoc();
$stmt->close();

// Fetch included products for this package
$stmt_inclusions = $mysqli->prepare("SELECT product_id, quantity FROM package_products WHERE package_id = ?");
$stmt_inclusions->bind_param("i", $package_id);
$stmt_inclusions->execute();
$inclusions_result = $stmt_inclusions->get_result();
$included_products = [];
while ($row = $inclusions_result->fetch_assoc()) {
    $included_products[] = $row;
}
$stmt_inclusions->close();

// Fetch all products for the dropdowns
$products_result = $mysqli->query("SELECT id, name FROM products ORDER BY name ASC");
$all_products = [];
while ($row = $products_result->fetch_assoc()) {
    $all_products[] = $row;
}
?>

<style> /* Re-using form styles */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box; }
    .product-inclusion-row { display: flex; gap: 15px; align-items: center; margin-bottom: 10px; }
    .product-inclusion-row select { flex: 3; }
    .product-inclusion-row input { flex: 1; }
    .btn-danger { background: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
</style>

<div class="content-header">
    <h2>Edit Package</h2>
</div>

<div class="card">
    <form action="package_handle_edit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $package['id']; ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="name">Package Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($package['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Package Price (₱)</label>
                <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($package['price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="points_value">Points Value (PV)</label>
                <input type="number" id="points_value" name="points_value" value="<?php echo htmlspecialchars($package['points_value']); ?>" required>
            </div>
        </div>

        <hr style="margin: 30px 0; border-top: 1px solid var(--border-color);">
        <h3>Product Inclusions</h3>
        <div id="product-inclusions-container">
            <!-- Existing products will be loaded here -->
        </div>
        <button type="button" id="add-product-btn" class="btn btn-secondary" style="background: #f1f1f1;">+ Add Product</button>
        <hr style="margin: 30px 0;">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Package</button>
            <a href="package_management.php" class="btn btn-secondary" style="background:#f1f1f1">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('product-inclusions-container');
    const addProductBtn = document.getElementById('add-product-btn');
    const allProducts = <?php echo json_encode($all_products); ?>;
    const includedProducts = <?php echo json_encode($included_products); ?>;

    function createProductRow(productId = '', quantity = 1) {
        const row = document.createElement('div');
        row.className = 'product-inclusion-row';

        const select = document.createElement('select');
        select.name = 'product_id[]';
        select.required = true;
        
        let options = '<option value="">-- Select a Product --</option>';
        allProducts.forEach(p => {
            const isSelected = p.id == productId ? 'selected' : '';
            options += `<option value="${p.id}" ${isSelected}>${p.name}</option>`;
        });
        select.innerHTML = options;

        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.name = 'quantity[]';
        quantityInput.placeholder = 'Qty';
        quantityInput.min = '1';
        quantityInput.value = quantity;
        quantityInput.required = true;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn-danger';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = () => container.removeChild(row);

        row.appendChild(select);
        row.appendChild(quantityInput);
        row.appendChild(removeBtn);
        container.appendChild(row);
    }

    // Load existing products into the form
    if (includedProducts.length > 0) {
        includedProducts.forEach(item => {
            createProductRow(item.product_id, item.quantity);
        });
    } else {
        // If package has no products, start with one empty row
        createProductRow();
    }

    addProductBtn.addEventListener('click', () => createProductRow());
});
</script>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>