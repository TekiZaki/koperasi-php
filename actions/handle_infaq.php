<?php
// actions/handle_infaq.php
session_start();
require_once '../config/Database.php';
require_once '../models/Infaq.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    // Log unauthorized attempt
    error_log("Unauthorized infaq action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=infaq&error=unauthorized");
    exit();
}

// CSRF Protection: Verify token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Log CSRF attempt
        error_log("CSRF token mismatch for infaq action from user ID: " . $_SESSION['user_id']);
        header("Location: ../index.php?page=infaq&error=csrf_mismatch");
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();
$infaq = new Infaq($db);

// Sanitize and validate action
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Input validation and sanitization
        $infaq->infaq_date = filter_input(INPUT_POST, 'infaq_date', FILTER_SANITIZE_STRING);
        $infaq->donor_name = filter_input(INPUT_POST, 'donor_name', FILTER_SANITIZE_STRING);
        $infaq->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $infaq->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $infaq->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($infaq->infaq_date) || empty($infaq->description) || empty($infaq->type) || !is_numeric($infaq->amount) || $infaq->amount <= 0) {
            throw new Exception("Invalid input for creating infaq.");
        }
        if (!in_array($infaq->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid infaq type.");
        }

        $infaq->created_by_user_id = $_SESSION['user_id'];
        $infaq->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $infaq->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $infaq->infaq_date = filter_input(INPUT_POST, 'infaq_date', FILTER_SANITIZE_STRING);
        $infaq->donor_name = filter_input(INPUT_POST, 'donor_name', FILTER_SANITIZE_STRING);
        $infaq->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $infaq->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $infaq->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($infaq->id) || empty($infaq->infaq_date) || empty($infaq->description) || empty($infaq->type) || !is_numeric($infaq->amount) || $infaq->amount <= 0) {
            throw new Exception("Invalid input for updating infaq.");
        }
        if (!in_array($infaq->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid infaq type.");
        }

        $infaq->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $infaq->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($infaq->id)) {
            throw new Exception("Invalid ID for deleting infaq.");
        }
        $infaq->delete();
    } else {
        throw new Exception("Invalid action or request method.");
    }
} catch (Exception $e) {
    error_log("Infaq action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=infaq&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=infaq&status=success");
exit();
?>
