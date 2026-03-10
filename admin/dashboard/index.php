<?php
require_once '../include/header.php';
require_once '../../database/config.php';

// Ambil jumlah siswa
$query_siswa = "SELECT COUNT(*) as total FROM siswa WHERE status = 'aktif'";
$result_siswa = mysqli_query($conn, $query_siswa);
$total_siswa = mysqli_fetch_assoc($result_siswa)['total'];

// Ambil jumlah kelas
$query_kelas = "SELECT COUNT(*) as total FROM kelas";
$result_kelas = mysqli_query($conn, $query_kelas);
$total_kelas = mysqli_fetch_assoc($result_kelas)['total'];

// Ambil jumlah siswa yang sudah dijuruskan (menggunakan tabel hasil_klasifikasi)
$query_penjurusan = "SELECT COUNT(*) as total FROM hasil_klasifikasi";
$result_penjurusan = mysqli_query($conn, $query_penjurusan);
$total_penjurusan = mysqli_fetch_assoc($result_penjurusan)['total'];

// Query untuk mendapatkan jumlah siswa per jurusan (untuk grafik)
$query_ipa = "SELECT COUNT(*) as total FROM hasil_klasifikasi WHERE hasil_prediksi = 'IPA'";
$result_ipa = mysqli_query($conn, $query_ipa);
$total_ipa = mysqli_fetch_assoc($result_ipa)['total'];

$query_ips = "SELECT COUNT(*) as total FROM hasil_klasifikasi WHERE hasil_prediksi = 'IPS'";
$result_ips = mysqli_query($conn, $query_ips);
$total_ips = mysqli_fetch_assoc($result_ips)['total'];

// Ambil log aktivitas terbaru
$query_log = "SELECT la.aktivitas, la.created_at, u.nama_lengkap 
              FROM log_aktivitas la 
              JOIN users u ON la.user_id = u.id 
              ORDER BY la.created_at DESC LIMIT 5";
$result_log = mysqli_query($conn, $query_log);
?>

