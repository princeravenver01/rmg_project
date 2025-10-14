<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') { exit('Access Denied'); }
?>

<div class="content-header">
    <h2>Encashment Requests</h2>
</div>

<div class="card">
    <?php if(isset($_SESSION['message'])) { /* ... message display code ... */ } ?>
    <style> /* Table Styles */ </style>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Member</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT er.*, u.name AS member_name 
                    FROM encashment_requests er 
                    JOIN users u ON er.user_id = u.id 
                    ORDER BY er.status = 'pending' DESC, er.id DESC";
            $result = $mysqli->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($req = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . date('M d, Y', strtotime($req['requested_at'])) . "</td>";
                    echo "<td>" . htmlspecialchars($req['member_name']) . " (ID: " . $req['user_id'] . ")</td>";
                    echo "<td>₱" . number_format($req['amount'], 2) . "</td>";
                    echo "<td>" . ucfirst($req['status']) . "</td>";
                    echo '<td>';
                    if ($req['status'] === 'pending') {
                        echo '<form action="handle_encashment_action.php" method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="'.$req['id'].'">
                                <button type="submit" name="action" value="approve" style="color:green; border:none; background:none; cursor:pointer;">Approve</button>
                              </form> | ';
                        echo '<form action="handle_encashment_action.php" method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="'.$req['id'].'">
                                <button type="submit" name="action" value="decline" style="color:red; border:none; background:none; cursor:pointer;">Decline</button>
                              </form>';
                    } else {
                        echo 'Processed';
                    }
                    echo '</td>';
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="5" style="text-align: center;">No encashment requests found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>