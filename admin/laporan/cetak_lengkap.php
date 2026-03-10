<?php
// Start output buffering
ob_start();

// Include database configuration
require_once '../../database/config.php';

// Cek parameter
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$jenis_kelamin = isset($_GET['jenis_kelamin']) ? $_GET['jenis_kelamin'] : '';
$hasil_prediksi = isset($_GET['hasil_prediksi']) ? $_GET['hasil_prediksi'] : '';

// Dapatkan informasi sekolah
$query_sekolah = "SELECT * FROM settings WHERE id = 1";
$result_sekolah = mysqli_query($conn, $query_sekolah);
$sekolah = mysqli_fetch_assoc($result_sekolah);

if (!$sekolah) {
    // Default values jika data sekolah tidak ada
    $sekolah = [
        'nama_sekolah' => 'SMA NEGERI CIWARU',
        'alamat' => 'Jl. Raya Ciwaru No. 123, Kabupaten Kuningan',
        'telepon' => '(0232) 123456',
        'email' => 'info@smanegericiwaru.sch.id',
        'website' => 'www.smanegericiwaru.sch.id',
        'kepala_sekolah' => 'Drs. Nama Kepala Sekolah',
        'nip_kepala_sekolah' => '196xxxxxxxx'
    ];
}

// Query untuk data kelas
$kelas_name = 'Semua Kelas'; // Default jika tidak ada filter kelas
if (!empty($kelas_id)) {
    $query_kelas = "SELECT nama_kelas FROM kelas WHERE id = " . mysqli_real_escape_string($conn, $kelas_id);
    $result_kelas = mysqli_query($conn, $query_kelas);
    if ($result_kelas && mysqli_num_rows($result_kelas) > 0) {
        $kelas = mysqli_fetch_assoc($result_kelas);
        $kelas_name = $kelas['nama_kelas'];
    }
}

// ==== BAGIAN 1: DATA SISWA ====
// Base query untuk data siswa
$query_siswa = "SELECT s.id, s.nis, s.nisn, s.nama_lengkap, s.jenis_kelamin, 
          s.tempat_lahir, s.tanggal_lahir, k.nama_kelas, 
          (SELECT COUNT(*) FROM hasil_klasifikasi WHERE siswa_id = s.id) as sudah_jurusan,
          (SELECT hasil_prediksi FROM hasil_klasifikasi WHERE siswa_id = s.id LIMIT 1) as hasil_prediksi
          FROM siswa s
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE s.status = 'aktif'";

// Apply filters untuk data siswa
if (!empty($kelas_id)) {
    $query_siswa .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query_siswa .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}
if (!empty($hasil_prediksi)) {
    $query_siswa .= " AND (SELECT hasil_prediksi FROM hasil_klasifikasi WHERE siswa_id = s.id LIMIT 1) = '" . mysqli_real_escape_string($conn, $hasil_prediksi) . "'";
}

$query_siswa .= " ORDER BY k.nama_kelas, s.nama_lengkap";
$result_siswa = mysqli_query($conn, $query_siswa);

// Statistik untuk ringkasan siswa
$query_summary = "SELECT 
                COUNT(CASE WHEN s.jenis_kelamin = 'L' THEN 1 END) as total_laki,
                COUNT(CASE WHEN s.jenis_kelamin = 'P' THEN 1 END) as total_perempuan,
                COUNT(CASE WHEN hk.id IS NOT NULL THEN 1 END) as total_terjuruskan,
                COUNT(CASE WHEN hk.id IS NULL THEN 1 END) as total_belum_jurusan
                FROM siswa s
                LEFT JOIN hasil_klasifikasi hk ON s.id = hk.siswa_id
                WHERE s.status = 'aktif'";

if (!empty($kelas_id)) {
    $query_summary .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query_summary .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}
if (!empty($hasil_prediksi)) {
    $query_summary .= " AND (SELECT hasil_prediksi FROM hasil_klasifikasi WHERE siswa_id = s.id LIMIT 1) = '" . mysqli_real_escape_string($conn, $hasil_prediksi) . "'";
}

$result_summary = mysqli_query($conn, $query_summary);
$summary = mysqli_fetch_assoc($result_summary);

// Query untuk statistik jurusan
$query_jurusan = "SELECT 
                 COUNT(CASE WHEN hk.hasil_prediksi = 'IPA' THEN 1 END) as total_ipa,
                 COUNT(CASE WHEN hk.hasil_prediksi = 'IPS' THEN 1 END) as total_ips
                 FROM hasil_klasifikasi hk
                 JOIN siswa s ON hk.siswa_id = s.id
                 WHERE s.status = 'aktif'";

if (!empty($kelas_id)) {
    $query_jurusan .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query_jurusan .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}
if (!empty($hasil_prediksi)) {
    $query_jurusan .= " AND hk.hasil_prediksi = '" . mysqli_real_escape_string($conn, $hasil_prediksi) . "'";
}

