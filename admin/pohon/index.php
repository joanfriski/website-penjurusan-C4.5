<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// Check permission
if ($_SESSION['role'] !== 'staff_bk' && $_SESSION['role'] !== 'kepsek') {
    header("Location: ../../login/login.php");
    exit;
}
?>

<style>
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    .content-wrapper.expanded {
        width: 100%;
        margin-left: 0;
    }
    
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
    .search-container {
        max-width: 100%; /* Ubah dari 800px menjadi 100% */
        margin: 0 auto;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .search-results {
        position: relative;
    }
    
    .results-dropdown {
        position: absolute;
        width: 100%;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        background: white;
        border: 1px solid #ddd;
        border-radius: 0 0 4px 4px;
        display: none;
    }
    
    .result-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }
    
    .result-item:hover {
        background-color: #f5f5f5;
    }
    
    .student-info {
        display: flex;
        justify-content: space-between;
    }
    
    .student-detail {
        margin-top: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f9f9f9;
        display: none;
    }
    
    .tree-container {
        overflow-x: auto;
        margin-top: 30px;
    }
    
    .decision-path {
        background-color: #f8f9fa;
        border-left: 4px solid #28a745;
        padding: 15px;
        margin-top: 20px;
    }
    
    .highlight-node {
        stroke: #dc3545;
        stroke-width: 3px;
    }
    
    /* Responsif untuk SVG pada perangkat mobile */
    @media (max-width: 768px) {
        .tree-container svg {
            width: 100%;
            height: auto;
        }
    }
    
    .info-card {
        margin-bottom: 20px;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-sitemap me-2"></i>Visualisasi Pohon Keputusan
                </h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Algoritma C4.5 untuk Prediksi Jurusan</h5>
                </div>
                <div class="card-body">
                    <p>Berikut adalah visualisasi pohon keputusan algoritma C4.5 yang digunakan dalam sistem prediksi jurusan. Visualisasi ini menunjukkan alur pengambilan keputusan berdasarkan nilai dan data siswa.</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card info-card">
                                <div class="card-body">
                                    <h5 class="card-title">Kategori Nilai</h5>
                                    <ul class="mb-0">
                                        <li><strong>Tinggi:</strong> Nilai ≥ 80</li>
                                        <li><strong>Sedang:</strong> Nilai ≥ 70 dan < 80</li>
                                        <li><strong>Rendah:</strong> Nilai < 70</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card info-card">
                                <div class="card-body">
                                    <h5 class="card-title">Kategori IQ</h5>
                                    <ul class="mb-0">
                                        <li><strong>Tinggi:</strong> IQ ≥ 110 (Baik)</li>
                                        <li><strong>Sedang:</strong> IQ ≥ 90 dan < 110 (Cukup)</li>
                                        <li><strong>Rendah:</strong> IQ < 90 (Kurang)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="search-container">
                <div class="mb-3">
                    <label for="searchInput" class="form-label">Cari Siswa untuk Analisis Penjurusan:</label>
                    <div class="search-results">
                        <input type="text" class="form-control" id="searchInput" placeholder="Masukkan nama siswa atau NIS..." autocomplete="off">
                        <div class="results-dropdown" id="resultsDropdown"></div>
                    </div>
                </div>
                
                <div class="student-detail" id="studentDetail">
                    <h4>Detail Siswa</h4>
                    <div id="detailContent"></div>
                </div>
                
                <div id="treeVisualization" style="display: none;">
                    <h4 class="mt-4 mb-3">Visualisasi Pohon Keputusan</h4>
                    
                    <div class="tree-container" id="treeContainer">
                        <!-- SVG Diagram Pohon Keputusan -->
                        <svg width="100%" height="360" viewBox="0 0 1000 360">
                            <!-- Nodes -->
                            <!-- Root Node (IQ) -->
                            <rect id="node-root" x="450" y="10" width="100" height="50" rx="5" fill="#6c757d" />
                            <text x="500" y="40" font-family="Arial" font-size="14" text-anchor="middle" fill="white">Tes IQ</text>
                            
                            <!-- Level 1 Nodes -->
                            <!-- Kurang Node -->
                            <rect id="node-kurang" x="200" y="110" width="100" height="50" rx="5" fill="#17a2b8" />
                            <text x="250" y="140" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IQ "Kurang"</text>
                            
                            <!-- Cukup Node -->
                            <rect id="node-cukup" x="450" y="110" width="100" height="50" rx="5" fill="#17a2b8" />
                            <text x="500" y="140" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IQ "Cukup"</text>
                            
                            <!-- Baik Node -->
                            <rect id="node-baik" x="700" y="110" width="100" height="50" rx="5" fill="#17a2b8" />
                            <text x="750" y="140" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IQ "Baik"</text>
                            
                            <!-- Level 2 Nodes for Cukup -->
                            <rect id="node-cukup-tinggi" x="350" y="210" width="100" height="50" rx="5" fill="#ffc107" />
                            <text x="400" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Tinggi"</text>
                            
                            <rect id="node-cukup-sedang-rendah" x="500" y="210" width="150" height="50" rx="5" fill="#ffc107" />
                            <text x="575" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Sedang/Rendah"</text>
                            
                            <!-- Level 2 Nodes for Baik -->
                            <rect id="node-baik-tinggi-sedang" x="650" y="210" width="150" height="50" rx="5" fill="#ffc107" />
                            <text x="725" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Tinggi/Sedang"</text>
                            
                            <rect id="node-baik-rendah" x="850" y="210" width="100" height="50" rx="5" fill="#ffc107" />
                            <text x="900" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Rendah"</text>
                            
                            <!-- Leaf Nodes -->
                            <rect id="leaf-kurang-ips" x="200" y="290" width="100" height="50" rx="5" fill="#28a745" />
                            <text x="250" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPS</text>
                            
                            <rect id="leaf-cukup-tinggi-ipa" x="350" y="290" width="100" height="50" rx="5" fill="#007bff" />
                            <text x="400" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPA</text>
                            
                            <rect id="leaf-cukup-sedang-rendah-ips" x="500" y="290" width="100" height="50" rx="5" fill="#28a745" />
                            <text x="550" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPS</text>
                            
                            <rect id="leaf-baik-tinggi-sedang-ipa" x="650" y="290" width="100" height="50" rx="5" fill="#007bff" />
                            <text x="700" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPA</text>
                            
                            <rect id="leaf-baik-rendah-ips" x="850" y="290" width="100" height="50" rx="5" fill="#28a745" />
                            <text x="900" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPS</text>
                            
                            <!-- Connecting Lines -->
                            <!-- From Root to Level 1 -->
                            <line id="line-root-kurang" x1="500" y1="60" x2="250" y2="110" stroke="#6c757d" stroke-width="2" />
                            <line id="line-root-cukup" x1="500" y1="60" x2="500" y2="110" stroke="#6c757d" stroke-width="2" />
                            <line id="line-root-baik" x1="500" y1="60" x2="750" y2="110" stroke="#6c757d" stroke-width="2" />
                            
                            <!-- From Level 1 to Level 2 -->
                            <line id="line-cukup-tinggi" x1="500" y1="160" x2="400" y2="210" stroke="#17a2b8" stroke-width="2" />
                            <line id="line-cukup-sedang-rendah" x1="500" y1="160" x2="575" y2="210" stroke="#17a2b8" stroke-width="2" />
                            
                            <line id="line-baik-tinggi-sedang" x1="750" y1="160" x2="725" y2="210" stroke="#17a2b8" stroke-width="2" />
                            <line id="line-baik-rendah" x1="750" y1="160" x2="900" y2="210" stroke="#17a2b8" stroke-width="2" />
                            
                            <!-- From Level 2 to Leaf -->
                            <line id="line-kurang-ips" x1="250" y1="160" x2="250" y2="290" stroke="#17a2b8" stroke-width="2" />
                            <line id="line-cukup-tinggi-ipa" x1="400" y1="260" x2="400" y2="290" stroke="#ffc107" stroke-width="2" />
                            <line id="line-cukup-sedang-rendah-ips" x1="575" y1="260" x2="550" y2="290" stroke="#ffc107" stroke-width="2" />
                            <line id="line-baik-tinggi-sedang-ipa" x1="725" y1="260" x2="700" y2="290" stroke="#ffc107" stroke-width="2" />
                            <line id="line-baik-rendah-ips" x1="900" y1="260" x2="900" y2="290" stroke="#ffc107" stroke-width="2" />
                            
                            <!-- Labels on edges -->
                            <text x="350" y="90" font-family="Arial" font-size="12" fill="#495057">< 90</text>
                            <text x="500" y="90" font-family="Arial" font-size="12" fill="#495057">≥ 90 dan < 110</text>
                            <text x="650" y="90" font-family="Arial" font-size="12" fill="#495057">≥ 110</text>
                            
                            <text x="430" y="190" font-family="Arial" font-size="12" fill="#495057">≥ 80</text>
                            <text x="560" y="190" font-family="Arial" font-size="12" fill="#495057">< 80</text>
                            
                            <text x="680" y="190" font-family="Arial" font-size="12" fill="#495057">≥ 70</text>
                            <text x="830" y="190" font-family="Arial" font-size="12" fill="#495057">< 70</text>
                        </svg>
                    </div>
                    
                    <div class="decision-path mt-4" id="decisionPath">
                        <h5>Jalur Keputusan untuk Siswa Ini:</h5>
                        <div id="pathDescription"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Make sure jQuery is loaded -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {

    // Tambahkan kode berikut ke bagian akhir file JavaScript yang sudah ada
// Tempatkan di dalam $(document).ready(function() { ... })

// Fungsi untuk animasi pembentukan pohon keputusan
function animateDecisionTree() {
    // Reset semua elemen
    $('rect, line, text').css({
        'opacity': 0,
        'stroke-width': '2'
    }).removeClass('highlight-node');
    
    // Sembunyikan deskripsi jalur
    $('#pathDescription').css('opacity', 0);
    
    // Tombol dinonaktifkan selama animasi
    $('#animateTreeBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Animasi sedang berjalan...');
    
    // Animasi berurutan
    // 1. Root node (Mulai dari root)
    $('#node-root').animate({ opacity: 1 }, 500, function() {
        $('text:contains("Tes IQ")').animate({ opacity: 1 }, 300);
        
        // 2. Garis dari root ke level 1 (secara berurutan)
        setTimeout(function() {
            $('#line-root-kurang').animate({ opacity: 1 }, 300);
            $('text:contains("< 90")').animate({ opacity: 1 }, 300);
            
            setTimeout(function() {
                $('#line-root-cukup').animate({ opacity: 1 }, 300);
                $('text:contains("≥ 90 dan < 110")').animate({ opacity: 1 }, 300);
                
                setTimeout(function() {
                    $('#line-root-baik').animate({ opacity: 1 }, 300);
                    $('text:contains("≥ 110")').animate({ opacity: 1 }, 300);
                    
                    // 3. Level 1 nodes - IQ Categories
                    setTimeout(function() {
                        // IQ Kurang node
                        $('#node-kurang').animate({ opacity: 1 }, 400);
                        $('text:contains("IQ \\"Kurang\\"")').animate({ opacity: 1 }, 300);
                        
                        // IQ Cukup node
                        setTimeout(function() {
                            $('#node-cukup').animate({ opacity: 1 }, 400);
                            $('text:contains("IQ \\"Cukup\\"")').animate({ opacity: 1 }, 300);
                            
                            // IQ Baik node
                            setTimeout(function() {
                                $('#node-baik').animate({ opacity: 1 }, 400);
                                $('text:contains("IQ \\"Baik\\"")').animate({ opacity: 1 }, 300);
                                
                                // 4. Lines from Level 1 to Level 2
                                setTimeout(function() {
                                    // Line from Kurang to IPS
                                    $('#line-kurang-ips').animate({ opacity: 1 }, 300);
                                    
                                    // Lines from Cukup to Level 2
                                    setTimeout(function() {
                                        $('#line-cukup-tinggi').animate({ opacity: 1 }, 300);
                                        $('text:contains("≥ 80")').animate({ opacity: 1 }, 300);
                                        
                                        setTimeout(function() {
                                            $('#line-cukup-sedang-rendah').animate({ opacity: 1 }, 300);
                                            $('text:contains("< 80")').animate({ opacity: 1 }, 300);
                                            
                                            // Lines from Baik to Level 2
                                            setTimeout(function() {
                                                $('#line-baik-tinggi-sedang').animate({ opacity: 1 }, 300);
                                                $('text:contains("≥ 70")').animate({ opacity: 1 }, 300);
                                                
                                                setTimeout(function() {
                                                    $('#line-baik-rendah').animate({ opacity: 1 }, 300);
                                                    $('text:contains("< 70")').animate({ opacity: 1 }, 300);
                                                    
                                                    // 5. Level 2 nodes - Nilai Categories
                                                    setTimeout(function() {
                                                        // Level 2 for Cukup
                                                        $('#node-cukup-tinggi').animate({ opacity: 1 }, 400);
                                                        $('text:contains("Nilai \\"Tinggi\\"")').animate({ opacity: 1 }, 300);
                                                        
                                                        setTimeout(function() {
                                                            $('#node-cukup-sedang-rendah').animate({ opacity: 1 }, 400);
                                                            $('text:contains("Nilai \\"Sedang/Rendah\\"")').animate({ opacity: 1 }, 300);
                                                            
                                                            // Level 2 for Baik
                                                            setTimeout(function() {
                                                                $('#node-baik-tinggi-sedang').animate({ opacity: 1 }, 400);
                                                                $('text:contains("Nilai \\"Tinggi/Sedang\\"")').animate({ opacity: 1 }, 300);
                                                                
                                                                setTimeout(function() {
                                                                    $('#node-baik-rendah').animate({ opacity: 1 }, 400);
                                                                    $('text:contains("Nilai \\"Rendah\\"")').animate({ opacity: 1 }, 300);
                                                                    
                                                                    // 6. Lines from Level 2 to Leaf
                                                                    setTimeout(function() {
                                                                        $('#line-cukup-tinggi-ipa, #line-cukup-sedang-rendah-ips, #line-baik-tinggi-sedang-ipa, #line-baik-rendah-ips').animate({ opacity: 1 }, 300);
                                                                        
                                                                        // 7. Leaf nodes (results)
                                                                        setTimeout(function() {
                                                                            $('#leaf-kurang-ips, #leaf-cukup-tinggi-ipa, #leaf-cukup-sedang-rendah-ips, #leaf-baik-tinggi-sedang-ipa, #leaf-baik-rendah-ips').animate({ opacity: 1 }, 500);
                                                                            $('text:contains("IPA"), text:contains("IPS")').animate({ opacity: 1 }, 300);
                                                                            
                                                                            // 8. Tampilkan kembali jalur keputusan jika ada data siswa yang dipilih
                                                                            setTimeout(function() {
                                                                                if ($('#pathDescription').html().trim() !== '') {
                                                                                    $('#pathDescription').animate({ opacity: 1 }, 500);
                                                                                    
                                                                                    // Re-highlight jalur jika ada siswa yang dipilih
                                                                                    const studentDetail = $('#studentDetail');
                                                                                    if (studentDetail.is(':visible') && studentDetail.find('.badge').length > 0) {
                                                                                        const hasilPrediksi = studentDetail.find('.badge').text().trim();
                                                                                        const kategoriIQ = studentDetail.find('th:contains("Kategori IQ")').next().text().trim();
                                                                                        const kategoriNilai = studentDetail.find('th:contains("Kategori Nilai")').next().text().trim();
                                                                                        
                                                                                        // Re-apply highlight berdasarkan data siswa
                                                                                        if (kategoriIQ && kategoriNilai) {
                                                                                            visualizeDecisionPath({
                                                                                                kategori_iq: kategoriIQ,
                                                                                                kategori_nilai: kategoriNilai,
                                                                                                hasil_prediksi: hasilPrediksi,
                                                                                                nilai_iq: studentDetail.find('th:contains("Nilai IQ")').next().text().trim(),
                                                                                                nilai_mapel_ipa: studentDetail.find('th:contains("Nilai Rata-rata IPA")').next().text().trim()
                                                                                            });
                                                                                        }
                                                                                    }
                                                                                }
                                                                                
                                                                                // Aktifkan kembali tombol
                                                                                $('#animateTreeBtn').prop('disabled', false).html('<i class="fas fa-play-circle me-1"></i>Ulangi Animasi');
                                                                            }, 500);
                                                                        }, 400);
                                                                    }, 300);
                                                                }, 200);
                                                            }, 200);
                                                        }, 200);
                                                    }, 300);
                                                }, 200);
                                            }, 200);
                                        }, 200);
                                    }, 200);
                                }, 300);
                            }, 200);
                        }, 200);
                    }, 300);
                }, 200);
            }, 200);
        }, 200);
    });
}

// Tambahkan tombol animasi
function addAnimationButton() {
    // Tambahkan tombol di bawah judul visualisasi
    const animateBtn = `
        <div class="mb-3">
            <button id="animateTreeBtn" class="btn btn-primary">
                <i class="fas fa-play-circle me-1"></i>Mulai Animasi Pohon Keputusan
            </button>
            <small class="text-muted ms-2">Klik untuk melihat tahapan pembentukan pohon keputusan</small>
        </div>
    `;
    
    $('#treeVisualization h4').after(animateBtn);
    
    // Tambahkan event listener pada tombol
    $('#animateTreeBtn').on('click', function() {
        animateDecisionTree();
    });
}

// Modifikasi fungsi visualizeDecisionPath untuk bekerja dengan animasi
const originalVisualizeDecisionPath = visualizeDecisionPath;
visualizeDecisionPath = function(data) {
    // Panggil fungsi asli
    originalVisualizeDecisionPath(data);
    
    // Tambahan untuk animasi: pastikan seluruh elemen terlihat
    $('rect, line, text').css('opacity', 1);
};

// Modifikasi fungsi untuk memuat detail siswa
const originalLoadStudentDetail = loadStudentDetail;
loadStudentDetail = function(studentId) {
    // Panggil fungsi asli
    originalLoadStudentDetail(studentId);
    
    // Pastikan tombol animasi ada
    if ($('#animateTreeBtn').length === 0) {
        setTimeout(addAnimationButton, 500); // Tunggu sebentar untuk memastikan DOM sudah siap
    }
};

// Tambahkan CSS untuk animasi
$('head').append(`
    <style>
        rect, line, text {
            transition: opacity 0.3s ease, stroke-width 0.3s ease;
        }
        
        .highlight-node {
            stroke: #dc3545;
            stroke-width: 3px;
        }
        
        .tree-container svg {
            margin: 0 auto;
            display: block;
        }
        
        .decision-tree-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 20px;
            gap: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 5px;
            border-radius: 3px;
        }
    </style>
`);

// Inisialisasi - Tambahkan tombol animasi jika ada pohon keputusan
$(document).ready(function() {
    // Menunggu dokumen siap
    setTimeout(function() {
        if ($('#treeVisualization').is(':visible') && $('#animateTreeBtn').length === 0) {
            addAnimationButton();
            
            // Tambahkan legenda
            const legendHTML = `
                <div class="decision-tree-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #6c757d;"></div>
                        <span>Root Node</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #17a2b8;"></div>
                        <span>IQ Categories</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #ffc107;"></div>
                        <span>Nilai Categories</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #007bff;"></div>
                        <span>IPA Result</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #28a745;"></div>
                        <span>IPS Result</span>
                    </div>
                </div>
            `;
            
            // Tambahkan legenda di bawah SVG
            $('#treeContainer').after(legendHTML);
            
            // Jalankan animasi pada awal load
            // Sembunyikan semua elemen terlebih dahulu
            $('rect, line, text').css('opacity', 0);
            
            // Mulai animasi setelah sedikit delay
            setTimeout(animateDecisionTree, 500);
        }
    }, 1000);
});

    console.log('Tree visualization initialized');
    
    const searchInput = $('#searchInput');
    const resultsDropdown = $('#resultsDropdown');
    const studentDetail = $('#studentDetail');
    const detailContent = $('#detailContent');
    const treeVisualization = $('#treeVisualization');
    const pathDescription = $('#pathDescription');
    
    // Fungsi untuk mendapatkan base URL secara dinamis
    function getBaseUrl() {
        return window.location.origin + window.location.pathname.split('/admin/')[0];
    }
    
    // Fungsi pencarian siswa
    function searchStudents(query) {
        console.log('Searching for:', query);
        
        if (query.length < 1) {
            resultsDropdown.html('<div class="result-item">Masukkan minimal 1 karakter</div>').show();
            return;
        }
        
        // Tampilkan loading
        resultsDropdown.html('<div class="result-item">Mencari...</div>').show();
        
        // Gunakan base URL yang dinamis
        const baseUrl = getBaseUrl();
        const apiUrl = baseUrl + '/admin/api/search_students.php?q=' + encodeURIComponent(query);
        console.log('API URL:', apiUrl);
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('Response received:', data);
                
                if (data.error) {
                    resultsDropdown.html(`<div class="result-item text-danger">${data.error}</div>`);
                    return;
                }
                
                if (data.length > 0) {
                    let html = '';
                    data.forEach(student => {
                        html += `
                            <div class="result-item" data-id="${student.id}">
                                <div class="student-info">
                                    <strong>${student.nama_lengkap}</strong>
                                    <span>NIS: ${student.nis} | Kelas: ${student.nama_kelas || '-'}</span>
                                </div>
                            </div>
                        `;
                    });
                    resultsDropdown.html(html);
                } else {
                    resultsDropdown.html('<div class="result-item">Tidak ditemukan siswa</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                resultsDropdown.html(`<div class="result-item text-danger">Error: ${error}</div>`);
            }
        });
    }
    
    // Event handler untuk input pencarian
    searchInput.on('input', function() {
        const query = $(this).val().trim();
        searchStudents(query);
    });
    
    // Event handler untuk memilih siswa
    resultsDropdown.on('click', '.result-item', function() {
        if (!$(this).data('id')) return; // Skip jika tidak ada ID (pesan error/info)
        
        const studentId = $(this).data('id');
        const studentName = $(this).find('strong').text();
        console.log('Selected student:', studentName, 'ID:', studentId);
        
        searchInput.val(studentName);
        resultsDropdown.hide();
        loadStudentDetail(studentId);
    });
    
    // Click di luar dropdown akan menutupnya
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-results').length) {
            resultsDropdown.hide();
        }
    });
    
    // Fungsi untuk memuat detail siswa dan visualisasi
    function loadStudentDetail(studentId) {
        const baseUrl = getBaseUrl();
        const apiUrl = baseUrl + '/admin/api/get_student_detail.php?id=' + studentId;
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            beforeSend: function() {
                detailContent.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                studentDetail.show();
                treeVisualization.hide();
            },
            success: function(data) {
                if (data.error) {
                    detailContent.html(`<div class="alert alert-danger">${data.error}</div>`);
                } else {
                    // Format tampilan data siswa
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">NIS</th>
                                        <td>${data.nis}</td>
                                    </tr>
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <td>${data.nama_lengkap}</td>
                                    </tr>
                                    <tr>
                                        <th>Kelas</th>
                                        <td>${data.nama_kelas || 'Belum ada kelas'}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                    `;
                    
                    // Tambahkan info prediksi jika ada
                    if (data.has_prediksi) {
                        html += `
                            <tr>
                                <th width="40%">Nilai Rata-rata IPA</th>
                                <td>${data.nilai_mapel_ipa ? Number(data.nilai_mapel_ipa).toFixed(2) : '-'}</td>
                            </tr>
                            <tr>
                                <th>Nilai Rata-rata IPS</th>
                                <td>${data.nilai_mapel_ips ? Number(data.nilai_mapel_ips).toFixed(2) : '-'}</td>
                            </tr>
                            <tr>
                                <th>Nilai IQ</th>
                                <td>${data.nilai_iq || '-'}</td>
                            </tr>
                            <tr>
                                <th>Minat</th>
                                <td>${data.minat || '-'}</td>
                            </tr>
                            <tr>
                                <th>Hasil Prediksi</th>
                                <td>
                                    <span class="badge bg-${data.hasil_prediksi === 'IPA' ? 'primary' : 'success'} fs-6">
                                        ${data.hasil_prediksi}
                                    </span>
                                </td>
                            </tr>
                        `;
                        
                        // Tampilkan juga kategori IQ dan nilai untuk visualisasi
                        html += `
                            <tr>
                                <th>Kategori IQ</th>
                                <td>${data.kategori_iq || '-'}</td>
                            </tr>
                            <tr>
                                <th>Kategori Nilai</th>
                                <td>${data.kategori_nilai || '-'}</td>
                            </tr>
                        `;
                    } else {
                        html += `
                            <tr>
                                <td colspan="2" class="text-center">
                                    <div class="alert alert-warning mb-0">
                                        Belum ada prediksi jurusan untuk siswa ini.
                                    </div>
                                </td>
                            </tr>
                        `;
                    }
                    
                    html += `
                                </table>
                            </div>
                        </div>
                    `;
                    
                    // Tambahkan tombol untuk melihat detail lengkap
                    if (data.has_prediksi) {
                        html += `
                            <div class="text-center mt-3">
                                <a href="${baseUrl}/admin/penjurusan/detail.php?id=${data.prediksi_id}" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Lihat Detail Lengkap
                                </a>
                            </div>
                        `;
                    }
                    
                    detailContent.html(html);
                    
                    // Tampilkan visualisasi pohon keputusan jika ada prediksi
                    if (data.has_prediksi) {
                        treeVisualization.show();
                        visualizeDecisionPath(data);
                    } else {
                        treeVisualization.hide();
                    }
                }
                studentDetail.show();
            },
            error: function(xhr, status, error) {
                console.error('Error loading student details:', status, error);
                detailContent.html(`
                    <div class="alert alert-danger">
                        Gagal memuat detail siswa<br>
                        Error: ${error}<br>
                        Status: ${xhr.status}
                    </div>
                `);
                studentDetail.show();
                treeVisualization.hide();
            }
        });
    }
    
    // Fungsi untuk visualisasi jalur keputusan
    function visualizeDecisionPath(data) {
        // Reset semua highlight
        $('rect, line').removeClass('highlight-node');
        
        // Siapkan deskripsi jalur
        let pathHTML = '<ol class="mb-0">';
        
        // Highlight jalur sesuai data siswa
        const kategoriIQ = data.kategori_iq;
        const kategoriNilai = data.kategori_nilai;
        const hasilPrediksi = data.hasil_prediksi;
        const nilaiIQ = data.nilai_iq;
        const nilaiMapelIPA = data.nilai_mapel_ipa;
        
        // Highlight node root (awal)
        $('#node-root').addClass('highlight-node');
        pathHTML += `<li><strong>Tes IQ</strong>: Nilai IQ = ${nilaiIQ}</li>`;
        
        // Identifikasi jalur berdasarkan kategori IQ
        if (kategoriIQ === 'Rendah') {
            // Jalur IQ Rendah / Kurang
            $('#line-root-kurang, #node-kurang, #line-kurang-ips, #leaf-kurang-ips').addClass('highlight-node');
            pathHTML += `<li><strong>Kategori IQ</strong>: "Kurang" (IQ < 90)</li>`;
            pathHTML += `<li><strong>Hasil langsung</strong>: IPS (Karena IQ berkategori Kurang)</li>`;
        } 
        else if (kategoriIQ === 'Sedang') {
            // Jalur IQ Sedang / Cukup
            $('#line-root-cukup, #node-cukup').addClass('highlight-node');
            pathHTML += `<li><strong>Kategori IQ</strong>: "Cukup" (IQ ≥ 90 dan < 110)</li>`;
            
            if (kategoriNilai === 'Tinggi') {
                // Cukup + Nilai Tinggi -> IPA
                $('#line-cukup-tinggi, #node-cukup-tinggi, #line-cukup-tinggi-ipa, #leaf-cukup-tinggi-ipa').addClass('highlight-node');
                pathHTML += `<li><strong>Kategori Nilai</strong>: "Tinggi" (Nilai IPA rata-rata: ${Number(nilaiMapelIPA).toFixed(2)} ≥ 80)</li>`;
                pathHTML += `<li><strong>Hasil</strong>: IPA (Karena IQ Cukup dan Nilai Tinggi)</li>`;
            } else {
                // Cukup + Nilai Sedang/Rendah -> IPS
                $('#line-cukup-sedang-rendah, #node-cukup-sedang-rendah, #line-cukup-sedang-rendah-ips, #leaf-cukup-sedang-rendah-ips').addClass('highlight-node');
                pathHTML += `<li><strong>Kategori Nilai</strong>: "${kategoriNilai}" (Nilai IPA rata-rata: ${Number(nilaiMapelIPA).toFixed(2)} < 80)</li>`;
                pathHTML += `<li><strong>Hasil</strong>: IPS (Karena IQ Cukup dan Nilai tidak Tinggi)</li>`;
            }
        } 
        else if (kategoriIQ === 'Tinggi') {
            // Jalur IQ Tinggi / Baik
            $('#line-root-baik, #node-baik').addClass('highlight-node');
            pathHTML += `<li><strong>Kategori IQ</strong>: "Baik" (IQ ≥ 110)</li>`;
            
            if (kategoriNilai === 'Tinggi' || kategoriNilai === 'Sedang') {
                // Baik + Nilai Tinggi/Sedang -> IPA
                $('#line-baik-tinggi-sedang, #node-baik-tinggi-sedang, #line-baik-tinggi-sedang-ipa, #leaf-baik-tinggi-sedang-ipa').addClass('highlight-node');
                pathHTML += `<li><strong>Kategori Nilai</strong>: "${kategoriNilai}" (Nilai IPA rata-rata: ${Number(nilaiMapelIPA).toFixed(2)} ≥ 70)</li>`;
                pathHTML += `<li><strong>Hasil</strong>: IPA (Karena IQ Baik dan Nilai Tinggi/Sedang)</li>`;
            } else {
                // Baik + Nilai Rendah -> IPS
                $('#line-baik-rendah, #node-baik-rendah, #line-baik-rendah-ips, #leaf-baik-rendah-ips').addClass('highlight-node');
                pathHTML += `<li><strong>Kategori Nilai</strong>: "Rendah" (Nilai IPA rata-rata: ${Number(nilaiMapelIPA).toFixed(2)} < 70)</li>`;
                pathHTML += `<li><strong>Hasil</strong>: IPS (Karena IQ Baik tetapi Nilai Rendah)</li>`;
            }
        }
        
        pathHTML += '</ol>';
        pathDescription.html(pathHTML);
    }
    
    // Inisialisasi - tampilkan dropdown saat input difokuskan
    searchInput.on('focus', function() {
        const query = $(this).val().trim();
        if (query.length > 0) {
            searchStudents(query);
        } else {
            resultsDropdown.html('<div class="result-item">Masukkan nama siswa atau NIS</div>').show();
        }
    });
});


</script>

<?php 
require_once '../include/footer.php'; 
ob_end_flush();
?>