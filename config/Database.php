<?php
// config/Database.php
class Database {
    private $host = "localhost";
    private $db_name = "koperasi_php";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            // Set error mode to exception for better error handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Disable emulated prepares for stronger type checking and prevention of SQL injection
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            // Log connection error instead of echoing it directly
            error_log("Connection error: " . $exception->getMessage(), 0);
            // You might want to throw a custom exception or redirect to an error page
            // For now, re-throwing the PDOException
            throw new Exception("Database connection failed. Please try again later.");
        }
        return $this->conn;
    }
}
