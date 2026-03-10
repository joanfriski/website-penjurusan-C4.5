<?php
require_once '../include/header.php';
require_once '../../database/config.php';

// Jumlah siswa per jurusan
$query_ipa = "SELECT COUNT(*) as total FROM hasil_klasifikasi WHERE hasil_prediksi = 'IPA'";
$result_ipa = mysqli_query($conn, $query_ipa);
$total_ipa = mysqli_fetch_assoc($result_ipa)['total'];

$query_ips = "SELECT COUNT(*) as total FROM hasil_klasifikasi WHERE hasil_prediksi = 'IPS'";
$result_ips = mysqli_query($conn, $query_ips);
$total_ips = mysqli_fetch_assoc($result_ips)['total'];

// Jumlah siswa per kelas
$query_kelas = "SELECT k.nama_kelas, COUNT(s.id) as total_siswa 
                FROM kelas k 
                LEFT JOIN siswa s ON k.id = s.kelas_id AND s.status = 'aktif'
                GROUP BY k.id 
                ORDER BY k.nama_kelas";
$result_kelas = mysqli_query($conn, $query_kelas);

// Total siswa aktif
$query_total_siswa = "SELECT COUNT(*) as total FROM siswa WHERE status = 'aktif'";
$result_total_siswa = mysqli_query($conn, $query_total_siswa);
$total_siswa = mysqli_fetch_assoc($result_total_siswa)['total'];

// Total siswa yang sudah diprediksi
$query_prediksi = "SELECT COUNT(*) as total FROM hasil_klasifikasi";
$result_prediksi = mysqli_query($conn, $query_prediksi);
$total_prediksi = mysqli_fetch_assoc($result_prediksi)['total'];

// Persentase siswa yang sudah diprediksi
$persentase_prediksi = ($total_siswa > 0) ? round(($total_prediksi / $total_siswa) * 100) : 0;

