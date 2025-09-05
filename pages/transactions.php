<?php
// pages/transactions.php
$transaction = new Transaction($db);
$stmt = $transaction->read();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique names for datalist
$transactionNamesStmt = $transaction->readUniqueNames();
$transactionNames = $transactionNamesStmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as a simple array of strings
?>

<div class="page-header">
    <h1>Kas Umum</h1>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addTransactionModal')">Tambah Transaksi</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <?php if (isAdmin()): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="<?php echo isAdmin() ? '6' : '5'; ?>" style="text-align: center;">Tidak ada data.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $row): ?>
                        <tr>
                            <td><?php echo date("d M Y", strtotime(htmlspecialchars($row['transaction_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['type'] == 'pemasukan' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['type'])); ?>
                                </span>
                            </td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['amount'], 0, ',', '.'); ?></td>
                            <?php if (isAdmin()): ?>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-warning" onclick="openEditTransactionModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                <form action="actions/handle_transaction.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div id="addTransactionModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addTransactionModal')">&times;</span>
        <h2>Tambah Transaksi Baru</h2>
        <form action="actions/handle_transaction.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="add_transaction_date">Tanggal Transaksi</label>
                <input type="date" id="add_transaction_date" name="transaction_date" required>
            </div>
            <div class="form-group">
                <label for="add_name">Nama (Opsional)</label>
                <input type="text" id="add_name" name="name" list="transactionNamesDatalist">
                <datalist id="transactionNamesDatalist">
                    <?php foreach ($transactionNames as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label for="add_description">Deskripsi</label>
                <input type="text" id="add_description" name="description" required>
            </div>
            <div class="form-group">
                <label for="add_type">Tipe</label>
                <select id="add_type" name="type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label for="add_amount">Jumlah</label>
                <input type="text" id="add_amount" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addTransactionModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="editTransactionModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editTransactionModal')">&times;</span>
        <h2>Edit Transaksi</h2>
        <form action="actions/handle_transaction.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="edit_transaction_date">Tanggal Transaksi</label>
                <input type="date" id="edit_transaction_date" name="transaction_date" required>
            </div>
            <div class="form-group">
                <label for="edit_name">Nama (Opsional)</label>
                <input type="text" id="edit_name" name="name" list="transactionNamesDatalist">
                <!-- Datalist is global -->
            </div>
            <div class="form-group">
                <label for="edit_description">Deskripsi</label>
                <input type="text" id="edit_description" name="description" required>
            </div>
            <div class="form-group">
                <label for="edit_type">Tipe</label>
                <select id="edit_type" name="type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_amount">Jumlah</label>
                <input type="text" id="edit_amount" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editTransactionModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
