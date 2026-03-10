<?php
require_once '../../database/config.php';
require_once '../../vendor/autoload.php'; // Pastikan library PhpSpreadsheet sudah diinstall

use PhpOffice\PhpSpreadsheet\IOFactory;

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Query untuk mendapatkan daftar kelas (untuk dropdown kelas mapping)
$kelas_query = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
$kelas_result = mysqli_query($conn, $kelas_query);

// Variable untuk menyimpan pesan
$message = '';
$messageType = '';
$preview_data = [];
$has_preview = false;
$file_path = '';
$errors = [];
$success_count = 0;
$error_count = 0;

// Tambahkan array untuk debug info
$debug_import_info = [];

// Tambahkan array untuk log detail insert nilai mapel
$debug_nilai_log = [];

// Tambahan debug khusus untuk Ekonomi
if (!isset($debug_ekonomi_all)) $debug_ekonomi_all = [];

// Dapatkan daftar kelas untuk mapping
$kelas_list = [];
while ($kelas = mysqli_fetch_assoc($kelas_result)) {
    $kelas_list[] = $kelas;
}

// Dapatkan daftar mata pelajaran
$mapel_query = "SELECT * FROM mata_pelajaran ORDER BY nama ASC";
$mapel_result = mysqli_query($conn, $mapel_query);
$mapel_list = [];
while ($mapel = mysqli_fetch_assoc($mapel_result)) {
    $mapel_list[] = $mapel;
}

// Perbaiki fungsi normalisasi nama mapel agar lebih toleran
function normalize_mapel_name($name) {
    // Hilangkan spasi, karakter non-alfabet, dan lowercase
    return strtolower(preg_replace('/[^a-z]/', '', strtolower($name)));
}