<!-- CSS untuk perbaikan resposif content -->
<style>
    /* Base styling untuk content-wrapper */
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    /* Saat sidebar collapsed */
    .content-wrapper.expanded {
        width: 100%;
        margin-left: 0;
    }
    
    /* Untuk perangkat mobile */
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
    /* Pastikan main content selalu mengikuti lebar yang tersedia */
    main {
        width: 100%;
        transition: all 0.3s ease;
    }
    body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2, .header h3, .header p {
            margin: 5px 0;
        }
        .title {
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary {
            margin-bottom: 20px;
            width: 100%;
        }
        .summary-item {
            display: inline-block;
            width: 24%;
            padding: 10px;
            box-sizing: border-box;
            vertical-align: top;
        }
        .summary-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .footer p {
            margin: 5px 0;
        }
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            button {
                display: none;
            }
        }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-3 py-3 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h2 class="h3 mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Penjurusan
                    </h2>
                    <p class="text-muted small mt-1">Ikhtisar data siswa dan proses penjurusan menggunakan algoritma C4.5</p>
                </div>

                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                    <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('d F Y'); ?>
                            </button>
                    </div>
                </div>
            </div>
            
           <!-- Stat Cards -->
           <div class="row stat-cards mb-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Siswa</h5>
                                    <p class="card-text display-6"><?php echo $total_siswa; ?></p>
                                </div>
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="../siswa/index.php" class="text-white stretched-link text-decoration-none">
                                Lihat Detail
                            </a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Kelas</h5>
                                    <p class="card-text display-6"><?php echo $total_kelas; ?></p>
                                </div>
                                <i class="fas fa-school fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="../kelas/index.php" class="text-white stretched-link text-decoration-none">
                                Lihat Detail
                            </a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Siswa Dijuruskan</h5>
                                    <p class="card-text display-6"><?php echo $total_penjurusan; ?></p>
                                </div>
                                <i class="fas fa-sitemap fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a href="../hasil_perhitungan/index.php" class="text-white stretched-link text-decoration-none">
                                Lihat Detail
                            </a>
                            <i class="fas fa-angle-right text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <!-- Grafik dan Aktivitas -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>Statistik Penjurusan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="penjurusanChart"></canvas>
                            </div>
                            <div class="row mt-3 text-center">
                                <div class="col-6">
                                    <div class="p-3 rounded bg-light">
                                        <h6>IPA</h6>
                                        <h4 class="text-primary"><?php echo $total_ipa; ?></h4>
                                        <small class="text-muted">Siswa</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded bg-light">
                                        <h6>IPS</h6>
                                        <h4 class="text-danger"><?php echo $total_ips; ?></h4>
                                        <small class="text-muted">Siswa</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-bell me-2"></i>Aktivitas Terkini
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="alert alert-info m-3">
                                <i class="fas fa-info-circle me-2"></i>Selamat datang di sistem penjurusan SMAN 1 Ciwaru
                            </div>
                            <div class="list-group list-group-flush">
                                <?php if(mysqli_num_rows($result_log) > 0): ?>
                                    <?php while($log = mysqli_fetch_assoc($result_log)): ?>
                                    <div class="list-group-item activity-item">
                                        <div class="d-flex justify-content-between">
                                            <span><i class="fas fa-history text-primary me-2"></i><?php echo $log['aktivitas']; ?></span>
                                            <small class="text-muted"><?php echo date('d/m H:i', strtotime($log['created_at'])); ?></small>
                                        </div>
                                        <small class="text-muted">Oleh: <?php echo $log['nama_lengkap']; ?></small>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="list-group-item">Belum ada aktivitas tercatat</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Sistem -->
            <div class="card shadow-sm mb-4">
                <div class="card-header text-white" style="background: linear-gradient(90deg, #0d6efd 0%, #0a58ca 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informasi Sistem Penjurusan
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-4"><i class="fas fa-lightbulb text-warning me-2"></i><strong>Sistem C4.5</strong> digunakan untuk mengklasifikasikan jurusan siswa berdasarkan data nilai, IQ, dan minat belajar mereka.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary"><i class="fas fa-cogs me-2"></i>Alur Penjurusan:</h6>
                            <div class="list-group">
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill me-2">1</span> Input data siswa
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill me-2">2</span> Input nilai & IQ
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill me-2">3</span> Input minat siswa
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill me-2">4</span> Proses klasifikasi C4.5
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-pill me-2">5</span> Hasil penjurusan
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-success"><i class="fas fa-clipboard-check me-2"></i>Atribut Utama:</h6>
                            <div class="card mb-2">
                                <div class="card-body d-flex align-items-center py-2">
                                    <span class="badge bg-primary rounded-circle p-2 me-2"><i class="fas fa-flask"></i></span>
                                    <span>Nilai IPA/IPS</span>
                                </div>
                            </div>
                            <div class="card mb-2">
                                <div class="card-body d-flex align-items-center py-2">
                                    <span class="badge bg-info rounded-circle p-2 me-2"><i class="fas fa-brain"></i></span>
                                    <span>IQ Test</span>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body d-flex align-items-center py-2">
                                    <span class="badge bg-success rounded-circle p-2 me-2"><i class="fas fa-heart"></i></span>
                                    <span>Minat Siswa</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data untuk grafik penjurusan
        const ctx = document.getElementById('penjurusanChart').getContext('2d');
        
        // Mendapatkan data jurusan
        const totalIPA = <?php echo $total_ipa; ?>;
        const totalIPS = <?php echo $total_ips; ?>;
        
        // Warna yang lebih menarik
        const colors = {
            ipa: {
                main: '#4361ee',
                hover: '#3a56d4'
            },
            ips: {
                main: '#ef476f',
                hover: '#d64265'
            }
        };
        
        // Membuat chart
        const penjurusanChart = new Chart(ctx, {
            type: 'doughnut', // Changed to doughnut for better look
            data: {
                labels: ['IPA', 'IPS'],
                datasets: [{
                    data: [totalIPA, totalIPS],
                    backgroundColor: [colors.ipa.main, colors.ips.main],
                    hoverBackgroundColor: [colors.ipa.hover, colors.ips.hover],
                    borderWidth: 0,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 15,
                            padding: 15,
                            font: {
                                size: 14
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Hasil Penjurusan',
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} siswa (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Fungsi untuk resize chart
        function resizeChart() {
            penjurusanChart.resize();
        }
        
        // Event listener untuk resize window
        window.addEventListener('resize', resizeChart);
        
        // Sidebar toggle handler
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                // Give time for the sidebar transition
                setTimeout(resizeChart, 300);
            });
        }
    });

    // Tambahan script untuk sidebar responsif
    $(document).ready(function() {
        // Memeriksa ukuran layar saat halaman dimuat
        checkScreenSize();
        
        // Memeriksa ukuran layar saat window di-resize
        $(window).resize(function() {
            checkScreenSize();
        });
        
        // Toggle sidebar dari header
        $("#sidebarToggle").on("click", function() {
            $("#sidebar").toggleClass("collapsed");
        });
        
        // Fungsi untuk memeriksa ukuran layar
        function checkScreenSize() {
            if ($(window).width() < 768) {
                $("#sidebar").addClass("collapsed");
            }
        }
        
        // Animasi smooth scroll untuk link di sidebar
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            
            $('html, body').animate({
                scrollTop: $($(this).attr('href')).offset().top
            }, 500, 'linear');
        });
    });
</script>

<?php require_once '../include/footer.php'; ?>