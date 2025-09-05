<?php
// pages/infaq.php
$infaq = new Infaq($db);
$stmt = $infaq->read();
$infaqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique donor names for datalist
$donorNamesStmt = $infaq->readUniqueDonorNames();
$donorNames = $donorNamesStmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as a simple array of strings
?>

<div class="page-header">
    <h1>Kas Infaq</h1>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addInfaqModal')">Tambah Infaq</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Donatur</th>
                        <th>Deskripsi</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <?php if (isAdmin()): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($infaqs)): ?>
                        <tr><td colspan="<?php echo isAdmin() ? '6' : '5'; ?>" style="text-align: center;">Tidak ada data infaq.</td></tr>
                    <?php else: ?>
                        <?php foreach ($infaqs as $row): ?>
                        <tr>
                            <td><?php echo date("d M Y", strtotime(htmlspecialchars($row['infaq_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['type'] == 'pemasukan' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['type'])); ?>
                                </span>
                            </td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['amount'], 0, ',', '.'); ?></td>
                            <?php if (isAdmin()): ?>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-warning" onclick="openEditInfaqModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                <form action="actions/handle_infaq.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
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

<!-- Add Infaq Modal -->
<div id="addInfaqModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addInfaqModal')">&times;</span>
        <h2>Tambah Infaq</h2>
        <form action="actions/handle_infaq.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label>Tanggal</label>
                <input type="date" name="infaq_date" required>
            </div>
            <div class="form-group">
                <label>Nama Donatur (Opsional)</label>
                <input type="text" name="donor_name" list="donorNamesDatalist">
                <datalist id="donorNamesDatalist">
                    <?php foreach ($donorNames as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description" required>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <select name="type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah</label>
                <input type="text" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addInfaqModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Infaq Modal -->
<div id="editInfaqModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editInfaqModal')">&times;</span>
        <h2>Edit Infaq</h2>
        <form action="actions/handle_infaq.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_infaq_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label>Tanggal</label>
                <input type="date" name="infaq_date" id="edit_infaq_date" required>
            </div>
            <div class="form-group">
                <label>Nama Donatur (Opsional)</label>
                <input type="text" name="donor_name" id="edit_infaq_donor_name" list="donorNamesDatalist">
                <!-- Datalist is global, so it doesn't need to be repeated here -->
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description" id="edit_infaq_description" required>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <select name="type" id="edit_infaq_type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah</label>
                <input type="text" name="amount" id="edit_infaq_amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editInfaqModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
