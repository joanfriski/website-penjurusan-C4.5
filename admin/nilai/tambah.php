<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit;
}

// Ambil data siswa untuk dropdown (hanya yang belum memiliki semua nilai)
$query_siswa = "SELECT s.id, s.nis, s.nama_lengkap 
                FROM siswa s
                WHERE s.status = 'aktif'
                AND (
                    SELECT COUNT(*) 
                    FROM mata_pelajaran 
                ) > (
                    SELECT COUNT(*) 
                    FROM nilai n 
                    WHERE n.siswa_id = s.id AND n.tahun_ajaran = '2025/2026'
                )
                ORDER BY s.nama_lengkap ASC";
$result_siswa = mysqli_query($conn, $query_siswa);

// Ambil data mata pelajaran untuk dropdown
$query_mapel = "SELECT id, kode, nama, kategori FROM mata_pelajaran ORDER BY kategori, nama ASC";
$result_mapel = mysqli_query($conn, $query_mapel);

// Menyimpan data mata pelajaran dalam array untuk digunakan di JavaScript
$mapel_data = [];
mysqli_data_seek($result_mapel, 0);
while ($mapel = mysqli_fetch_assoc($result_mapel)) {
    $mapel_data[] = $mapel;
}

// Ambil tahun ajaran saat ini
$tahun_ajaran = "2025/2026";

