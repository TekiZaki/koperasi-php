<?php
// models/User.php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password; // Stored hashed password
    public $name;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByUsername() {
        $query = "SELECT id, username, password, name, role FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind parameter for username
        // Using filter_var for basic sanitization, though PDO will handle quoting for SQL injection.
        // It's good practice to ensure input matches expected format.
        $clean_username = filter_var($this->username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $stmt->bindParam(':username', $clean_username);
        $stmt->execute();
        return $stmt;
    }

    public function findById() {
        $query = "SELECT id, username, name, role FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        // Bind parameter as integer to ensure type safety
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Creates a new user.
     * Use this method to register new users, ensuring password hashing.
     *
     * @return bool True on success, false on failure.
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, password, name, role) VALUES (:username, :password, :name, :role)";
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $clean_username = filter_var($this->username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_name = filter_var($this->name, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_role = filter_var($this->role, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        // Hash the password securely
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(':username', $clean_username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':name', $clean_name);
        $stmt->bindParam(':role', $clean_role);

        if ($stmt->execute()) {
            return true;
        }

        error_log("User creation error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Updates an existing user's details (excluding password, which should have its own method).
     *
     * @return bool True on success, false on failure.
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET username = :username, name = :name, role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $clean_username = filter_var($this->username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_name = filter_var($this->name, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_role = filter_var($this->role, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        $stmt->bindParam(':username', $clean_username);
        $stmt->bindParam(':name', $clean_name);
        $stmt->bindParam(':role', $clean_role);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        error_log("User update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Updates a user's password.
     *
     * @return bool True on success, false on failure.
     */
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Hash the new password securely
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        error_log("Password update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Deletes a user.
     *
     * @return bool True on success, false on failure.
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        error_log("User delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }
}
