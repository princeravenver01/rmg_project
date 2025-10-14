<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Only admins and staff can view products
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    echo "<h2>Access Denied</h2></header><div class='card'><p>You do not have permission to view this page.</p></div>";
    require_once '../../src/templates/admin_footer.php';
    exit();
}
?>

<div class="content-header">
    <h2>Product Management</h2>
    <?php if ($_SESSION['role'] === 'admin'): // Only admins can add products ?>
    <a href="product_add.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Product</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['msg_type'] . '" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; background-color: ' . ($_SESSION['msg_type'] == 'success' ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;') . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    }
    ?>
    <style> /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 15px; text-align: left; vertical-align: middle; }
        th { font-weight: 600; font-size: 14px; color: var(--text-muted); }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Member Price</th>
                <th>SRP</th>
                <th>Points</th>
                <th>Stock</th>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
    <?php
    $sql = "SELECT * FROM products ORDER BY id DESC";
    $result = $mysqli->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($product = $result->fetch_assoc()) {
            $image_path = !empty($product['image_path']) ? '../' . $product['image_path'] : 'https://via.placeholder.com/60';
            echo "<tr>";
            echo '<td><img src="' . $image_path . '" alt="' . htmlspecialchars($product['name']) . '" class="product-image"></td>';
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>₱" . number_format($product['member_price'], 2) . "</td>";
            echo "<td>₱" . number_format($product['srp'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($product['points_value']) . "</td>";
            echo "<td>" . htmlspecialchars($product['stock_quantity']) . "</td>";
            if ($_SESSION['role'] === 'admin') {
                echo '<td>';
                echo '<a href="product_edit.php?id=' . $product['id'] . '" style="color: #3498db; text-decoration: none; margin-right: 15px;">Edit</a>';
                echo '<form action="product_handle_delete.php" method="POST" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this product? This action cannot be undone.\');">';
                echo '<input type="hidden" name="id" value="' . $product['id'] . '">';
                echo '<button type="submit" style="background: none; border: none; color: #e74c3c; cursor: pointer; padding: 0; font-size: inherit;">Delete</button>';
                echo '</form>';
                echo '</td>';
            }
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="7" style="text-align: center; padding: 20px;">No products found.</td></tr>';
    }
    $mysqli->close();
    ?>
</tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
?>