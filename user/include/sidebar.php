<div class="col-md-3 col-lg-2 px-0">
    <div class="sidebar" id="sidebar" style="width: 250px; position: fixed; top: 56px; left: 0; height: calc(100vh - 56px); z-index: 100; transition: all 0.3s ease; overflow-y: auto; background: linear-gradient(180deg, #0d6efd 0%, #084298 100%); box-shadow: 2px 0 5px rgba(0,0,0,0.1);">
        <div class="p-3">
            <h5 class="text-center text-white mb-4">
                <i class="fas fa-school me-2"></i>SMAN 1 Ciwaru
            </h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="../dashboard/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../siswa/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'siswa') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                        <i class="fas fa-users me-2"></i>Data Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../laporan/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'laporan') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                        <i class="fas fa-file-alt me-2"></i>Laporan
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a href="../pengaturan/profile.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'pengaturan') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                        <i class="fas fa-cog me-2"></i>Profil
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

