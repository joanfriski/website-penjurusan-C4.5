<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';
require_once 'c45_algorithm.php';
?>


<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Pohon Keputusan (Decision Tree)</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="visualisasi.php" class="btn btn-sm btn-outline-primary">Visualisasi Pohon Keputusan</a>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Pembentukan Pohon Keputusan</h5>
                        </div>
                        <div class="card-body">
                            <p>Pohon keputusan dibuat menggunakan algoritma C4.5 berdasarkan data-data berikut:</p>
                            <ul>
                                <li>Nilai Mata Pelajaran (IPA dan IPS)</li>
                                <li>Hasil Test IQ</li>
                                <li>Minat Siswa</li>
                            </ul>
                            
                            <form action="" method="post" class="mt-3">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                                        <select name="tahun_ajaran" id="tahun_ajaran" class="form-select" required>
                                            <option value="">Pilih Tahun Ajaran</option>
                                            <?php 
                                            // Query untuk mendapatkan daftar tahun ajaran dari tabel nilai
                                            $query_tahun = mysqli_query($conn, "SELECT DISTINCT tahun_ajaran FROM nilai ORDER BY tahun_ajaran DESC");
                                            while($row = mysqli_fetch_assoc($query_tahun)) {
                                                echo "<option value='".$row['tahun_ajaran']."'>".$row['tahun_ajaran']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="kelas" class="form-label">Kelas</label>
                                        <select name="kelas" id="kelas" class="form-select" required>
                                            <option value="">Pilih Kelas</option>
                                            <?php 
                                            // Query untuk mendapatkan daftar kelas
                                            $query_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
                                            while($row = mysqli_fetch_assoc($query_kelas)) {
                                                echo "<option value='".$row['id']."'>".$row['nama_kelas']." - ".$row['tahun_ajaran']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="generate" class="btn btn-primary">Bentuk Pohon Keputusan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            if(isset($_POST['generate'])) {
                $tahun_ajaran = $_POST['tahun_ajaran'];
                $kelas_id = $_POST['kelas'];
                
                // Hapus data pohon keputusan yang lama jika ada
                mysqli_query($conn, "DELETE FROM decision_tree");
                
                // Panggil fungsi untuk membuat dataset
                $dataset = createDataset($conn, $kelas_id, $tahun_ajaran);
                
                if(count($dataset) > 0) {
                    // Buat objek C4.5
                    $c45 = new C45Algorithm();
                    $c45->setDataset($dataset);
                    $c45->setAttributes(['nilai_ipa', 'nilai_ips', 'skor_iq', 'minat']);
                    $c45->setTarget('jurusan');
                    
                    // Bangun pohon keputusan
                    $tree = $c45->buildTree();
                    
                    // Simpan pohon keputusan ke database
                    $c45->saveTreeToDatabase($conn);
                    
                    $rules = $c45->generateRules();
                    ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">Pohon Keputusan Berhasil Dibentuk</h5>
                                </div>
                                <div class="card-body">
                                    <h5>Aturan (Rules) yang Dihasilkan:</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Aturan</th>
                                                    <th>Keputusan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($rules as $index => $rule): ?>
                                                <tr>
                                                    <td><?= $index+1 ?></td>
                                                    <td><?= $rule['condition'] ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= ($rule['decision'] == 'IPA') ? 'primary' : (($rule['decision'] == 'IPS') ? 'success' : 'warning') ?>">
                                                            <?= $rule['decision'] ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="visualisasi.php" class="btn btn-primary">Lihat Visualisasi Pohon</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-danger">
                        <strong>Error!</strong> Tidak dapat membuat pohon keputusan. Data tidak mencukupi untuk tahun ajaran dan kelas yang dipilih.
                    </div>
                    <?php
                }
            }
            
            // Fungsi untuk membuat dataset dari data di database
            function createDataset($conn, $kelas_id, $tahun_ajaran) {
                $dataset = [];
                
                // Query untuk mendapatkan data siswa dengan nilai IPA, IPS, test IQ, dan minat
                $query = "SELECT s.id, s.nama_lengkap,
                          AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) AS nilai_ipa,
                          AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) AS nilai_ips,
                          t.skor AS skor_iq,
                          t.kategori AS kategori_iq,
                          m.minat
                          FROM siswa s
                          JOIN nilai n ON s.id = n.siswa_id
                          JOIN mata_pelajaran mp ON n.mapel_id = mp.id
                          JOIN test_iq t ON s.id = t.siswa_id
                          JOIN minat_siswa m ON s.id = m.siswa_id
                          WHERE s.kelas_id = '$kelas_id' AND n.tahun_ajaran = '$tahun_ajaran'
                          GROUP BY s.id";
                
                $result = mysqli_query($conn, $query);
                
                if(mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $nilai_ipa_kategori = kategorikanNilai($row['nilai_ipa']);
                        $nilai_ips_kategori = kategorikanNilai($row['nilai_ips']);
                        
                        // Tentukan jurusan berdasarkan nilai, IQ, dan minat (simulasi data untuk testing)
                        $jurusan = prediksiJurusan($nilai_ipa_kategori, $nilai_ips_kategori, $row['kategori_iq'], $row['minat']);
                        
                        $dataset[] = [
                            'nilai_ipa' => $nilai_ipa_kategori,
                            'nilai_ips' => $nilai_ips_kategori,
                            'skor_iq' => $row['kategori_iq'],
                            'minat' => $row['minat'],
                            'jurusan' => $jurusan
                        ];
                    }
                }
                
                return $dataset;
            }
            
            // Fungsi untuk mengkategorikan nilai
            function kategorikanNilai($nilai) {
                if($nilai >= 85) return 'Tinggi';
                else if($nilai >= 75) return 'Sedang';
                else return 'Rendah';
            }
            
            // Fungsi untuk memprediksi jurusan (simulasi data untuk testing)
            function prediksiJurusan($nilai_ipa, $nilai_ips, $iq, $minat) {
                // Ini hanya contoh sederhana, pada implementasi sebenarnya
                // jurusan ditentukan berdasarkan data penjurusan yang sudah ada
                if($nilai_ipa == 'Tinggi' && $iq == 'Tinggi' && $minat == 'IPA') return 'IPA';
                else if($nilai_ips == 'Tinggi' && $minat == 'IPS') return 'IPS';
                else if($nilai_ipa == 'Tinggi' && $nilai_ips == 'Tinggi') {
                    return $minat; // Ikuti minat siswa jika nilai kedua jurusan tinggi
                }
                else if($nilai_ipa > $nilai_ips) return 'IPA';
                else if($nilai_ips > $nilai_ipa) return 'IPS';
                else if($minat == 'IPA') return 'IPA';
                else return 'IPS';
            }
            ?>
        </main>
    </div>
</div>

<script>
// Tambahkan script untuk menangani interaksi UI jika diperlukan
$(document).ready(function() {
    // Inisialisasi Select2 jika digunakan
    if($.fn.select2) {
        $('#tahun_ajaran').select2();
        $('#kelas').select2();
    }
});
</script>

<?php require_once '../include/footer.php'; ?>