// Proses form jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $siswa_id = $_POST['siswa_id'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $user_id = $_SESSION['user_id'];
    
    // Validasi input dasar
    $errors = [];
    
    if (empty($siswa_id)) {
        $errors[] = "Siswa harus dipilih";
    }
    
    if (empty($tahun_ajaran)) {
        $errors[] = "Tahun ajaran harus diisi";
    }
    
    // Jika tidak ada error pada validasi dasar
    if (empty($errors)) {
        $success_count = 0;
        $error_count = 0;
        
        // Loop melalui semua mata pelajaran yang dikirim
        foreach ($_POST['nilai'] as $mapel_id => $nilai) {
            // Skip jika nilai kosong (tidak diisi)
            if ($nilai === '') {
                continue;
            }
            
            // Validasi nilai
            if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
                $errors[] = "Nilai untuk mata pelajaran #$mapel_id harus berupa angka antara 0-100";
                $error_count++;
                continue;
            }
            
            // Cek apakah nilai untuk siswa, mapel, dan tahun ajaran sudah ada
            $query_check = "SELECT * FROM nilai WHERE siswa_id = '$siswa_id' AND mapel_id = '$mapel_id' 
                           AND tahun_ajaran = '$tahun_ajaran'";
            $result_check = mysqli_query($conn, $query_check);
            
            if (mysqli_num_rows($result_check) > 0) {
                $errors[] = "Nilai untuk siswa dan mata pelajaran pada tahun ajaran ini sudah ada";
                $error_count++;
                continue;
            }
            
            // Simpan data nilai
            $query = "INSERT INTO nilai (siswa_id, mapel_id, nilai, tahun_ajaran) 
                     VALUES ('$siswa_id', '$mapel_id', '$nilai', '$tahun_ajaran')";
            
            if (mysqli_query($conn, $query)) {
                $success_count++;
            } else {
                $errors[] = "Gagal menyimpan nilai untuk mata pelajaran #$mapel_id: " . mysqli_error($conn);
                $error_count++;
            }
        }
        
        // Log aktivitas jika ada data yang berhasil disimpan
        if ($success_count > 0) {
            $aktivitas = "Menambahkan $success_count nilai baru";
            $query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
            mysqli_query($conn, $query_log);
            
            $_SESSION['success'] = "$success_count data nilai berhasil ditambahkan";
            
            if ($error_count == 0) {
                header("Location: index.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Tidak ada data nilai yang berhasil disimpan";
        }
    }
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
        /* Styling untuk dropdown Select2 */
.select2-container--default .select2-selection--single {
    height: 38px;
    padding: 5px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

.select2-container--default .select2-search--dropdown .select2-search__field {
    padding: 8px;
    border: 1px solid #ced4da;
}

/* Format hasil pencarian */
.select2-result-siswa {
    padding: 6px;
}

.select2-result-siswa__nis {
    font-size: 12px;
    color: #666;
}

.select2-result-siswa__nama {
    font-weight: bold;
}
    }
</style>
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery (kalau belum) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Data Nilai
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="" method="POST" id="form-nilai">
                        <div class="row mb-4">
                        <div class="col-md-6">
    <label for="siswa_id" class="form-label">Siswa</label>
    <select class="form-select" id="siswa_id" name="siswa_id" required>
        <option value="">-- Pilih Siswa --</option>
        <?php 
        // Pastikan pointer result di-reset ke awal
        mysqli_data_seek($result_siswa, 0);

        // Ambil nilai siswa_id yang aktif, baik dari POST atau dari data lain (misal untuk edit)
        $selected_siswa_id = isset($_POST['siswa_id']) ? $_POST['siswa_id'] : (isset($current_data['siswa_id']) ? $current_data['siswa_id'] : '');

        while ($siswa = mysqli_fetch_assoc($result_siswa)): 
        ?>
            <option value="<?= htmlspecialchars($siswa['id']) ?>" <?= $selected_siswa_id == $siswa['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($siswa['nis'] . ' - ' . $siswa['nama_lengkap']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    <div class="form-text">Pilih siswa yang akan diberi nilai</div>
</div>

                            
                            <div class="col-md-6">
                                <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                                <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" value="<?php echo isset($_POST['tahun_ajaran']) ? $_POST['tahun_ajaran'] : $tahun_ajaran; ?>" required>
                                <div class="form-text">Format: YYYY/YYYY (contoh: 2025/2026)</div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">Data Nilai</h5>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-success me-2" id="btn-tambah-mapel">
                                <i class="fas fa-plus me-1"></i> Tambah Mata Pelajaran
                            </button>
                            <button type="button" class="btn btn-info" id="btn-tampilkan-semua">
                                <i class="fas fa-list me-1"></i> Tampilkan Semua Mata Pelajaran
                            </button>
                        </div>
                        
                        <div id="mapel-container">
                            <!-- Form mata pelajaran akan ditambahkan di sini oleh JavaScript -->
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Semua
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Petunjuk Pengisian</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Pastikan memilih siswa yang benar terlebih dahulu</li>
                            <li>Klik tombol "Tambah Mata Pelajaran" untuk menambahkan form mata pelajaran satu per satu</li>
                            <li>Atau klik tombol "Tampilkan Semua Mata Pelajaran" untuk menampilkan semua mata pelajaran sekaligus</li>
                            <li>Nilai diisi dengan angka desimal antara 0-100</li>
                            <li>Tahun ajaran menggunakan format YYYY/YYYY (contoh: 2025/2026)</li>
                            <li>Kosongkan nilai jika tidak ingin memasukkan nilai untuk mata pelajaran tertentu</li>
                            <li>Sistem tidak mengizinkan duplikasi data untuk siswa, mata pelajaran, dan tahun ajaran yang sama</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {


$(document).ready(function() {
    $('#siswa_id').select2({
        placeholder: "Ketik untuk mencari siswa...",
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Tidak ada siswa yang ditemukan";
            },
            searching: function() {
                return "Mencari...";
            },
            inputTooShort: function() {
                return "Silakan ketik nama atau NIS siswa";
            }
        },
        minimumInputLength: 1, // Hanya menampilkan hasil setelah minimal 1 karakter diketik
        templateResult: formatSiswa,
        templateSelection: formatSiswaSelection
    });
});

// Format tampilan hasil pencarian
function formatSiswa(siswa) {
    if (siswa.loading) return siswa.text;
    
    if (!siswa.id) return siswa.text;
    
    var $container = $(
        '<div class="select2-result-siswa">' +
            '<div class="select2-result-siswa__nis">NIS: ' + siswa.element.text.split(' - ')[0] + '</div>' +
            '<div class="select2-result-siswa__nama">' + siswa.element.text.split(' - ')[1] + '</div>' +
        '</div>'
    );
    
    return $container;
}

// Format tampilan siswa yang terpilih
function formatSiswaSelection(siswa) {
    return siswa.text;
}

    // Data mata pelajaran dari PHP
    const mapelData = <?php echo json_encode($mapel_data); ?>;
    const mapelContainer = document.getElementById('mapel-container');
    const btnTambahMapel = document.getElementById('btn-tambah-mapel');
    let mapelCounter = 0;
    
    // Untuk melacak mata pelajaran yang sudah ditambahkan
    let addedMapelIds = new Set();

    
    // Fungsi untuk memeriksa apakah masih ada mata pelajaran yang tersisa
    function checkRemainingMapel() {
        return mapelData.some(mapel => !addedMapelIds.has(mapel.id));
    }
    
    // Fungsi untuk memperbarui status tombol tambah mata pelajaran
    function updateTambahMapelButton() {
        if (checkRemainingMapel()) {
            btnTambahMapel.style.display = 'inline-block';
        } else {
            btnTambahMapel.style.display = 'none';
        }
    }
    
    // Fungsi untuk membuat form mata pelajaran
    function createMapelForm() {
        const mapelDiv = document.createElement('div');
        mapelDiv.className = 'row mb-3 mapel-row';
        mapelCounter++;
        
        // Membuat opsi hanya untuk mata pelajaran yang belum ditambahkan
        const ipaMapels = mapelData.filter(m => m.kategori === 'IPA' && !addedMapelIds.has(m.id));
        const ipsMapels = mapelData.filter(m => m.kategori === 'IPS' && !addedMapelIds.has(m.id));
        
        mapelDiv.innerHTML = `
            <div class="col-md-8">
                <label for="mapel_${mapelCounter}" class="form-label">Mata Pelajaran</label>
                <select class="form-select mapel-select" id="mapel_${mapelCounter}" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    ${ipaMapels.length > 0 ? `
                    <optgroup label="Mata Pelajaran IPA">
                        ${ipaMapels.map(m => 
                            `<option value="${m.id}">${m.kode} - ${m.nama}</option>`
                        ).join('')}
                    </optgroup>
                    ` : ''}
                    ${ipsMapels.length > 0 ? `
                    <optgroup label="Mata Pelajaran IPS">
                        ${ipsMapels.map(m => 
                            `<option value="${m.id}">${m.kode} - ${m.nama}</option>`
                        ).join('')}
                    </optgroup>
                    ` : ''}
                </select>
            </div>
            <div class="col-md-3">
                <label for="nilai_${mapelCounter}" class="form-label">Nilai</label>
                <input type="number" class="form-control nilai-input" id="nilai_${mapelCounter}" min="0" max="100" step="0.01">
            </div>
            <div class="col-md-1 d-flex align-items-end mb-2">
                <button type="button" class="btn btn-danger btn-hapus">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        // Tambahkan event listener untuk select mata pelajaran
        const selectMapel = mapelDiv.querySelector('.mapel-select');
        selectMapel.addEventListener('change', function() {
            const mapelId = this.value;
            const nilaiInput = mapelDiv.querySelector('.nilai-input');
            
            if (mapelId) {
                addedMapelIds.add(mapelId);
                nilaiInput.name = `nilai[${mapelId}]`;
                nilaiInput.required = true;
                updateTambahMapelButton();
            } else {
                nilaiInput.name = '';
                nilaiInput.required = false;
            }
        });
        
        // Tambahkan event listener untuk tombol hapus
        const btnHapus = mapelDiv.querySelector('.btn-hapus');
        btnHapus.addEventListener('click', function() {
            // Menghapus mata pelajaran dari set yang sudah ditambahkan
            const mapelId = mapelDiv.querySelector('.mapel-select').value;
            if (mapelId) {
                addedMapelIds.delete(mapelId);
                updateTambahMapelButton();
            }
            mapelDiv.remove();
        });
        
        mapelContainer.appendChild(mapelDiv);
        

        // Update status tombol
        updateTambahMapelButton();
    }
    
    // Fungsi untuk menampilkan semua mata pelajaran
    function showAllMapel() {
        // Kosongkan container terlebih dahulu
        mapelContainer.innerHTML = '';
        
        // Reset mata pelajaran yang sudah ditambahkan
        addedMapelIds.clear();
        
        // Tambahkan semua mata pelajaran ke set
        mapelData.forEach(mapel => addedMapelIds.add(mapel.id));
        
        // Tampilkan form untuk setiap mata pelajaran
        let mapelsByCategory = {
            'IPA': mapelData.filter(m => m.kategori === 'IPA'),
            'IPS': mapelData.filter(m => m.kategori === 'IPS')
        };
        
        // Buat header untuk setiap kategori
        for (const [category, mapels] of Object.entries(mapelsByCategory)) {
            if (mapels.length > 0) {
                const categoryHeader = document.createElement('h6');
                categoryHeader.className = 'mt-4 mb-3';
                categoryHeader.textContent = `Mata Pelajaran ${category}`;
                mapelContainer.appendChild(categoryHeader);
                
                // Buat form untuk setiap mata pelajaran dalam kategori
                mapels.forEach(mapel => {
                    const mapelRow = document.createElement('div');
                    mapelRow.className = 'row mb-3 mapel-item';
                    mapelRow.dataset.mapelId = mapel.id;
                    mapelRow.innerHTML = `
                        <div class="col-md-7">
                            <label class="form-label">${mapel.kode} - ${mapel.nama}</label>
                            <input type="hidden" name="mapel_id[]" value="${mapel.id}">
                        </div>
                        <div class="col-md-4">
                            <input type="number" class="form-control" name="nilai[${mapel.id}]" min="0" max="100" step="0.01" placeholder="Nilai">
                        </div>
                        <div class="col-md-1 d-flex align-items-center">
                            <button type="button" class="btn btn-danger btn-hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    
                    // Tambahkan event listener untuk tombol hapus
                    const btnHapus = mapelRow.querySelector('.btn-hapus');
                    btnHapus.addEventListener('click', function() {
                        const mapelId = mapelRow.dataset.mapelId;
                        addedMapelIds.delete(mapelId);
                        mapelRow.remove();
                        updateTambahMapelButton();
                        
                        // Periksa jika kategori ini kosong dan hapus header jika perlu
                        const remainingInCategory = mapelContainer.querySelectorAll(`.mapel-item[data-mapel-id^="${mapel.kategori}"]`).length;
                        if (remainingInCategory === 0) {
                            const headers = mapelContainer.querySelectorAll('h6');
                            headers.forEach(header => {
                                if (header.textContent === `Mata Pelajaran ${category}`) {
                                    header.remove();
                                }
                            });
                        }
                    });
                    
                    mapelContainer.appendChild(mapelRow);
                });
            }
        }
        
        // Update status tombol tambah mata pelajaran (sembunyikan karena sudah ditampilkan semua)
        updateTambahMapelButton();
    }
    
    // Event listener untuk tombol tambah mata pelajaran
    btnTambahMapel.addEventListener('click', createMapelForm);
    
    // Event listener untuk tombol tampilkan semua mata pelajaran
    document.getElementById('btn-tampilkan-semua').addEventListener('click', showAllMapel);
    
    // Validasi format tahun ajaran
    document.getElementById('form-nilai').addEventListener('submit', function(event) {
        const tahunAjaran = document.getElementById('tahun_ajaran').value;
        const pattern = /^\d{4}\/\d{4}$/;
        
        if (!pattern.test(tahunAjaran)) {
            event.preventDefault();
            alert('Format tahun ajaran harus YYYY/YYYY (contoh: 2025/2026)');
        }

        // Validasi: minimal ada satu mata pelajaran yang dipilih dan nilai diisi
        const nilaiInputs = document.querySelectorAll('input[name^="nilai["]');
        let hasValue = false;
        
        nilaiInputs.forEach(input => {
            if (input.value !== '') {
                hasValue = true;
            }
        });
        
        if (!hasValue) {
            event.preventDefault();
            alert('Minimal satu nilai mata pelajaran harus diisi');
        }
    });
    
    // Tambahkan form mata pelajaran pertama saat halaman dimuat
    createMapelForm();
});

</script>

<?php require_once '../include/footer.php'; 
ob_end_flush();
?>
