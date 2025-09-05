<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="assets/logo.png" alt="Koperasi Masjid Logo" style="width: 100px; height: auto;" />
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="index.php?page=dashboard" class="<?php echo ($page == 'dashboard') ? 'aktif' : ''; ?>">Dashboard</a></li>
            <li><a href="index.php?page=transactions" class="<?php echo ($page == 'transactions') ? 'aktif' : ''; ?>">Kas Umum</a></li>
            <li><a href="index.php?page=savings" class="<?php echo ($page == 'savings') ? 'aktif' : ''; ?>">Simpanan</a></li>
            <li><a href="index.php?page=loans" class="<?php echo ($page == 'loans') ? 'aktif' : ''; ?>">Piutang</a></li>
            <li><a href="index.php?page=infaq" class="<?php echo ($page == 'infaq') ? 'aktif' : ''; ?>">Infaq</a></li>
        </ul>
    </nav>
</aside>
<!-- includes/sidebar.php -->