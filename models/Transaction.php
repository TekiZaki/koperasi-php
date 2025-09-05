<?php
// models/Transaction.php
class Transaction {
    private $conn;
    private $table_name = "transactions";

    public $id;
    public $transaction_date;
    public $name;
    public $description;
    public $type;
    public $amount;
    public $created_by_user_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT id, transaction_date, name, description, type, amount FROM " . $this->table_name . " ORDER BY transaction_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET transaction_date = :transaction_date, name = :name, description = :description, type = :type, amount = :amount, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        $stmt->bindParam(":transaction_date", $this->transaction_date);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET transaction_date = :transaction_date, name = :name, description = :description, type = :type, amount = :amount WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here
        $stmt->bindParam(":transaction_date", $this->transaction_date);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // New method to read unique names from transactions
    public function readUniqueNames() {
        $query = "SELECT DISTINCT name FROM " . $this->table_name . " WHERE name IS NOT NULL AND name != '' ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
