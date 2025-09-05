<?php
// models/Infaq.php
class Infaq {
    private $conn;
    private $table_name = "infaqs";

    public $id;
    public $infaq_date;
    public $description;
    public $donor_name;
    public $amount;
    public $type; // pemasukan, pengeluaran
    public $created_by_user_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Baca semua data infaq
    public function read() {
        // Order by infaq_date and then by id to ensure a consistent, reproducible order
        $query = "SELECT id, infaq_date, description, donor_name, amount, type FROM " . $this->table_name . " ORDER BY infaq_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Buat data infaq baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET infaq_date = :infaq_date, description = :description, donor_name = :donor_name, amount = :amount, type = :type, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        // PDO bindParam handles SQL injection prevention
        $stmt->bindParam(":infaq_date", $this->infaq_date);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":donor_name", $this->donor_name);
        $stmt->bindParam(":amount", $this->amount); // Amount should be a float/decimal, bind as is
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id);

        if ($stmt->execute()) {
            return true;
        }
        // Log the error details for debugging
        error_log("Infaq create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Perbarui data infaq
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET infaq_date = :infaq_date, description = :description, donor_name = :donor_name, amount = :amount, type = :type WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        $stmt->bindParam(":infaq_date", $this->infaq_date);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":donor_name", $this->donor_name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Infaq update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Hapus data infaq
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Ensure ID is an integer; PDO binding will also ensure this for the query.
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Infaq delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // New method to read unique donor names
    public function readUniqueDonorNames() {
        // Only select distinct names that are not null or empty
        $query = "SELECT DISTINCT donor_name FROM " . $this->table_name . " WHERE donor_name IS NOT NULL AND donor_name != '' ORDER BY donor_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