// Jika ada file yang diupload
if (isset($_POST['preview'])) {
    // Validasi file
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0) {
        $message = "File tidak valid atau tidak ditemukan.";
        $messageType = "danger";
    } else {
        $allowed_ext = ['xls', 'xlsx', 'csv'];
        $file_name = $_FILES['excel_file']['name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        if (!in_array($file_ext, $allowed_ext)) {
            $message = "File harus berformat Excel (.xls, .xlsx) atau CSV.";
            $messageType = "danger";
        } else {
            // Upload file ke folder temporary
            $upload_dir = 'temp/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_path = $upload_dir . time() . '_' . $file_name;
            move_uploaded_file($_FILES['excel_file']['tmp_name'], $file_path);
            
            // Baca file Excel
            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Ambil header kolom
            $header = array_map('strtoupper', array_map('trim', $rows[0]));
            // Buat mapping nama kolom ke index
            $col_map = array_flip($header);
            
            // Tentukan baris data mulai dari baris ke-1 (setelah header)
            $start_row = 1;
            
            // Ambil tahun ajaran untuk nilai
            $tahun_ajaran = isset($_POST['tahun_ajaran']) ? $_POST['tahun_ajaran'] : (date('Y') . '/' . (date('Y') + 1));
            
            // Opsi auto create mata pelajaran
            $auto_create_mapel = isset($_POST['auto_create_mapel']) && $_POST['auto_create_mapel'];
            
            // Array untuk menyimpan ID mata pelajaran
            $mapel_ids = [
                'geografi' => null,
                'fisika' => null,
                'kimia' => null,
                'biologi' => null,
                'sosiologi' => null,
                'ekonomi' => null
            ];
            // Dapatkan id mapel dari database (atau auto-create jika perlu)
            foreach ($mapel_ids as $mapel_nama => &$mapel_id) {
                $kategori = in_array($mapel_nama, ['fisika', 'kimia', 'biologi']) ? 'IPA' : 'IPS';
                $base_kode = strtoupper(substr($mapel_nama, 0, 3));
                $kode = $base_kode;
                $kode_cek = "SELECT COUNT(*) as cnt FROM mata_pelajaran WHERE kode = '$kode'";
                $cek_result = mysqli_query($conn, $kode_cek);
                $cnt = 0;
                if ($cek_result) {
                    $row_cnt = mysqli_fetch_assoc($cek_result);
                    $cnt = (int)$row_cnt['cnt'];
                }
                $urutan = 1;
                while ($cnt > 0) {
                    $kode = $base_kode . $urutan;
                    $kode_cek = "SELECT COUNT(*) as cnt FROM mata_pelajaran WHERE kode = '$kode'";
                    $cek_result = mysqli_query($conn, $kode_cek);
                    $cnt = 0;
                    if ($cek_result) {
                        $row_cnt = mysqli_fetch_assoc($cek_result);
                        $cnt = (int)$row_cnt['cnt'];
                    }
                    $urutan++;
                }
                $check_query = "SELECT id, nama FROM mata_pelajaran WHERE LOWER(nama) = LOWER('" . ucfirst($mapel_nama) . "')";
                $check_result = mysqli_query($conn, $check_query);
                if ($check_result && mysqli_num_rows($check_result) > 0) {
                    $mapel = mysqli_fetch_assoc($check_result);
                    $mapel_id = $mapel['id'];
                } else {
                    $create_mapel_query = "INSERT INTO mata_pelajaran (kode, nama, kategori) VALUES ('$kode', '" . ucfirst($mapel_nama) . "', '$kategori')";
                    if (mysqli_query($conn, $create_mapel_query)) {
                        $mapel_id = mysqli_insert_id($conn);
                    }
                }
            }
            unset($mapel_id);
            
            // Siapkan nilai default untuk field wajib siswa
            $jenis_kelamin = 'L';
            $status = 'aktif';
            
            for ($i = $start_row; $i < count($rows); $i++) {
                $row = $rows[$i];
                // Padding: pastikan jumlah kolom sama dengan header
                while (count($row) < count($header)) {
                    $row[] = '';
                }
                $row_debug = [
                    'baris' => $i + 1,
                    'data' => $row,
                    'hasil' => 'OK',
                    'errors' => [],
                    'nilai_mapel' => []
                ];
                // Skip baris kosong
                if (empty(trim(implode('', $row)))) {
                    continue;
                }
                // Ambil data dari kolom yang sesuai header
                $nama_lengkap = isset($col_map['NAMA LENGKAP']) ? mysqli_real_escape_string($conn, trim($row[$col_map['NAMA LENGKAP']])) : '';
                // KELAS
                $kelas_id = null;
                if (isset($col_map['KELAS'])) {
                    $kelas_nama = mysqli_real_escape_string($conn, trim($row[$col_map['KELAS']]));
                    if ($kelas_nama !== '') {
                        // Cari kelas, jika tidak ada insert
                        $kelas_query = "SELECT id FROM kelas WHERE nama_kelas = '$kelas_nama' LIMIT 1";
                        $kelas_result = mysqli_query($conn, $kelas_query);
                        if ($kelas_result && mysqli_num_rows($kelas_result) > 0) {
                            $kelas_row = mysqli_fetch_assoc($kelas_result);
                            $kelas_id = $kelas_row['id'];
                        } else {
                            $insert_kelas = "INSERT INTO kelas (nama_kelas) VALUES ('$kelas_nama')";
                            if (mysqli_query($conn, $insert_kelas)) {
                                $kelas_id = mysqli_insert_id($conn);
                            }
                        }
                    }
                }
                // KELAMIN
                $jenis_kelamin = 'L';
                if (isset($col_map['KELAMIN'])) {
                    $jk_val = strtoupper(trim($row[$col_map['KELAMIN']]));
                    if ($jk_val === 'L') $jenis_kelamin = 'L';
                    elseif ($jk_val === 'P') $jenis_kelamin = 'P';
                }
                // MINAT
                $minat_val = null;
                $minat_str = null;
                if (isset($col_map['MINAT'])) {
                    $minat_val = trim($row[$col_map['MINAT']]);
                    if ($minat_val == '1') $minat_str = 'IPA';
                    elseif ($minat_val == '2') $minat_str = 'IPS';
                }
                // TES IQ
                $tes_iq = null;
                if (isset($col_map['TES IQ'])) {
                    $tes_iq = is_numeric($row[$col_map['TES IQ']]) ? (int)$row[$col_map['TES IQ']] : null;
                }
                // Skip jika nama kosong atau terlalu pendek
                if (empty($nama_lengkap) || strlen($nama_lengkap) < 3) {
                    $errors[] = "Baris #" . ($i + 1) . ": Nama tidak boleh kosong atau terlalu pendek.";
                    $error_count++;
                    $row_debug['hasil'] = 'GAGAL';
                    $row_debug['errors'][] = 'Nama kosong/terlalu pendek';
                    $debug_import_info[] = $row_debug;
                    continue;
                }

                // Check for duplicate student (same name and class)
                $check_duplicate = "SELECT id FROM siswa WHERE nama_lengkap = '$nama_lengkap' AND kelas_id = " . ($kelas_id ? "'$kelas_id'" : "NULL");
                $duplicate_result = mysqli_query($conn, $check_duplicate);
                if (mysqli_num_rows($duplicate_result) > 0) {
                    $errors[] = "Baris #" . ($i + 1) . ": Data siswa dengan nama '$nama_lengkap' di kelas yang sama sudah ada.";
                    $error_count++;
                    $row_debug['hasil'] = 'GAGAL';
                    $row_debug['errors'][] = 'Data siswa duplikat';
                    $debug_import_info[] = $row_debug;
                    continue;
                }

                // Generate NIS dan NISN unik di dalam loop
                $timestamp = time();
                $random = mt_rand(1000, 9999);
                $nis_temp = "IMP" . $timestamp . $random . ($i + 1);
                $nisn_temp = "N" . $timestamp . $random . ($i + 1);
                // Insert ke database siswa
                $insert_query = "INSERT INTO siswa (nis, nisn, nama_lengkap, jenis_kelamin, kelas_id, status) 
                VALUES ('$nis_temp', '$nisn_temp', '$nama_lengkap', '$jenis_kelamin', " . ($kelas_id ? "'$kelas_id'" : "NULL") . ", '$status')";
                $result = mysqli_query($conn, $insert_query);
                if ($result) {
                    $siswa_id = mysqli_insert_id($conn);
                    $success_count++;
                    // Insert nilai mapel
                    foreach ($mapel_ids as $mapel_nama => $mapel_id) {
                        $col_header = strtoupper($mapel_nama);
                        $col_idx = null;
                        foreach ($col_map as $h => $idx) {
                            if (strpos($h, $col_header) !== false) {
                                $col_idx = $idx;
                                break;
                            }
                        }
                        $nilai = ($col_idx !== null && isset($row[$col_idx]) && is_numeric($row[$col_idx])) ? (float)$row[$col_idx] : null;
                        $debug_nilai = [
                            'siswa' => $nama_lengkap,
                            'mapel' => $mapel_nama,
                            'nilai' => $nilai,
                            'status' => '',
                            'error' => ''
                        ];
                        if ($mapel_id !== null && $nilai !== null) {
                            $check_nilai_query = "SELECT id FROM nilai WHERE siswa_id = '$siswa_id' AND mapel_id = '$mapel_id' AND tahun_ajaran = '$tahun_ajaran'";
                            $check_nilai_result = mysqli_query($conn, $check_nilai_query);
                            if ($check_nilai_result && mysqli_num_rows($check_nilai_result) > 0) {
                                $update_nilai_query = "UPDATE nilai SET nilai = '$nilai' WHERE siswa_id = '$siswa_id' AND mapel_id = '$mapel_id' AND tahun_ajaran = '$tahun_ajaran'";
                                $update_result = mysqli_query($conn, $update_nilai_query);
                                $debug_nilai['status'] = $update_result ? 'UPDATE BERHASIL' : 'GAGAL UPDATE';
                                if (!$update_result) {
                                    $debug_nilai['error'] = mysqli_error($conn);
                                    $errors[] = "Gagal update nilai $mapel_nama untuk $nama_lengkap: " . mysqli_error($conn);
                                }
                            } else {
                                $insert_nilai_query = "INSERT INTO nilai (siswa_id, mapel_id, nilai, tahun_ajaran) VALUES ('$siswa_id', '$mapel_id', '$nilai', '$tahun_ajaran')";
                                $insert_result = mysqli_query($conn, $insert_nilai_query);
                                $debug_nilai['status'] = $insert_result ? 'INSERT BERHASIL' : 'GAGAL INSERT';
                                if (!$insert_result) {
                                    $debug_nilai['error'] = mysqli_error($conn);
                                    $errors[] = "Gagal insert nilai $mapel_nama untuk $nama_lengkap: " . mysqli_error($conn);
                                }
                            }
                        } else {
                            if ($mapel_id === null) {
                                $debug_nilai['status'] = 'SKIP - Mapel tidak ditemukan';
                            } elseif ($nilai === null) {
                                $debug_nilai['status'] = 'SKIP - Nilai kosong/tidak numerik';
                            }
                        }
                        $debug_nilai_log[] = $debug_nilai;
                        $row_debug['nilai_mapel'][] = $debug_nilai;
                    }
                    // Insert MINAT ke tabel minat_siswa jika ada
                    if ($minat_str) {
                        $insert_minat = "INSERT INTO minat_siswa (siswa_id, minat) VALUES ('$siswa_id', '$minat_str')";
                        mysqli_query($conn, $insert_minat);
                    }
                    // Insert TES IQ ke tabel test_iq jika ada
                    if ($tes_iq !== null) {
                        $tanggal_iq = date('Y-m-d');
                        // Tentukan kategori IQ
                        if ($tes_iq >= 110) {
                            $kategori = 'Tinggi';
                        } elseif ($tes_iq >= 90) {
                            $kategori = 'Sedang';
                        } else {
                            $kategori = 'Rendah';
                        }
                        $keterangan = 'Import Excel';
                        $insert_iq = "INSERT INTO test_iq (siswa_id, skor, kategori, tanggal_test, keterangan) VALUES ('$siswa_id', '$tes_iq', '$kategori', '$tanggal_iq', '$keterangan')";
                        mysqli_query($conn, $insert_iq);
                    }
                    // Log aktivitas impor siswa
                    $user_id = $_SESSION['user_id'];
                    $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                                VALUES ('$user_id', 'Import data siswa', 'Nama: $nama_lengkap', '".$_SERVER['REMOTE_ADDR']."')";
                    mysqli_query($conn, $log_query);
                } else {
                    $errors[] = "Baris #" . ($i + 1) . ": Gagal menambahkan data siswa: " . mysqli_error($conn);
                    $error_count++;
                    $row_debug['hasil'] = 'GAGAL';
                    $row_debug['errors'][] = 'Gagal insert siswa: ' . mysqli_error($conn);
                }
                $debug_import_info[] = $row_debug;
            }
            
            // Setelah proses padding dan sebelum loop insert siswa, tambahkan satu baris dummy
            $dummy_row = array_fill(0, isset($rows[0]) ? count($rows[0]) : 0, '');
            $rows[] = $dummy_row;
            
            // Hapus file temporary
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // PERBAIKAN: Pesan yang lebih informatif
            $nilai_errors = array_filter($errors, function($error) {
                return strpos($error, 'nilai') !== false;
            });
            
            if (count($nilai_errors) > 0) {
                $message = "Import selesai dengan peringatan. Berhasil: $success_count siswa, Gagal: $error_count siswa. Ada " . count($nilai_errors) . " error nilai mata pelajaran.";
                $messageType = "warning";
            } else {
                $message = "Import selesai. Berhasil: $success_count siswa, Gagal: $error_count siswa.";
                $messageType = ($error_count > 0) ? "warning" : "success";
            }
            
            // Tambahkan info debug mapel ke pesan jika ada error nilai
            if (count($nilai_errors) > 0 && !empty($debug_mapel_log)) {
                $message .= " Info mapel: " . implode('; ', array_slice($debug_mapel_log, 0, 3));
            }
            
            // Jika semua berhasil dan tidak ada error nilai, redirect ke halaman index
            if ($error_count == 0 && count($nilai_errors) == 0) {
                $_SESSION['success'] = $message;
                header("Location: index.php");
                exit();
            }

            // --- Selalu simpan log debug ke file setelah proses import ---
            $debug_data = [
                'ringkasan' => [
                    'total_data' => isset($rows) ? count($rows) : 0,
                    'berhasil' => $success_count ?? 0,
                    'gagal' => $error_count ?? 0
                ],
                'status_mapel' => [],
                'mapping_kolom' => [],
                'errors' => $errors,
                'detail_per_baris' => $debug_import_info ?? []
            ];
            if (isset($mapel_ids)) {
                foreach ($mapel_ids as $mapel_nama => $mapel_id) {
                    $col_var = $mapel_nama . '_col';
                    $col_value = isset($$col_var) ? $$col_var : null;
                    $debug_data['status_mapel'][$mapel_nama] = [
                        'id' => $mapel_id,
                        'status' => $mapel_id ? 'Ditemukan' : 'Tidak ditemukan',
                        'kolom' => $col_value !== null ? ($col_value + 1) : 'Tidak dipilih'
                    ];
                    if ($col_value !== null) {
                        $header = isset($preview_data[0][$col_value]) ? $preview_data[0][$col_value] : 'Unknown';
                        $debug_data['mapping_kolom'][$mapel_nama] = "Kolom " . ($col_value + 1) . " ($header)";
                    }
                }
            }
            $msg = "[IMPORT DEBUG]\n";
            $msg .= "Total Data: " . $debug_data['ringkasan']['total_data'] . ", Berhasil: " . $debug_data['ringkasan']['berhasil'] . ", Gagal: " . $debug_data['ringkasan']['gagal'] . "\n";
            $msg .= "Status Mapel:\n";
            foreach ($debug_data['status_mapel'] as $mapel => $info) {
                $msg .= ucfirst($mapel) . ': ' . $info['status'] . ' (ID: ' . ($info['id'] ?? 'null') . ', Kolom: ' . $info['kolom'] . ")\n";
            }
            if (!empty($debug_data['mapping_kolom'])) {
                $msg .= "\nMapping Kolom:\n";
                foreach ($debug_data['mapping_kolom'] as $mapel => $mapping) {
                    $msg .= ucfirst($mapel) . ': ' . $mapping . "\n";
                }
            }
            if (!empty($debug_mapel_log)) {
                $msg .= "\nLog Mapel:\n" . implode("\n", $debug_mapel_log) . "\n";
            }
            if (!empty($debug_data['errors'])) {
                $msg .= "\nErrors:\n" . implode("\n", $debug_data['errors']) . "\n";
            }
            if (!empty($debug_nilai_log)) {
                $msg .= "\nLog Nilai Mapel:\n";
                foreach ($debug_nilai_log as $log) {
                    $msg .= "Siswa: {$log['siswa']}, Mapel: {$log['mapel']}, Nilai: {$log['nilai']}, Status: {$log['status']}, Error: {$log['error']}\n";
                }
            }
            if (!empty($debug_ekonomi_all)) {
                $msg .= "\n[DEBUG EKONOMI]" . "\n" . implode("\n", $debug_ekonomi_all) . "\n";
            }
            $log_dir = 'temp/';
            if (!file_exists($log_dir)) {
                mkdir($log_dir, 0777, true);
            }
            $log_file = $log_dir . 'debug_import_log.txt';
            file_put_contents($log_file, $msg, FILE_APPEND);
            // --- END log debug ---
        }
    }
}

