<?php
// index.php
session_start();

// Autoload models
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/models/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once 'config/Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Security: Regenerate session ID to prevent Session Fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Security: Set a session timeout
$session_timeout = 1800; // 30 minutes (in seconds)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time

$database = new Database();
$db = $database->getConnection();

// Validate and sanitize the 'page' GET parameter
$allowed_pages = ['dashboard', 'transactions', 'savings', 'loans', 'infaq'];
$page = $_GET['page'] ?? 'dashboard';

if (!in_array($page, $allowed_pages)) {
    // If an invalid page is requested, default to dashboard or show an error
    $page = 'dashboard';
    // Optionally, log this attempt or redirect with an error message
    // error_log("Invalid page requested: " . ($_GET['page'] ?? ''));
}

include 'includes/header.php';

// Page routing
// The includes below are safe because $page has been validated against a whitelist.
switch ($page) {
    case 'transactions':
        include 'pages/transactions.php';
        break;
    case 'savings':
        include 'pages/savings.php';
        break;
    case 'loans':
        include 'pages/loans.php';
        break;
    case 'infaq':
        include 'pages/infaq.php';
        break;
    case 'dashboard':
    default:
        include 'pages/dashboard.php';
        break;
}

include 'includes/footer.php';
?>
