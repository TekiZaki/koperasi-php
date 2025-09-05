<?php
// actions/handle_transaction.php
session_start();
require_once '../config/Database.php';
require_once '../models/Transaction.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    error_log("Unauthorized transaction action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=transactions&error=unauthorized");
    exit();
}

// CSRF Protection: Verify token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token mismatch for transaction action from user ID: " . $_SESSION['user_id']);
        header("Location: ../index.php?page=transactions&error=csrf_mismatch");
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();
$transaction = new Transaction($db);

// Sanitize and validate action
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Input validation and sanitization
        $transaction->transaction_date = filter_input(INPUT_POST, 'transaction_date', FILTER_SANITIZE_STRING);
        $transaction->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $transaction->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $transaction->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $transaction->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($transaction->transaction_date) || empty($transaction->description) || empty($transaction->type) || !is_numeric($transaction->amount) || $transaction->amount <= 0) {
            throw new Exception("Invalid input for creating transaction.");
        }
        if (!in_array($transaction->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid transaction type.");
        }

        $transaction->created_by_user_id = $_SESSION['user_id'];
        $transaction->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $transaction->transaction_date = filter_input(INPUT_POST, 'transaction_date', FILTER_SANITIZE_STRING);
        $transaction->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $transaction->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $transaction->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $transaction->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($transaction->id) || empty($transaction->transaction_date) || empty($transaction->description) || empty($transaction->type) || !is_numeric($transaction->amount) || $transaction->amount <= 0) {
            throw new Exception("Invalid input for updating transaction.");
        }
        if (!in_array($transaction->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid transaction type.");
        }

        $transaction->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($transaction->id)) {
            throw new Exception("Invalid ID for deleting transaction.");
        }
        $transaction->delete();
    } else {
        throw new Exception("Invalid action or request method.");
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Transaction action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=transactions&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=transactions&status=success");
exit();
