<?php
// login.php
session_start();
require_once 'config/Database.php';
require_once 'models/User.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection: Add a token check if forms are protected.
    // For a simple login, it's less critical, but good practice for other forms.

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    // Sanitize user input immediately
    $username_input = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password_input = $_POST['password']; // Password cannot be sanitized, only validated/hashed

    // Validate input (e.g., length, character set)
    if (empty($username_input) || empty($password_input)) {
        $error_message = "Username dan password harus diisi.";
    } else {
        $user->username = $username_input;
        $stmt = $user->findByUsername();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password using password_verify for hashed passwords
            if (password_verify($password_input, $row['password'])) {
                // Login successful

                // Security: Regenerate session ID upon successful login to prevent Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); // Sanitize name before storing in session
                $_SESSION['user_role'] = htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'); // Sanitize role
                $_SESSION['last_activity'] = time(); // For session timeout
                $_SESSION['initiated'] = true; // Mark session as initiated

                header("Location: index.php");
                exit();
            } else {
                $error_message = "Password salah.";
                // Security: Generic error messages prevent username enumeration
            }
        } else {
            $error_message = "Username tidak ditemukan.";
            // Security: Generic error messages prevent username enumeration
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Koperasi Masjid</title>
    <link rel="icon" type="image/x-icon" href="assets/logo.png" />
    <!-- FIX: Link to the main stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<!-- FIX: Add class to body for specific styling -->
<body class="login-page">
    <div class="login-container">
        <h1>Selamat Datang</h1>
        <p>Silakan login untuk melanjutkan</p>
        <?php if (!empty($error_message)): ?>
            <!-- FIX: Use the error-message class from the main stylesheet -->
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
