<?php
session_start();
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: encashment_requests.php');
    exit();
}

$request_id = (int)$_POST['request_id'];
$action = $_POST['action'];
$admin_id = $_SESSION['user_id'];

if ($request_id <= 0 || ($action !== 'approve' && $action !== 'decline')) {
    header('Location: encashment_requests.php');
    exit();
}

if ($action === 'approve') {
    // Just update the status. The money was already deducted from the wallet.
    $stmt = $mysqli->prepare("UPDATE encashment_requests SET status = 'approved', processed_at = NOW(), processed_by_id = ? WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $admin_id, $request_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Request approved."; $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Could not process request."; $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();

} elseif ($action === 'decline') {
    // If declined, we must return the money to the member's wallet (using a transaction)
    $mysqli->begin_transaction();
    try {
        // 1. Get the request details
        $stmt_get = $mysqli->prepare("SELECT user_id, amount FROM encashment_requests WHERE id = ? AND status = 'pending'");
        $stmt_get->bind_param("i", $request_id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        if ($req = $result->fetch_assoc()) {
            $user_id = $req['user_id'];
            $amount = $req['amount'];

            // 2. Update the request status
            $stmt_update = $mysqli->prepare("UPDATE encashment_requests SET status = 'declined', processed_at = NOW(), processed_by_id = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $admin_id, $request_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 3. Refund the amount to the wallet
            $stmt_refund = $mysqli->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
            $stmt_refund->bind_param("di", $amount, $user_id);
            $stmt_refund->execute();
            $stmt_refund->close();

            $mysqli->commit();
            $_SESSION['message'] = "Request declined and funds returned to member's wallet.";
            $_SESSION['msg_type'] = "success";

        } else {
            throw new Exception("Request not found or already processed.");
        }
        $stmt_get->close();

    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}

$mysqli->close();
header('Location: encashment_requests.php');
exit();