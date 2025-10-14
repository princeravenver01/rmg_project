<?php
session_start();

// --- START OF FIX ---
// 1. Include the database connection FIRST. This file also loads the Composer autoloader and config.
require_once '../../src/includes/db_connect.php'; 

// 2. Perform all LOGIC before any HTML is output.
$generated_code_ids = $_SESSION['generated_codes'] ?? [];
unset($_SESSION['generated_codes']); // Clear the session variable immediately

// 3. Include the page header AFTER logic is complete.
require_once '../../src/templates/admin_header.php';

// 4. Handle the case where someone accesses the page directly.
if (empty($generated_code_ids)) {
    echo '<div class="card"><p>No recently generated codes to display. Please <a href="purchase_record.php">generate new codes</a> first.</p></div>';
    require_once '../../src/templates/admin_footer.php'; // Include footer for consistent layout
    exit(); // Stop the script
}
// --- END OF FIX ---
?>

<!-- =================================================================== -->
<!--               CORRECTED CSS WITH PRINT STYLES                       -->
<!-- =================================================================== -->
<style>
    .code-card {
        background: #fff; border: 1px solid #ddd; border-radius: 10px;
        padding: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 30px;
    }
    .qr-code img {
        width: 150px; height: 150px; border: 5px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .code-details h3 {
        margin: 0 0 10px 0; font-family: 'Courier New', Courier, monospace;
        font-size: 24px; color: var(--primary-color);
    }
    .code-details p { margin: 5px 0; color: #555; }
    
    /* --- THIS IS THE MISSING PRINT CSS --- */
    @media print {
        /* Hide all major layout elements by default */
        body > .admin-layout > .sidebar,
        body > .admin-layout > .main-content > .top-bar,
        .content-header {
            display: none !important;
        }
        /* Make the main content area take up the full page */
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        /* Remove shadows and borders from cards for a cleaner print */
        .card {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }
        /* Ensure each code card tries not to break across pages */
        .code-card {
            page-break-inside: avoid;
            border: 1px solid #ccc !important; /* Add a simple border for printing */
            box-shadow: none !important;
        }
    }
</style>

<div class="content-header">
    <h2>Activation Codes Generated Successfully</h2>
    <div>
        <a href="purchase_record.php" class="btn btn-secondary" style="background:#f1f1f1;">Generate More</a>
        <button onclick="window.print();" class="btn btn-primary"><i class="fa fa-print"></i> Print All</button>
    </div>
</div>

<?php
// Prepare a statement to fetch code details
$sql = "SELECT ac.code, ac.account_type, p.name as package_name 
        FROM activation_codes ac 
        JOIN packages p ON ac.package_id = p.id 
        WHERE ac.id = ?";
$stmt = $mysqli->prepare($sql);

foreach ($generated_code_ids as $code_id):
    $stmt->bind_param("i", $code_id);
    $stmt->execute();
    $code_data = $stmt->get_result()->fetch_assoc();
    
    // Pass just the code to the QR generator for simplicity and security
    $qr_data = $code_data['code'];
?>
    <div class="code-card">
        <div class="qr-code">
            <img src="qr_generator.php?data=<?php echo urlencode($qr_data); ?>" alt="QR Code for <?php echo htmlspecialchars($code_data['code']); ?>">
        </div>
        <div class="code-details">
            <h3><?php echo htmlspecialchars($code_data['code']); ?></h3>
            <p><strong>Package:</strong> <?php echo htmlspecialchars($code_data['package_name']); ?></p>
            <p><strong>Account Type:</strong> <?php echo htmlspecialchars($code_data['account_type']); ?></p>
            <p><strong>Status:</strong> <span style="color:green; font-weight:bold;">Available</span></p>
        </div>
    </div>
<?php 
endforeach; 
$stmt->close();
$mysqli->close();
?>

<?php require_once '../../src/templates/admin_footer.php'; ?>