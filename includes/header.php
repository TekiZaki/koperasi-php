<?php
// includes/header.php
// This check should be at the top of any protected page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Regenerate session ID every now and then to increase security (e.g., every 5 minutes)
// This helps mitigate session hijacking by making old session IDs invalid.
if (!isset($_SESSION['last_regenerate'])) {
    $_SESSION['last_regenerate'] = time();
} elseif (time() - $_SESSION['last_regenerate'] > 300) { // Regenerate every 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regenerate'] = time();
}

$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'user';

// Generate a CSRF token if one doesn't exist (for forms)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to check for admin roles
function isAdmin() {
    return in_array($_SESSION['user_role'], ['admin', 'superadmin']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Koperasi</title>
    <link rel="icon" type="image/x-icon" href="assets/logo.png" />
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
</head>
<body data-user-role="<?php echo htmlspecialchars($user_role); ?>">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="app-header">
                <button class="menu-toggle" id="menu-toggle">
                    &#9776; <!-- Hamburger Icon -->
                </button>
                <div class="user-info">
                    <span>Selamat Datang, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </header>
            <main class="content-body">

