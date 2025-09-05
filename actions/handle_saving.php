<?php
// actions/handle_saving.php
session_start();
require_once '../config/Database.php';
require_once '../models/Saving.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    error_log("Unauthorized saving action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=savings&error=unauthorized");
    exit();
}

// CSRF Protection: Verify token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token mismatch for saving action from user ID: " . $_SESSION['user_id']);
        header("Location: ../index.php?page=savings&error=csrf_mismatch");
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();
$saving = new Saving($db);

// Sanitize and validate action
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Input validation and sanitization
        $saving->saving_date = filter_input(INPUT_POST, 'saving_date', FILTER_SANITIZE_STRING);
        $saving->member_name = filter_input(INPUT_POST, 'member_name', FILTER_SANITIZE_STRING);
        $saving->saving_type = filter_input(INPUT_POST, 'saving_type', FILTER_SANITIZE_STRING);
        $saving->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $saving->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

        // Basic validation
        if (empty($saving->saving_date) || empty($saving->member_name) || empty($saving->saving_type) || !is_numeric($saving->amount) || $saving->amount <= 0) {
            throw new Exception("Invalid input for creating saving.");
        }
        if (!in_array($saving->saving_type, ['wajib', 'sukarela'])) {
            throw new Exception("Invalid saving type.");
        }

        $saving->created_by_user_id = $_SESSION['user_id'];
        $saving->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $saving->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $saving->saving_date = filter_input(INPUT_POST, 'saving_date', FILTER_SANITIZE_STRING);
        $saving->member_name = filter_input(INPUT_POST, 'member_name', FILTER_SANITIZE_STRING);
        $saving->saving_type = filter_input(INPUT_POST, 'saving_type', FILTER_SANITIZE_STRING);
        $saving->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $saving->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

        // Basic validation
        if (empty($saving->id) || empty($saving->saving_date) || empty($saving->member_name) || empty($saving->saving_type) || !is_numeric($saving->amount) || $saving->amount <= 0) {
            throw new Exception("Invalid input for updating saving.");
        }
        if (!in_array($saving->saving_type, ['wajib', 'sukarela'])) {
            throw new Exception("Invalid saving type.");
        }

        $saving->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $saving->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($saving->id)) {
            throw new Exception("Invalid ID for deleting saving.");
        }
        $saving->delete();
    } else {
        throw new Exception("Invalid action or request method.");
    }
} catch (Exception $e) {
    error_log("Saving action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=savings&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=savings&status=success");
exit();
