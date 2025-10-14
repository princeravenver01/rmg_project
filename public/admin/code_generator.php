<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// The header file includes its own session check, but we need an additional role check here.
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// --- CORRECTED SECURITY CHECK ---
// We check the role AFTER the header is included.
// We use strtolower() to make the check case-insensitive.
if (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'staff') {
    // Instead of a blank exit(), we display a proper message within the layout.
    echo '</div></header>'; // Close the header's opening tags
    echo '<div class="card"><p><strong>Access Denied:</strong> You do not have permission to view this page.</p></div>';
    require_once '../../src/templates/admin_footer.php';
    exit(); // Now we can exit safely.
}

// Fetch all packages for the dropdown
$packages_result = $mysqli->query("SELECT id, name, price FROM packages WHERE is_active = 1 ORDER BY name ASC");
?>

<style> /* Form Styles */
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select { width: 100%; max-width: 500px; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); }
    .form-actions { margin-top: 20px; }
</style>

<div class="content-header">
    <h2>Generate Activation Codes</h2>
</div>

<div class="card">
    <form action="code_handle_generate.php" method="POST">
        <div class="form-group">
            <label for="package_id">Select Package</label>
            <select id="package_id" name="package_id" required>
                <option value="">-- Choose a Package --</option>
                <?php
                while ($package = $packages_result->fetch_assoc()) {
                    echo '<option value="' . $package['id'] . '">';
                    echo htmlspecialchars($package['name']) . ' (₱' . number_format($package['price'], 2) . ')';
                    echo '</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="quantity">Number of Codes to Generate</label>
            <input type="number" id="quantity" name="quantity" min="1" max="100" value="1" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Generate Codes</button>
            <a href="activation_codes.php" class="btn btn-secondary" style="background:#f1f1f1">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>