$result_jurusan = mysqli_query($conn, $query_jurusan);
$jurusan = mysqli_fetch_assoc($result_jurusan);

// ==== BAGIAN 2: DATA PENJURUSAN LENGKAP ====
// Base query untuk data penjurusan detail
$query_penjurusan = "SELECT p.id, s.nis, s.nama_lengkap, k.nama_kelas, p.nilai_mapel_ipa, 
          p.nilai_mapel_ips, p.nilai_iq, p.kategori_iq, p.minat, p.hasil_prediksi, p.created_at 
          FROM hasil_klasifikasi p
          JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE 1=1";

// Apply filters
if (!empty($kelas_id)) {
    $query_penjurusan .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query_penjurusan .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}
if (!empty($hasil_prediksi)) {
    $query_penjurusan .= " AND p.hasil_prediksi = '" . mysqli_real_escape_string($conn, $hasil_prediksi) . "'";
}

$query_penjurusan .= " ORDER BY s.nama_lengkap";
$result_penjurusan = mysqli_query($conn, $query_penjurusan);

// Judul laporan
$title = "LAPORAN LENGKAP DATA SISWA DAN PENJURUSAN";
if (!empty($kelas_name) && $kelas_name != 'Semua Kelas') {
    $title .= " KELAS " . $kelas_name;
}
if (!empty($jenis_kelamin)) {
    $gender = $jenis_kelamin == 'L' ? "LAKI-LAKI" : "PEREMPUAN";
    $title .= " " . $gender;
}
if (!empty($hasil_prediksi)) {
    $title .= " JURUSAN " . $hasil_prediksi;
}

