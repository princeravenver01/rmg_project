<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    exit('Access Denied');
}

// Fetch all products to populate the dropdowns
$products_result = $mysqli->query("SELECT id, name FROM products ORDER BY name ASC");
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}
?>

<style> /* Form Styles */
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
    <h2>Add New Package</h2>
</div>

<div class="card">
    <form action="package_handle_add.php" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Package Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="price">Package Price (₱)</label>
                <input type="number" step="0.01" id="price" name="price" required>
            </div>
            <div class="form-group">
                <label for="points_value">Points Value (PV)</label>
                <input type="number" id="points_value" name="points_value" required>
            </div>
        </div>

        <hr style="margin: 30px 0; border-top: 1px solid var(--border-color);">

        <h3>Product Inclusions</h3>
        <div id="product-inclusions-container">
            <!-- Product rows will be added here by JavaScript -->
        </div>
        <button type="button" id="add-product-btn" class="btn btn-secondary" style="background: #f1f1f1;">+ Add Product</button>

        <hr style="margin: 30px 0;">

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Package</button>
            <a href="package_management.php" class="btn btn-secondary" style="background:#f1f1f1">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('product-inclusions-container');
    const addProductBtn = document.getElementById('add-product-btn');
    const products = <?php echo json_encode($products); ?>;

    function createProductRow() {
        const row = document.createElement('div');
        row.className = 'product-inclusion-row';

        const select = document.createElement('select');
        select.name = 'product_id[]';
        select.required = true;
        let options = '<option value="">-- Select a Product --</option>';
        products.forEach(p => {
            options += `<option value="${p.id}">${p.name}</option>`;
        });
        select.innerHTML = options;

        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.name = 'quantity[]';
        quantityInput.placeholder = 'Qty';
        quantityInput.min = '1';
        quantityInput.value = '1';
        quantityInput.required = true;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn-danger';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = function() {
            container.removeChild(row);
        };

        row.appendChild(select);
        row.appendChild(quantityInput);
        row.appendChild(removeBtn);
        container.appendChild(row);
    }

    addProductBtn.addEventListener('click', createProductRow);

    // Add one row by default to start
    createProductRow();
});
</script>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>