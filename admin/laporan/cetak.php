<?php
require_once '../../database/config.php';

// Cek jenis laporan
$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($type != 'siswa') {
    die("Jenis laporan tidak valid!");
}

// Filter
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$jenis_kelamin = isset($_GET['jenis_kelamin']) ? $_GET['jenis_kelamin'] : '';

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

// Base query
$query = "SELECT s.id, s.nis, s.nisn, s.nama_lengkap, s.jenis_kelamin, 
          s.tempat_lahir, s.tanggal_lahir, k.nama_kelas, 
          (SELECT COUNT(*) FROM prediksi_jurusan WHERE siswa_id = s.id) as sudah_jurusan,
          (SELECT hasil_prediksi FROM prediksi_jurusan WHERE siswa_id = s.id LIMIT 1) as hasil_prediksi
          FROM siswa s
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE s.status = 'aktif'";

// Apply filters
if (!empty($kelas_id)) {
    $query .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}

$query .= " ORDER BY k.nama_kelas, s.nama_lengkap";
$result = mysqli_query($conn, $query);

// Statistik untuk ringkasan
$query_summary = "SELECT 
                COUNT(CASE WHEN s.jenis_kelamin = 'L' THEN 1 END) as total_laki,
                COUNT(CASE WHEN s.jenis_kelamin = 'P' THEN 1 END) as total_perempuan,
                COUNT(CASE WHEN pj.id IS NOT NULL THEN 1 END) as total_terjuruskan,
                COUNT(CASE WHEN pj.id IS NULL THEN 1 END) as total_belum_jurusan
                FROM siswa s
                LEFT JOIN prediksi_jurusan pj ON s.id = pj.siswa_id
                WHERE s.status = 'aktif'";

if (!empty($kelas_id)) {
    $query_summary .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query_summary .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}

$result_summary = mysqli_query($conn, $query_summary);
$summary = mysqli_fetch_assoc($result_summary);

// Query untuk statistik jurusan
$query_jurusan = "SELECT 
                 COUNT(CASE WHEN pj.hasil_prediksi = 'IPA' THEN 1 END) as total_ipa,
                 COUNT(CASE WHEN pj.hasil_prediksi = 'IPS' THEN 1 END) as total_ips
                 FROM prediksi_jurusan pj
                 JOIN siswa s ON pj.siswa_id = s.id
                 WHERE s.status = 'aktif'";

if (!empty($kelas_id)) {
    $query_jurusan .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query_jurusan .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}

$result_jurusan = mysqli_query($conn, $query_jurusan);
$jurusan = mysqli_fetch_assoc($result_jurusan);

// Judul laporan
$title = "LAPORAN DATA SISWA";
if (!empty($kelas_name) && $kelas_name != 'Semua Kelas') {
    $title .= " KELAS " . $kelas_name;
}
if (!empty($jenis_kelamin)) {
    $gender = $jenis_kelamin == 'L' ? "LAKI-LAKI" : "PEREMPUAN";
    $title .= " " . $gender;
}

// Dapatkan tanggal untuk laporan
$date = date('d-m-Y');

// Set header untuk dokumen PDF
header("Content-type: text/html");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Siswa</title>
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
        .empty-data {
            font-style: italic;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><?php echo $sekolah['nama_sekolah']; ?></h2>
        <p><?php echo $sekolah['alamat']; ?></p>
        <p>Telp: <?php echo $sekolah['telepon']; ?> | Email: <?php echo $sekolah['email']; ?> | Website: <?php echo $sekolah['website']; ?></p>
    </div>
    
    <div class="title">
        <?php echo $title; ?>
        <br>TAHUN AJARAN <?php echo date('Y'); ?>/<?php echo date('Y')+1; ?>
    </div>
    
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
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $status_jurusan = $row['sudah_jurusan'] > 0 ? "Sudah" : "Belum";
                    $hasil_prediksi = !empty($row['hasil_prediksi']) ? $row['hasil_prediksi'] : "<span class='empty-data'>Belum diisi</span>";
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
                <td><?php echo $hasil_prediksi; ?></td>
            </tr>
            <?php
                }
            } else {
            ?>
            <tr>
                <td colspan="9" style="text-align: center;">Data tidak ditemukan</td>
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
    
    <button onclick="window.print()" style="position: fixed; top: 20px; right: 20px; padding: 10px 20px;">
        Cetak Laporan
    </button>
    
    <script>
        window.onload = function() {
            // Auto print when page loads
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>