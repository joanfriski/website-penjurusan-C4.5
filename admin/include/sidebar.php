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
                <a href="../kelas/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'kelas') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-chalkboard me-2"></i>Data Kelas
                </a>
            </li>
            <li class="nav-item">
                <a href="../siswa/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'siswa') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-user-graduate me-2"></i>Data Siswa
                </a>
            </li>
            <li class="nav-item">
                <a href="../nilai/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'nilai') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-chart-bar me-2"></i>Nilai Akademik
                </a>
            </li>
            <li class="nav-item">
                <a href="../test_iq/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'test_iq') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-brain me-2"></i>Test IQ
                </a>
            </li>
            <li class="nav-item">
                <a href="../minat/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'minat') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-heart me-2"></i>Minat Siswa
                </a>
            </li>
            <li class="nav-item">
                <a href="../perhitungan/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'perhitungan') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-calculator me-2"></i>Perhitungan
                </a>
            </li>
            <li class="nav-item">
                <a href="../hasil_perhitungan/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'hasil_perhitungan') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-chart-line me-2"></i>Hasil Perhitungan
                </a>
            </li>
            <!--
            =============================
            Menu Klasifikasi Siswa (DISABLED)
            =============================
            <li class="nav-item">
                <a href="../decision_tree/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'decision_tree') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-code-branch me-2"></i>Klasifikasi Siswa
                </a>
            </li>
            =============================
            Menu Proses Penjurusan (DISABLED)
            =============================
            <li class="nav-item">
                <a href="../penjurusan/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'penjurusan') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-route me-2"></i>Proses Penjurusan
                </a>
            </li>
            =============================
            Menu Pohon Keputusan (DISABLED)
            =============================
            <li class="nav-item">
                <a href="../pohon/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'pohon') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-sitemap me-2"></i>Pohon Keputusan
                </a>
            </li>
            -->
            <li class="nav-item">
                <a href="../laporan/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'laporan') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-file-pdf me-2"></i>Laporan
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="../pengaturan/profil.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'pengaturan') !== false) ? 'active bg-white text-primary' : 'text-white'; ?>">
                    <i class="fas fa-user-cog me-2"></i>Pengaturan Profile
                </a>
            </li>
        </ul>
    </div>
</div>