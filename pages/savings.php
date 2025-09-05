<?php
// pages/savings.php
$saving = new Saving($db);
$stmt = $saving->readGroupedByMember();
$savings_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique member names for datalist
$memberNamesStmt = $saving->readUniqueMemberNames();
$memberNames = $memberNamesStmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as a simple array of strings
?>

<div class="page-header">
    <h1>Simpanan Anggota</h1>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addSavingModal')">Tambah Simpanan</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nama Anggota</th>
                        <th>Total Simpanan</th>
                        <th>Jumlah Transaksi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($savings_summary)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Tidak ada data simpanan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($savings_summary as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['total_amount'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($row['transaction_count']); ?></td>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-info" onclick="openSavingDetailModal('<?php echo htmlspecialchars($row['member_name'], ENT_QUOTES); ?>')">Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Saving Modal -->
<div id="addSavingModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addSavingModal')">&times;</span>
        <h2>Tambah Simpanan</h2>
        <form action="actions/handle_saving.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="add_saving_date">Tanggal</label>
                <input type="date" name="saving_date" required>
            </div>
            <div class="form-group">
                <label for="add_member_name">Nama Anggota</label>
                <input type="text" name="member_name" list="memberNamesDatalist" required>
                <datalist id="memberNamesDatalist">
                    <?php foreach ($memberNames as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label for="add_saving_type">Tipe Simpanan</label>
                <select name="saving_type" required>
                    <option value="wajib">Wajib</option>
                    <option value="sukarela">Sukarela</option>
                </select>
            </div>
            <div class="form-group">
                <label for="add_amount">Jumlah</label>
                <input type="text" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-group">
                <label for="add_description">Deskripsi</label>
                <input type="text" name="description">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSavingModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Saving Modal -->
<div id="editSavingModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editSavingModal')">&times;</span>
        <h2>Edit Simpanan</h2>
        <form action="actions/handle_saving.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_saving_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="edit_saving_date">Tanggal</label>
                <input type="date" name="saving_date" id="edit_saving_date" required>
            </div>
            <div class="form-group">
                <label for="edit_member_name">Nama Anggota</label>
                <input type="text" name="member_name" id="edit_member_name" list="memberNamesDatalist" required>
                <!-- Datalist is global -->
            </div>
            <div class="form-group">
                <label for="edit_saving_type">Tipe Simpanan</label>
                <select name="saving_type" id="edit_saving_type" required>
                    <option value="wajib">Wajib</option>
                    <option value="sukarela">Sukarela</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_saving_amount">Jumlah</label>
                <input type="text" name="amount" id="edit_saving_amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-group">
                <label for="edit_saving_description">Deskripsi</label>
                <input type="text" name="description" id="edit_saving_description">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSavingModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- ** NEW ** Saving Detail Modal -->
<div id="savingDetailModal" class="modal">
    <div class="modal-content modal-lg">
        <span class="close-btn" onclick="closeModal('savingDetailModal')">&times;</span>
        <h2 id="savingDetailTitle">Detail Simpanan Anggota</h2>

        <div id="savingDetailSummary" class="loan-details">
            <!-- Summary will be loaded via AJAX -->
        </div>

        <hr>

        <h3>Riwayat Simpanan</h3>
        <div class="table-responsive mt-2">
            <table id="savingHistoryTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Deskripsi</th>
                        <?php if (isAdmin()): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- History will be loaded via AJAX -->
                    <tr><td colspan="<?php echo isAdmin() ? '5' : '4'; ?>">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
