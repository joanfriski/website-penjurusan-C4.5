<?php
// Start output buffering
ob_start();

// Include database configuration
require_once '../../database/config.php';

// Get filter parameters
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$hasil_prediksi = isset($_GET['hasil_prediksi']) ? $_GET['hasil_prediksi'] : '';

// Base query
$query = "SELECT p.id, s.nis, s.nama_lengkap, k.nama_kelas, p.nilai_mapel_ipa, 
          p.nilai_mapel_ips, p.nilai_iq, p.kategori_iq, p.minat, p.hasil_prediksi, p.created_at 
          FROM hasil_klasifikasi p
          JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE 1=1";

// Apply filters
if (!empty($kelas_id)) {
    $query .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($hasil_prediksi)) {
    $query .= " AND p.hasil_prediksi = '" . mysqli_real_escape_string($conn, $hasil_prediksi) . "'";
}

$query .= " ORDER BY s.nama_lengkap";
$result = mysqli_query($conn, $query);

// Get school information
// Mengubah query dari tabel sekolah ke tabel settings
$query_settings = "SELECT nama_sekolah, alamat AS alamat_sekolah, telepon, email, website, kepala_sekolah, nip_kepala_sekolah FROM settings LIMIT 1";
$result_settings = mysqli_query($conn, $query_settings);
$settings = mysqli_fetch_assoc($result_settings);

// Get filter information
$filter_kelas = "";
if (!empty($kelas_id)) {
    $query_kelas = "SELECT nama_kelas FROM kelas WHERE id = " . mysqli_real_escape_string($conn, $kelas_id);
    $result_kelas = mysqli_query($conn, $query_kelas);
    $kelas = mysqli_fetch_assoc($result_kelas);
    $filter_kelas = $kelas['nama_kelas'];
}

// Hitung jumlah per jurusan untuk ringkasan
$count_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN hasil_prediksi = 'IPA' THEN 1 ELSE 0 END) as total_ipa,
    SUM(CASE WHEN hasil_prediksi = 'IPS' THEN 1 ELSE 0 END) as total_ips
FROM hasil_klasifikasi p
JOIN siswa s ON p.siswa_id = s.id
WHERE 1=1";

// Apply the same filters to count query
if (!empty($kelas_id)) {
    $count_query .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($hasil_prediksi)) {
    $count_query .= " AND p.hasil_prediksi = '" . mysqli_real_escape_string($conn, $hasil_prediksi) . "'";
}

$count_result = mysqli_query($conn, $count_query);
$count_data = mysqli_fetch_assoc($count_result);
$total_siswa = $count_data['total'];
$total_ipa = $count_data['total_ipa'];
$total_ips = $count_data['total_ips'];

// Get current date
$tanggal_cetak = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hasil Penjurusan Siswa</title>
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
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .school-address {
            font-size: 12px;
            margin: 5px 0;
        }
        .school-contact {
            font-size: 12px;
            margin: 5px 0;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .filter-info {
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .summary table {
            width: 50%;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .signature {
            margin-top: 60px;
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
</head>
<body>
    <button onclick="window.print()" style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; float: right; margin-bottom: 10px;">Cetak</button>
    
    <div class="header">
        <p class="school-name"><?php echo isset($settings['nama_sekolah']) ? strtoupper($settings['nama_sekolah']) : 'SMA NEGERI 1 CIWARU'; ?></p>
        <p class="school-address"><?php echo isset($settings['alamat_sekolah']) ? $settings['alamat_sekolah'] : 'Jl. Raya Ciwaru No. 123, Kecamatan Ciwaru, Kabupaten Kuningan'; ?></p>
        <p class="school-contact">
            Telp: <?php echo isset($settings['telepon']) ? $settings['telepon'] : '-'; ?> | 
            Email: <?php echo isset($settings['email']) ? $settings['email'] : '-'; ?> | 
            Website: <?php echo isset($settings['website']) ? $settings['website'] : '-'; ?>
        </p>
    </div>

    <div class="report-title">
        LAPORAN HASIL PENJURUSAN SISWA
    </div>

    <div class="filter-info">
        <table border="0" style="border: none; width: auto;">
            <tr style="border: none;">
                <td style="border: none; padding: 2px 5px; width: 100px;">Tanggal Cetak</td>
                <td style="border: none; padding: 2px 5px;">: <?php echo $tanggal_cetak; ?></td>
            </tr>
            <?php if (!empty($filter_kelas)): ?>
            <tr style="border: none;">
                <td style="border: none; padding: 2px 5px;">Filter Kelas</td>
                <td style="border: none; padding: 2px 5px;">: <?php echo $filter_kelas; ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($hasil_prediksi)): ?>
            <tr style="border: none;">
                <td style="border: none; padding: 2px 5px;">Filter Jurusan</td>
                <td style="border: none; padding: 2px 5px;">: <?php echo $hasil_prediksi; ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="summary">
        <p><strong>Ringkasan:</strong></p>
        <table>
            <tr>
                <th>Total Siswa</th>
                <th>Jurusan IPA</th>
                <th>Jurusan IPS</th>
            </tr>
            <tr>
                <td style="text-align: center;"><?php echo $total_siswa; ?></td>
                <td style="text-align: center;"><?php echo $total_ipa; ?></td>
                <td style="text-align: center;"><?php echo $total_ips; ?></td>
            </tr>
        </table>
    </div>

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
            if (mysqli_num_rows($result) > 0):
                $no = 1;
                while($row = mysqli_fetch_assoc($result)):
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
            else:
            ?>
            <tr>
                <td colspan="10" style="text-align: center;">Tidak ada data penjurusan yang ditemukan.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Ciwaru, <?php echo $tanggal_cetak; ?></p>
        <p>Kepala Sekolah</p>
        <div class="signature"></div>
        <p><strong><?php echo isset($settings['kepala_sekolah']) ? $settings['kepala_sekolah'] : '____________________'; ?></strong></p>
        <p>NIP. <?php echo isset($settings['nip_kepala_sekolah']) ? $settings['nip_kepala_sekolah'] : '.........................'; ?></p>
    </div>
</body>
</html>
<?php
// Output buffer flush
ob_end_flush();
?>