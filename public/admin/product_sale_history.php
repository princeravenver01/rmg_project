<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') { exit('Access Denied'); }
?>

<div class="content-header">
    <h2>Product Sale History (POS)</h2>
    <p>A log of all individual product sales processed through the Point of Sale.</p>
</div>

<div class="card">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 12px; text-align: left; vertical-align: middle; }
        th { font-weight: 600; font-size: 14px; color: var(--text-muted); }
        .details-link { text-decoration: none; }
    </style>
    <table>
        <thead>
            <tr>
                <th>Sale ID</th>
                <th>Date</th>
                <th>Member</th>
                <th>Total Amount</th>
                <th>Total Points</th>
                <th>Payment Method</th>
                <th>Processed By</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // This query joins all the necessary tables to get the names
            $sql = "SELECT ps.*, u.name as member_name, admin.name as admin_name
                    FROM product_sales ps
                    JOIN users admin ON ps.processed_by_id = admin.id
                    JOIN users u ON ps.member_id = u.id
                    ORDER BY ps.id DESC";
            $result = $mysqli->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>RMG-SALE-" . str_pad($row['id'], 6, '0', STR_PAD_LEFT) . "</td>";
                    echo "<td>" . date('M d, Y h:i A', strtotime($row['created_at'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['member_name']) . " (ID: " . $row['member_id'] . ")</td>";
                    echo "<td>₱" . number_format($row['total_amount'], 2) . "</td>";
                    echo "<td>" . htmlspecialchars($row['total_points']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['payment_method']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
                    // Link to the receipt page to view details
                    echo '<td><a href="generate_receipt.php?sale_id=' . $row['id'] . '" class="details-link" target="_blank">View Receipt</a></td>';
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="8" style="text-align: center; padding: 20px;">No product sales have been recorded yet.</td></tr>';
            }
            $mysqli->close();
            ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
?>