// Dapatkan tanggal untuk laporan
$tanggal_cetak = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Lengkap Data Siswa dan Penjurusan</title>
    <style>
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
        .subtitle {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            font-size: 14px;
            background-color: #f2f2f2;
            padding: 5px;
            border-radius: 5px;
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
            display: flex;
            flex-wrap: wrap;
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
        .page-break {
            page-break-before: always;
        }
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            button {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
        }
        .empty-data {
            font-style: italic;
            color: #777;
        }
        .filter-info {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" style="position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">
        Cetak Laporan
    </button>
    
    <div class="header">
        <h2><?php echo $sekolah['nama_sekolah']; ?></h2>
        <p><?php echo $sekolah['alamat']; ?></p>
        <p>Telp: <?php echo $sekolah['telepon']; ?> | Email: <?php echo $sekolah['email']; ?> | Website: <?php echo $sekolah['website']; ?></p>
    </div>
    
    <div class="title">
        <?php echo $title; ?>
        <br>TAHUN AJARAN <?php echo date('Y'); ?>/<?php echo date('Y')+1; ?>
    </div>
    
    <div class="filter-info">
        <table border="0" style="border: none; width: auto;">
            <tr style="border: none;">
                <td style="border: none; padding: 2px 5px; width: 100px;">Tanggal Cetak</td>
                <td style="border: none; padding: 2px 5px;">: <?php echo $tanggal_cetak; ?></td>
            </tr>
            <?php if (!empty($kelas_name) && $kelas_name != 'Semua Kelas'): ?>
            <tr style="border: none;">
                <td style="border: none; padding: 2px 5px;">Kelas</td>
                <td style="border: none; padding: 2px 5px;">: <?php echo $kelas_name; ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($jenis_kelamin)): ?>
            <tr style="border: none;">
                <td style="border: none; padding: 2px 5px;">Jenis Kelamin</td>
                <td style="border: none; padding: 2px 5px;">: <?php echo $jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($hasil_prediksi)): ?>
            <tr style="border: none;">
                <td style="border: none; padding: 2px 5px;">Jurusan</td>
                <td style="border: none; padding: 2px 5px;">: <?php echo $hasil_prediksi; ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Ringkasan Data -->
    <div class="summary">
        <div class="summary-item">
            <div class="summary-title">Total Siswa</div>
            <div class="summary-value"><?php echo ($summary['total_laki'] + $summary['total_perempuan']); ?></div>
            <div>Laki-laki: <?php echo $summary['total_laki']; ?>, Perempuan: <?php echo $summary['total_perempuan']; ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-title">Siswa Terjuruskan</div>
            <div class="summary-value"><?php echo $summary['total_terjuruskan']; ?></div>
            <div>Belum terjuruskan: <?php echo $summary['total_belum_jurusan']; ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-title">Jurusan IPA</div>
            <div class="summary-value"><?php echo $jurusan['total_ipa']; ?></div>
            <div><?php echo $summary['total_terjuruskan'] > 0 ? number_format(($jurusan['total_ipa'] / $summary['total_terjuruskan']) * 100, 1) : 0; ?>% dari total terjuruskan</div>
        </div>
        <div class="summary-item">
            <div class="summary-title">Jurusan IPS</div>
            <div class="summary-value"><?php echo $jurusan['total_ips']; ?></div>
            <div><?php echo $summary['total_terjuruskan'] > 0 ? number_format(($jurusan['total_ips'] / $summary['total_terjuruskan']) * 100, 1) : 0; ?>% dari total terjuruskan</div>
        </div>
    </div>
    
    <!-- BAGIAN 1: DAFTAR SISWA -->
    <div class="subtitle">BAGIAN I: DAFTAR SISWA</div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama Lengkap</th>
                <th>L/P</th>
                <th>TTL</th>
                <th>Kelas</th>
                <th>Status Jurusan</th>
                <th>Hasil Prediksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if ($result_siswa && mysqli_num_rows($result_siswa) > 0) {
                while ($row = mysqli_fetch_assoc($result_siswa)) {
                    $status_jurusan = $row['sudah_jurusan'] > 0 ? "Sudah" : "Belum";
                    $hasil_prediksi_siswa = !empty($row['hasil_prediksi']) ? $row['hasil_prediksi'] : "<span class='empty-data'>Belum diisi</span>";
                    $nis = !empty($row['nis']) ? $row['nis'] : "<span class='empty-data'>Belum diisi</span>";
                    $nisn = !empty($row['nisn']) ? $row['nisn'] : "<span class='empty-data'>Belum diisi</span>";
                    $nama_kelas = !empty($row['nama_kelas']) ? $row['nama_kelas'] : "<span class='empty-data'>Belum diisi</span>";
                    
                    if (!empty($row['tempat_lahir']) && !empty($row['tanggal_lahir'])) {
                        $ttl = $row['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($row['tanggal_lahir']));
                    } else {
                        $ttl = "<span class='empty-data'>Data TTL belum lengkap</span>";
                    }
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $nis; ?></td>
                <td><?php echo $nisn; ?></td>
                <td><?php echo $row['nama_lengkap']; ?></td>
                <td><?php echo $row['jenis_kelamin']; ?></td>
                <td><?php echo $ttl; ?></td>
                <td><?php echo $nama_kelas; ?></td>
                <td><?php echo $status_jurusan; ?></td>
                <td><?php echo $hasil_prediksi_siswa; ?></td>
            </tr>
            <?php
                }
            } else {
            ?>
            <tr>
                <td colspan="9" style="text-align: center;">Data siswa tidak ditemukan</td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    
    <!-- Page Break -->
    <div class="page-break"></div>
    
    <!-- Header Pada Halaman Baru -->
    <div class="header">
        <h2><?php echo $sekolah['nama_sekolah']; ?></h2>
        <p><?php echo $sekolah['alamat']; ?></p>
        <p>Telp: <?php echo $sekolah['telepon']; ?> | Email: <?php echo $sekolah['email']; ?> | Website: <?php echo $sekolah['website']; ?></p>
    </div>
    
    <!-- BAGIAN 2: DATA PENJURUSAN -->
    <div class="subtitle">BAGIAN II: DATA DETAIL PENJURUSAN</div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Nilai IPA</th>
                <th>Nilai IPS</th>
                <th>Nilai IQ</th>
                <th>Kategori IQ</th>
                <th>Minat</th>
                <th>Hasil Penjurusan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result_penjurusan && mysqli_num_rows($result_penjurusan) > 0) {
                $no = 1;
                while($row = mysqli_fetch_assoc($result_penjurusan)):
            ?>
            <tr>
                <td style="text-align: center;"><?php echo $no++; ?></td>
                <td><?php echo $row['nis']; ?></td>
                <td><?php echo $row['nama_lengkap']; ?></td>
                <td><?php echo $row['nama_kelas']; ?></td>
                <td style="text-align: center;"><?php echo $row['nilai_mapel_ipa']; ?></td>
                <td style="text-align: center;"><?php echo $row['nilai_mapel_ips']; ?></td>
                <td style="text-align: center;"><?php echo $row['nilai_iq']; ?></td>
                <td style="text-align: center;"><?php echo $row['kategori_iq']; ?></td>
                <td style="text-align: center;"><?php echo $row['minat']; ?></td>
                <td style="text-align: center;"><?php echo $row['hasil_prediksi']; ?></td>
            </tr>
            <?php 
                endwhile;
            } else {
            ?>
            <tr>
                <td colspan="10" style="text-align: center;">Data penjurusan tidak ditemukan</td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Kuningan, <?php echo date('d F Y'); ?></p>
        <p>Kepala Sekolah</p>
        <br><br><br>
        <p><b><?php echo $sekolah['kepala_sekolah']; ?></b></p>
        <p>NIP. <?php echo $sekolah['nip_kepala_sekolah']; ?></p>
    </div>
    
    <script>
        window.onload = function() {
            // Auto print when page loads - uncomment if needed
            // setTimeout(function() {
            //     window.print();
            // }, 500);
        }
    </script>
</body>
</html>
<?php
// Output buffer flush
ob_end_flush();
?>