// Menyimpan data kelas untuk chart
$kelas_labels = [];
$kelas_data = [];
mysqli_data_seek($result_kelas, 0);
while($row = mysqli_fetch_assoc($result_kelas)) {
    $kelas_labels[] = $row['nama_kelas'];
    $kelas_data[] = $row['total_siswa'];
}
?>
<!-- CSS untuk perbaikan responsif content -->
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
    
    /* Styling untuk pagination */
    .pagination {
        justify-content: center;
        margin-top: 15px;
    }
    
    .search-form {
        margin-bottom: 15px;
    }

      /* Styling untuk card */
      .card {
            height: 100%;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* Styling untuk chart container */
        .chart-container {
            position: relative;
            width: 100%;
            height: 300px;
        }
        
        /* Custom styling untuk menu laporan */
        .menu-card {
            text-align: center;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .menu-card:hover {
            transform: scale(1.03);
        }
        
        .menu-card .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
         /* Styling untuk statistik */
         .stat-card {
            border-left: 4px solid #4e73df;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fc;
        }
        
        .stat-primary {
            border-left-color: #4e73df;
        }
        
        .stat-success {
            border-left-color: #1cc88a;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        /* Animasi untuk cards */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animated-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        /* Styling untuk shadows */
        .shadow-hover:hover {
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-file-alt me-2"></i>Laporan Sistem Penjurusan
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?php echo date('d F Y'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
                 <!-- Menu Laporan -->
                 <div class="row mb-4 animated-card" style="animation-delay: 0.1s;">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Menu Laporan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="card menu-card shadow-hover h-100">
                                        <div class="card-body">
                                            <div class="card-icon text-primary">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <h5 class="card-title">Laporan Data Siswa</h5>
                                            <p class="card-text text-muted">Laporan data seluruh siswa aktif di sekolah</p>
                                            <a href="siswa.php" class="btn btn-primary">Lihat Laporan</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card menu-card shadow-hover h-100">
                                        <div class="card-body">
                                            <div class="card-icon text-success">
                                                <i class="fas fa-sitemap"></i>
                                            </div>
                                            <h5 class="card-title">Laporan Penjurusan</h5>
                                            <p class="card-text text-muted">Laporan hasil penjurusan siswa IPA dan IPS</p>
                                            <a href="penjurusan.php" class="btn btn-success">Lihat Laporan</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card menu-card shadow-hover h-100">
                                        <div class="card-body">
                                            <div class="card-icon text-info">
                                                <i class="fas fa-print"></i>
                                            </div>
                                            <h5 class="card-title">Cetak Laporan</h5>
                                            <p class="card-text text-muted">Cetak laporan siswa dan hasil penjurusan</p>
                                            <a href="cetak_lengkap.php" class="btn btn-info text-white">Cetak Laporan</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ringkasan Laporan -->
            <div class="row animated-card" style="animation-delay: 0.2s;">
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Ringkasan Laporan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="stat-card stat-primary">
                                        <div class="small text-muted">Total Siswa Aktif</div>
                                        <div class="stat-number text-primary"><?php echo $total_siswa; ?> <small>Siswa</small></div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="stat-card stat-success">
                                        <div class="small text-muted">Total Terjuruskan</div>
                                        <div class="stat-number text-success"><?php echo $total_prediksi; ?> <small>Siswa</small></div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="stat-card" style="border-left-color: #36a2eb;">
                                        <div class="small text-muted">Jurusan IPA</div>
                                        <div class="stat-number" style="color: #36a2eb;"><?php echo $total_ipa; ?> <small>Siswa</small></div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="stat-card" style="border-left-color: #ff6384;">
                                        <div class="small text-muted">Jurusan IPS</div>
                                        <div class="stat-number" style="color: #ff6384;"><?php echo $total_ips; ?> <small>Siswa</small></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $persentase_prediksi; ?>%;" aria-valuenow="<?php echo $persentase_prediksi; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $persentase_prediksi; ?>% Terjuruskan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grafik -->
            <div class="row animated-card" style="animation-delay: 0.3s;">
                <div class="col-lg-6 mb-4">
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
                            <div class="d-flex justify-content-center mt-3">
                                <div class="text-center me-4">
                                    <div class="d-inline-block p-2 rounded-circle" style="background-color: #36a2eb;"></div>
                                    <span class="ms-2 fw-bold">IPA: <?php echo $total_ipa; ?> Siswa</span>
                                </div>
                                <div class="text-center">
                                    <div class="d-inline-block p-2 rounded-circle" style="background-color: #ff6384;"></div>
                                    <span class="ms-2 fw-bold">IPS: <?php echo $total_ips; ?> Siswa</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-school me-2"></i>Siswa per Kelas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="kelasChart"></canvas>
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
       const ctxPenjurusan = document.getElementById('penjurusanChart').getContext('2d');
        const penjurusanChart = new Chart(ctxPenjurusan, {
            type: 'doughnut', // Changed from pie to doughnut for better look
            data: {
                labels: ['IPA', 'IPS'],
                datasets: [{
                    data: [<?php echo $total_ipa; ?>, <?php echo $total_ips; ?>],
                    backgroundColor: ['#36a2eb', '#ff6384'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Hasil Penjurusan',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
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
        
      // Data untuk grafik kelas
      const ctxKelas = document.getElementById('kelasChart').getContext('2d');
        const kelasChart = new Chart(ctxKelas, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($kelas_labels); ?>,
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: <?php echo json_encode($kelas_data); ?>,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 205, 86, 0.7)'
                    ],
                    borderColor: [
                        'rgb(75, 192, 192)',
                        'rgb(54, 162, 235)',
                        'rgb(153, 102, 255)',
                        'rgb(255, 159, 64)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 205, 86)'
                    ],
                    borderWidth: 1,
                    borderRadius: 5,
                    hoverBackgroundColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 205, 86, 1)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 5
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Jumlah Siswa per Kelas',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.raw} siswa`;
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        // Responsive handling for charts
        window.addEventListener('resize', function() {
            penjurusanChart.resize();
            kelasChart.resize();
        });
    });
</script>

<?php require_once '../include/footer.php'; ?>