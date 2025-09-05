<?php
// crud.php
require_once "config/Database.php";

$db = new Database();
$conn = $db->getConnection();

// Handle Add User
if (isset($_POST['add'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $name = $_POST['name'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password, $name, $role]);
    // Redirect back to crud.php after adding
    header("Location: crud.php");
    exit;
}

// Handle Edit User
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $name = $_POST['name'];
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, name=?, role=? WHERE id=?");
        $stmt->execute([$username, $password, $name, $role, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, name=?, role=? WHERE id=?");
        $stmt->execute([$username, $name, $role, $id]);
    }

    // Redirect back to crud.php after editing
    header("Location: crud.php");
    exit;
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    // Redirect back to crud.php after deleting
    header("Location: crud.php");
    exit;
}

// Fetch all users
$stmt = $conn->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='80' font-size='80'>👥</text></svg>">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4 text-center">👥 User Management</h2>

    <!-- Add User Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">Add New User</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" name="add" class="btn btn-success">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Users List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th style="min-width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <form method="POST" class="row g-2">
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td>
                                <input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </td>
                            <td>
                                <select name="role" class="form-select form-select-sm">
                                    <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                                    <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                                    <option value="superadmin" <?= $user['role']=='superadmin'?'selected':'' ?>>Superadmin</option>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="New Password (optional)">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="edit" class="btn btn-warning btn-sm">Update</button>
                                    <a href="crud.php?delete=<?= $user['id'] ?>" onclick="return confirm('Delete this user?');" class="btn btn-danger btn-sm">Delete</a>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>