// Jika form import disubmit
if (isset($_POST['import'])) {
    // Inisialisasi counter agar tidak undefined
    $success_count = 0;
    $error_count = 0;
    // Debug mode - tampilkan data POST
    if (isset($_POST['debug_mode'])) {
        echo "<pre>POST Data: ";
        print_r($_POST);
        echo "</pre>";
        exit();
    }

    // Check if file_path exists in POST data
    if (!isset($_POST['file_path']) || empty($_POST['file_path'])) {
        $message = "File path tidak ditemukan. Silahkan upload ulang file Excel.";
        $messageType = "danger";
    } else {
    $file_path = $_POST['file_path'];
    
    if (!file_exists($file_path)) {
        $message = "File tidak ditemukan. Silahkan upload ulang.";
        $messageType = "danger";
    } else {
        // Baca file Excel
        $spreadsheet = IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
            // Ambil header kolom
            $header = array_map('strtoupper', array_map('trim', $rows[0]));
            // Buat mapping nama kolom ke index
            $col_map = array_flip($header);
            
            // Tentukan baris data mulai dari baris ke-1 (setelah header)
            $start_row = 1;
        
        // Ambil tahun ajaran untuk nilai
        $tahun_ajaran = isset($_POST['tahun_ajaran']) ? $_POST['tahun_ajaran'] : (date('Y') . '/' . (date('Y') + 1));
        
        // Opsi auto create mata pelajaran
        $auto_create_mapel = isset($_POST['auto_create_mapel']) && $_POST['auto_create_mapel'];
        
            // Array untuk menyimpan ID mata pelajaran
        $mapel_ids = [
                'geografi' => null,
                'fisika' => null,
                'kimia' => null,
                'biologi' => null,
                'sosiologi' => null,
                'ekonomi' => null
            ];
            // Dapatkan id mapel dari database (atau auto-create jika perlu)
        foreach ($mapel_ids as $mapel_nama => &$mapel_id) {
                $kategori = in_array($mapel_nama, ['fisika', 'kimia', 'biologi']) ? 'IPA' : 'IPS';
                $base_kode = strtoupper(substr($mapel_nama, 0, 3));
                $kode = $base_kode;
                $kode_cek = "SELECT COUNT(*) as cnt FROM mata_pelajaran WHERE kode = '$kode'";
                $cek_result = mysqli_query($conn, $kode_cek);
                $cnt = 0;
                if ($cek_result) {
                    $row_cnt = mysqli_fetch_assoc($cek_result);
                    $cnt = (int)$row_cnt['cnt'];
                }
                $urutan = 1;
                while ($cnt > 0) {
                    $kode = $base_kode . $urutan;
                    $kode_cek = "SELECT COUNT(*) as cnt FROM mata_pelajaran WHERE kode = '$kode'";
                    $cek_result = mysqli_query($conn, $kode_cek);
                    $cnt = 0;
                    if ($cek_result) {
                        $row_cnt = mysqli_fetch_assoc($cek_result);
                        $cnt = (int)$row_cnt['cnt'];
                    }
                    $urutan++;
                }
                $check_query = "SELECT id, nama FROM mata_pelajaran WHERE LOWER(nama) = LOWER('" . ucfirst($mapel_nama) . "')";
            $check_result = mysqli_query($conn, $check_query);
            if ($check_result && mysqli_num_rows($check_result) > 0) {
                $mapel = mysqli_fetch_assoc($check_result);
                $mapel_id = $mapel['id'];
                } else {
                    $create_mapel_query = "INSERT INTO mata_pelajaran (kode, nama, kategori) VALUES ('$kode', '" . ucfirst($mapel_nama) . "', '$kategori')";
                if (mysqli_query($conn, $create_mapel_query)) {
                    $mapel_id = mysqli_insert_id($conn);
                }
            }
        }
            unset($mapel_id);
            
            // Siapkan nilai default untuk field wajib siswa
            $jenis_kelamin = 'L';
            $status = 'aktif';
            
            for ($i = $start_row; $i < count($rows); $i++) {
                $row = $rows[$i];
                // Padding: pastikan jumlah kolom sama dengan header
                while (count($row) < count($header)) {
                    $row[] = '';
                }
                $row_debug = [
                    'baris' => $i + 1,
                    'data' => $row,
                    'hasil' => 'OK',
                    'errors' => [],
                    'nilai_mapel' => []
                ];
            // Skip baris kosong
            if (empty(trim(implode('', $row)))) {
                continue;
            }
                // Ambil data dari kolom yang sesuai header
                $nama_lengkap = isset($col_map['NAMA LENGKAP']) ? mysqli_real_escape_string($conn, trim($row[$col_map['NAMA LENGKAP']])) : '';
                // KELAS
            $kelas_id = null;
                if (isset($col_map['KELAS'])) {
                    $kelas_nama = mysqli_real_escape_string($conn, trim($row[$col_map['KELAS']]));
                    if ($kelas_nama !== '') {
                        // Cari kelas, jika tidak ada insert
                        $kelas_query = "SELECT id FROM kelas WHERE nama_kelas = '$kelas_nama' LIMIT 1";
                        $kelas_result = mysqli_query($conn, $kelas_query);
                        if ($kelas_result && mysqli_num_rows($kelas_result) > 0) {
                            $kelas_row = mysqli_fetch_assoc($kelas_result);
                            $kelas_id = $kelas_row['id'];
                        } else {
                            $insert_kelas = "INSERT INTO kelas (nama_kelas) VALUES ('$kelas_nama')";
                            if (mysqli_query($conn, $insert_kelas)) {
                                $kelas_id = mysqli_insert_id($conn);
                            }
                        }
                    }
                }
                // KELAMIN
                $jenis_kelamin = 'L';
                if (isset($col_map['KELAMIN'])) {
                    $jk_val = strtoupper(trim($row[$col_map['KELAMIN']]));
                    if ($jk_val === 'L') $jenis_kelamin = 'L';
                    elseif ($jk_val === 'P') $jenis_kelamin = 'P';
                }
                // MINAT
                $minat_val = null;
                $minat_str = null;
                if (isset($col_map['MINAT'])) {
                    $minat_val = trim($row[$col_map['MINAT']]);
                    if ($minat_val == '1') $minat_str = 'IPA';
                    elseif ($minat_val == '2') $minat_str = 'IPS';
                }
                // TES IQ
                $tes_iq = null;
                if (isset($col_map['TES IQ'])) {
                    $tes_iq = is_numeric($row[$col_map['TES IQ']]) ? (int)$row[$col_map['TES IQ']] : null;
                }
                // Skip jika nama kosong atau terlalu pendek
                if (empty($nama_lengkap) || strlen($nama_lengkap) < 3) {
                    $errors[] = "Baris #" . ($i + 1) . ": Nama tidak boleh kosong atau terlalu pendek.";
                $error_count++;
                    $row_debug['hasil'] = 'GAGAL';
                    $row_debug['errors'][] = 'Nama kosong/terlalu pendek';
                    $debug_import_info[] = $row_debug;
                continue;
            }

                // Check for duplicate student (same name and class)
                $check_duplicate = "SELECT id FROM siswa WHERE nama_lengkap = '$nama_lengkap' AND kelas_id = " . ($kelas_id ? "'$kelas_id'" : "NULL");
                $duplicate_result = mysqli_query($conn, $check_duplicate);
                if (mysqli_num_rows($duplicate_result) > 0) {
                    $errors[] = "Baris #" . ($i + 1) . ": Data siswa dengan nama '$nama_lengkap' di kelas yang sama sudah ada.";
                    $error_count++;
                    $row_debug['hasil'] = 'GAGAL';
                    $row_debug['errors'][] = 'Data siswa duplikat';
                    $debug_import_info[] = $row_debug;
                    continue;
                }

                // Generate NIS dan NISN unik di dalam loop
                $timestamp = time();
                $random = mt_rand(1000, 9999);
                $nis_temp = "IMP" . $timestamp . $random . ($i + 1);
                $nisn_temp = "N" . $timestamp . $random . ($i + 1);
                // Insert ke database siswa
                $insert_query = "INSERT INTO siswa (nis, nisn, nama_lengkap, jenis_kelamin, kelas_id, status) 
                VALUES ('$nis_temp', '$nisn_temp', '$nama_lengkap', '$jenis_kelamin', " . ($kelas_id ? "'$kelas_id'" : "NULL") . ", '$status')";
                $result = mysqli_query($conn, $insert_query);
                if ($result) {
                    $siswa_id = mysqli_insert_id($conn);
                    $success_count++;
                    // Insert nilai mapel
                    foreach ($mapel_ids as $mapel_nama => $mapel_id) {
                        $col_header = strtoupper($mapel_nama);
                        $col_idx = null;
                        foreach ($col_map as $h => $idx) {
                            if (strpos($h, $col_header) !== false) {
                                $col_idx = $idx;
                            break;
                        }
                    }
                        $nilai = ($col_idx !== null && isset($row[$col_idx]) && is_numeric($row[$col_idx])) ? (float)$row[$col_idx] : null;
                        $debug_nilai = [
                            'siswa' => $nama_lengkap,
                            'mapel' => $mapel_nama,
                            'nilai' => $nilai,
                            'status' => '',
                            'error' => ''
                        ];
                        if ($mapel_id !== null && $nilai !== null) {
$check_nilai_query = "SELECT id FROM nilai WHERE siswa_id = '$siswa_id' AND mapel_id = '$mapel_id' AND tahun_ajaran = '$tahun_ajaran'";
$check_nilai_result = mysqli_query($conn, $check_nilai_query);
if ($check_nilai_result && mysqli_num_rows($check_nilai_result) > 0) {
    $update_nilai_query = "UPDATE nilai SET nilai = '$nilai' WHERE siswa_id = '$siswa_id' AND mapel_id = '$mapel_id' AND tahun_ajaran = '$tahun_ajaran'";
                                $update_result = mysqli_query($conn, $update_nilai_query);
                                $debug_nilai['status'] = $update_result ? 'UPDATE BERHASIL' : 'GAGAL UPDATE';
                                if (!$update_result) {
                                    $debug_nilai['error'] = mysqli_error($conn);
                                    $errors[] = "Gagal update nilai $mapel_nama untuk $nama_lengkap: " . mysqli_error($conn);
    }
} else {
                                $insert_nilai_query = "INSERT INTO nilai (siswa_id, mapel_id, nilai, tahun_ajaran) VALUES ('$siswa_id', '$mapel_id', '$nilai', '$tahun_ajaran')";
                                $insert_result = mysqli_query($conn, $insert_nilai_query);
                                $debug_nilai['status'] = $insert_result ? 'INSERT BERHASIL' : 'GAGAL INSERT';
                                if (!$insert_result) {
                                    $debug_nilai['error'] = mysqli_error($conn);
                                    $errors[] = "Gagal insert nilai $mapel_nama untuk $nama_lengkap: " . mysqli_error($conn);
    }
}
                        } else {
                            if ($mapel_id === null) {
                                $debug_nilai['status'] = 'SKIP - Mapel tidak ditemukan';
                            } elseif ($nilai === null) {
                                $debug_nilai['status'] = 'SKIP - Nilai kosong/tidak numerik';
                            }
                        }
                        $debug_nilai_log[] = $debug_nilai;
                        $row_debug['nilai_mapel'][] = $debug_nilai;
                    }
                    // Insert MINAT ke tabel minat_siswa jika ada
                    if ($minat_str) {
                        $insert_minat = "INSERT INTO minat_siswa (siswa_id, minat) VALUES ('$siswa_id', '$minat_str')";
                        mysqli_query($conn, $insert_minat);
                    }
                    // Insert TES IQ ke tabel test_iq jika ada
                    if ($tes_iq !== null) {
                        $tanggal_iq = date('Y-m-d');
                        // Tentukan kategori IQ
                        if ($tes_iq >= 110) {
                            $kategori = 'Tinggi';
                        } elseif ($tes_iq >= 90) {
                            $kategori = 'Sedang';
                        } else {
                            $kategori = 'Rendah';
                        }
                        $keterangan = 'Import Excel';
                        $insert_iq = "INSERT INTO test_iq (siswa_id, skor, kategori, tanggal_test, keterangan) VALUES ('$siswa_id', '$tes_iq', '$kategori', '$tanggal_iq', '$keterangan')";
                        mysqli_query($conn, $insert_iq);
                    }
                // Log aktivitas impor siswa
                $user_id = $_SESSION['user_id'];
                $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                            VALUES ('$user_id', 'Import data siswa', 'Nama: $nama_lengkap', '".$_SERVER['REMOTE_ADDR']."')";
                mysqli_query($conn, $log_query);
            } else {
                $errors[] = "Baris #" . ($i + 1) . ": Gagal menambahkan data siswa: " . mysqli_error($conn);
                $error_count++;
                    $row_debug['hasil'] = 'GAGAL';
                    $row_debug['errors'][] = 'Gagal insert siswa: ' . mysqli_error($conn);
                }
                $debug_import_info[] = $row_debug;
            }
            
            // Setelah proses padding dan sebelum loop insert siswa, tambahkan satu baris dummy
            $dummy_row = array_fill(0, isset($rows[0]) ? count($rows[0]) : 0, '');
            $rows[] = $dummy_row;
        
        // Hapus file temporary
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
            // PERBAIKAN: Pesan yang lebih informatif
            $nilai_errors = array_filter($errors, function($error) {
                return strpos($error, 'nilai') !== false;
            });
            
            if (count($nilai_errors) > 0) {
                $message = "Import selesai dengan peringatan. Berhasil: $success_count siswa, Gagal: $error_count siswa. Ada " . count($nilai_errors) . " error nilai mata pelajaran.";
                $messageType = "warning";
            } else {
                $message = "Import selesai. Berhasil: $success_count siswa, Gagal: $error_count siswa.";
        $messageType = ($error_count > 0) ? "warning" : "success";
            }
            
            // Tambahkan info debug mapel ke pesan jika ada error nilai
            if (count($nilai_errors) > 0 && !empty($debug_mapel_log)) {
                $message .= " Info mapel: " . implode('; ', array_slice($debug_mapel_log, 0, 3));
            }
            
            // Jika semua berhasil dan tidak ada error nilai, redirect ke halaman index
            if ($error_count == 0 && count($nilai_errors) == 0) {
            $_SESSION['success'] = $message;
            header("Location: index.php");
            exit();
            }

            // --- Selalu simpan log debug ke file setelah proses import ---
            $debug_data = [
                'ringkasan' => [
                    'total_data' => isset($rows) ? count($rows) : 0,
                    'berhasil' => $success_count ?? 0,
                    'gagal' => $error_count ?? 0
                ],
                'status_mapel' => [],
                'mapping_kolom' => [],
                'errors' => $errors,
                'detail_per_baris' => $debug_import_info ?? []
            ];
            if (isset($mapel_ids)) {
                foreach ($mapel_ids as $mapel_nama => $mapel_id) {
                    $col_var = $mapel_nama . '_col';
                    $col_value = isset($$col_var) ? $$col_var : null;
                    $debug_data['status_mapel'][$mapel_nama] = [
                        'id' => $mapel_id,
                        'status' => $mapel_id ? 'Ditemukan' : 'Tidak ditemukan',
                        'kolom' => $col_value !== null ? ($col_value + 1) : 'Tidak dipilih'
                    ];
                    if ($col_value !== null) {
                        $header = isset($preview_data[0][$col_value]) ? $preview_data[0][$col_value] : 'Unknown';
                        $debug_data['mapping_kolom'][$mapel_nama] = "Kolom " . ($col_value + 1) . " ($header)";
                    }
                }
            }
            $msg = "[IMPORT DEBUG]\n";
            $msg .= "Total Data: " . $debug_data['ringkasan']['total_data'] . ", Berhasil: " . $debug_data['ringkasan']['berhasil'] . ", Gagal: " . $debug_data['ringkasan']['gagal'] . "\n";
            $msg .= "Status Mapel:\n";
            foreach ($debug_data['status_mapel'] as $mapel => $info) {
                $msg .= ucfirst($mapel) . ': ' . $info['status'] . ' (ID: ' . ($info['id'] ?? 'null') . ', Kolom: ' . $info['kolom'] . ")\n";
            }
            if (!empty($debug_data['mapping_kolom'])) {
                $msg .= "\nMapping Kolom:\n";
                foreach ($debug_data['mapping_kolom'] as $mapel => $mapping) {
                    $msg .= ucfirst($mapel) . ': ' . $mapping . "\n";
                }
            }
            if (!empty($debug_mapel_log)) {
                $msg .= "\nLog Mapel:\n" . implode("\n", $debug_mapel_log) . "\n";
            }
            if (!empty($debug_data['errors'])) {
                $msg .= "\nErrors:\n" . implode("\n", $debug_data['errors']) . "\n";
            }
            if (!empty($debug_nilai_log)) {
                $msg .= "\nLog Nilai Mapel:\n";
                foreach ($debug_nilai_log as $log) {
                    $msg .= "Siswa: {$log['siswa']}, Mapel: {$log['mapel']}, Nilai: {$log['nilai']}, Status: {$log['status']}, Error: {$log['error']}\n";
                }
            }
            if (!empty($debug_ekonomi_all)) {
                $msg .= "\n[DEBUG EKONOMI]" . "\n" . implode("\n", $debug_ekonomi_all) . "\n";
            }
            $log_dir = 'temp/';
            if (!file_exists($log_dir)) {
                mkdir($log_dir, 0777, true);
            }
            $log_file = $log_dir . 'debug_import_log.txt';
            file_put_contents($log_file, $msg, FILE_APPEND);
            // --- END log debug ---
        }
    }
}