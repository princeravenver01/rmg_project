<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') { exit('Access Denied'); }

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
if ($sale_id <= 0) { die("Invalid Sale ID."); }

// Fetch sale details
$stmt_sale = $mysqli->prepare("SELECT ps.*, u.name as member_name, u.username as member_username, admin.name as admin_name FROM product_sales ps JOIN users u ON ps.member_id = u.id JOIN users admin ON ps.processed_by_id = admin.id WHERE ps.id = ?");
$stmt_sale->bind_param("i", $sale_id);
$stmt_sale->execute();
$sale = $stmt_sale->get_result()->fetch_assoc();
$stmt_sale->close();

if (!$sale) { die("Sale not found."); }

// Fetch sale line items
$stmt_items = $mysqli->prepare("SELECT psi.*, p.name as product_name FROM product_sale_items psi JOIN products p ON psi.product_id = p.id WHERE psi.sale_id = ?");
$stmt_items->bind_param("i", $sale_id);
$stmt_items->execute();
$items = $stmt_items->get_result();
?>

<!-- =================================================================== -->
<!--               NEW CSS FOR THERMAL PRINTER RECEIPT STYLE             -->
<!-- =================================================================== -->
<style>
    .receipt-wrapper {
        width: 100%;
        max-width: 400px; /* Typical width for thermal receipts */
        margin: 0 auto;
        font-family: 'Courier New', Courier, monospace; /* Monospaced font */
        font-size: 14px;
        color: #000;
        background: #fff;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .receipt-header, .receipt-footer { text-align: center; }
    .receipt-header h2 { margin: 0; font-size: 20px; }
    .receipt-header p { margin: 5px 0; }

    .receipt-section {
        margin: 20px 0;
        padding: 15px 0;
        border-top: 1px dashed #000;
        border-bottom: 1px dashed #000;
    }
    .receipt-section div { margin-bottom: 5px; }

    .receipt-table { width: 100%; }
    .receipt-table th, .receipt-table td { padding: 5px 0; }
    .receipt-table th { text-align: left; border-bottom: 1px solid #000; }
    .receipt-table .qty, .receipt-table .price { text-align: right; }
    .receipt-table .total { font-weight: bold; text-align: right;}
    .receipt-table .sub-item { padding-left: 15px; font-size: 12px; }

    .receipt-totals { margin-top: 20px; }
    .receipt-totals div { display: flex; justify-content: space-between; padding: 3px 0; }
    .receipt-totals .grand-total { font-size: 18px; font-weight: bold; border-top: 2px solid #000; margin-top: 5px; padding-top: 5px; }

    .receipt-footer { margin-top: 30px; font-size: 12px; }

    @media print {
        body * { visibility: hidden; }
        .receipt-wrapper, .receipt-wrapper * { visibility: visible; }
        .receipt-wrapper {
            position: absolute; left: 0; top: 0; margin: 0; padding: 0; width: 100%;
            box-shadow: none; border: none; font-size: 12px;
        }
        .btn, .content-header, .sidebar, .top-bar { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: none !important; }
    }
</style>

<div class="content-header">
    <h2>Transaction Receipt</h2>
    <button onclick="window.print();" class="btn btn-primary"><i class="fa fa-print"></i> Print Receipt</button>
</div>

<div class="card">
    <div class="receipt-wrapper">
        <div class="receipt-header">
            <h2>RMG Corporation</h2>
            <p>123 Business Rd, Makati, Metro Manila</p>
            <p>VAT REG TIN: 000-000-000-000</p>
            <p>OFFICIAL RECEIPT</p>
        </div>

        <div class="receipt-section">
            <div><strong>Date:</strong> <?php echo date("Y-m-d H:i:s", strtotime($sale['created_at'])); ?></div>
            <div><strong>Sale ID:</strong> <?php echo str_pad($sale['id'], 8, '0', STR_PAD_LEFT); ?></div>
            <div><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['admin_name']); ?></div>
            <div><strong>Member:</strong> <?php echo htmlspecialchars($sale['member_name']); ?></div>
            <div><strong>Username:</strong> <?php echo htmlspecialchars($sale['member_username']); ?></div>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>ITEM</th>
                    <th class="qty">QTY</th>
                    <th class="price">PRICE</th>
                    <th class="total">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                while($item = $items->fetch_assoc()): 
                    $line_total = $item['price_per_item'] * $item['quantity'];
                    $subtotal += $line_total;
                ?>
                <tr>
                    <td colspan="4"><?php echo htmlspecialchars($item['product_name']); ?></td>
                </tr>
                <tr>
                    <td class="sub-item"></td>
                    <td class="qty"><?php echo $item['quantity']; ?> x</td>
                    <td class="price"><?php echo number_format($item['price_per_item'], 2); ?></td>
                    <td class="total">₱<?php echo number_format($line_total, 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="receipt-totals">
            <?php
            // VAT Calculation
            $vat_rate = 1.12;
            $vatable_sales = $subtotal / $vat_rate;
            $vat_amount = $subtotal - $vatable_sales;
            ?>
            <div>
                <span>VATable Sales:</span>
                <span>₱<?php echo number_format($vatable_sales, 2); ?></span>
            </div>
            <div>
                <span>VAT (12%):</span>
                <span>₱<?php echo number_format($vat_amount, 2); ?></span>
            </div>
            <div class="grand-total">
                <span>TOTAL:</span>
                <span>₱<?php echo number_format($sale['total_amount'], 2); ?></span>
            </div>
            <div style="margin-top: 10px;">
                <span>Payment Method:</span>
                <span><?php echo htmlspecialchars($sale['payment_method']); ?></span>
            </div>
            <!-- You can add Cash Tendered and Change here if you store them in the DB -->
        </div>

        <div class="receipt-section" style="border-bottom: none;">
            <div style="font-weight: bold; text-align:center;">
                Points Awarded This Transaction: <?php echo htmlspecialchars($sale['total_points']); ?> PV
            </div>
        </div>

        <div class="receipt-footer">
            <p>Thank you for your purchase!</p>
            <p>This is not a sales invoice.</p>
        </div>
    </div>
</div>

<?php require_once '../../src/templates/admin_footer.php'; ?>