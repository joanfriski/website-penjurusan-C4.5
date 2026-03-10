<?php
ob_start();
require_once '../include/header.php';

require_once 'import_siswa_logic.php'; // Include file logika

// Ambil header dari file Excel jika ada preview
$header_row = [];
if ($has_preview && isset($preview_data[0])) {
    if (isset($_POST['has_header']) && $_POST['has_header']) {
        // Jika file punya header, ambil header dari baris pertama Excel
        $header_row = $preview_data[0];
    } else {
        // Jika tidak ada header, gunakan data baris pertama
        $header_row = array_map(function($i) { return 'Kolom ' . ($i + 1); }, array_keys($preview_data[0]));
    }
}

// Siapkan label kolom untuk mapping
$column_labels = [];
if ($has_preview && isset($preview_data[0])) {
    $row_example = isset($preview_data[1]) ? $preview_data[1] : $preview_data[0];
    foreach ($header_row as $col_index => $col_value) {
        $example = isset($row_example[$col_index]) ? $row_example[$col_index] : '';
        if ($col_value && $col_value !== (string)($col_index + 1)) {
            $column_labels[$col_index] = htmlspecialchars($col_value) . ' (contoh: ' . htmlspecialchars($example) . ') [Kolom ' . ($col_index + 1) . ']';
        } else {
            $column_labels[$col_index] = 'Kolom ' . ($col_index + 1) . ' (contoh: ' . htmlspecialchars($example) . ')';
        }
    }
}
?>

<!-- CSS untuk perbaikan tampilan -->
<style>
    /* Responsif content-wrapper */
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
        
        .filter-row {
            flex-direction: column;
        }
        
        .filter-row > div {
            margin-bottom: 10px;
        }
    }
    
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-file-import me-2 text-primary"></i>Import Data Siswa dari Excel
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="tambah.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <?php if($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : ($messageType == 'warning' ? 'exclamation-circle' : 'exclamation-triangle'); ?> me-2"></i>
                <?php echo $message; ?>
                <?php if(isset($errors) && !empty($errors)): ?>
                <ul class="mt-2 mb-0">
                    <?php foreach(array_slice($errors, 0, 5) as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                    <?php if(count($errors) > 5): ?>
                    <li>...dan <?php echo count($errors) - 5; ?> kesalahan lainnya.</li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-upload me-2 text-primary"></i>Upload File Excel
                    </h5>
                    <small class="text-muted">Upload file Excel yang berisi data siswa (format: .xls, .xlsx, atau .csv)</small>
                </div>
                <div class="card-body">
                    <?php if(!$has_preview): ?>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="excel_file" class="form-label required-field">File Excel</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" required accept=".xls,.xlsx,.csv">
                                <div class="form-text">File harus berformat Excel (.xls, .xlsx) atau CSV.</div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="has_header" name="has_header" checked>
                                    <label class="form-check-label" for="has_header">
                                        File Excel memiliki baris header
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Catatan:</strong> Sistem akan mengimpor data siswa dengan kolom wajib: Nama, Kelas, dan Jenis Kelamin. 
                            NIS dan NISN akan digenerate otomatis dan perlu diperbarui nanti.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" name="preview" class="btn btn-primary">
                                <i class="fas fa-file-import me-1"></i> Import Data
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <!-- Preview dan Mapping Kolom -->
                    <form action="" method="POST">
                        <input type="hidden" name="file_path" value="<?php echo $file_path; ?>">
                        <input type="hidden" name="has_header" value="<?php echo isset($_POST['has_header']) ? '1' : '0'; ?>">
                        
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-columns me-2"></i>Pemetaan Kolom
                        </h6>
                        
                        <!-- Mapping Nama Siswa -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nama_col" class="form-label required-field">Kolom Nama Siswa</label>
                                    <select class="form-select" id="nama_col" name="nama_col" required>
                                        <option value="" selected disabled>-- Pilih Kolom --</option>
                                        <?php foreach($column_labels as $col_index => $label): ?>
                                        <option value="<?php echo $col_index; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Mapping Mata Pelajaran -->
                        <h6 class="border-bottom pb-2 mb-3 mt-4">
                            <i class="fas fa-book me-2"></i>Pemetaan Kolom Mata Pelajaran
                        </h6>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="geografi_col" class="form-label">Kolom Nilai Geografi</label>
                                    <select class="form-select" id="geografi_col" name="geografi_col">
                                        <option value="" selected disabled>-- Pilih Kolom --</option>
                                        <?php foreach($column_labels as $col_index => $label): ?>
                                        <option value="<?php echo $col_index; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="fisika_col" class="form-label">Kolom Nilai Fisika</label>
                                    <select class="form-select" id="fisika_col" name="fisika_col">
                                        <option value="" selected disabled>-- Pilih Kolom --</option>
                                        <?php foreach($column_labels as $col_index => $label): ?>
                                        <option value="<?php echo $col_index; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="kimia_col" class="form-label">Kolom Nilai Kimia</label>
                                    <select class="form-select" id="kimia_col" name="kimia_col">
                                        <option value="" selected disabled>-- Pilih Kolom --</option>
                                        <?php foreach($column_labels as $col_index => $label): ?>
                                        <option value="<?php echo $col_index; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="biologi_col" class="form-label">Kolom Nilai Biologi</label>
                                    <select class="form-select" id="biologi_col" name="biologi_col">
                                        <option value="" selected disabled>-- Pilih Kolom --</option>
                                        <?php foreach($column_labels as $col_index => $label): ?>
                                        <option value="<?php echo $col_index; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sosiologi_col" class="form-label">Kolom Nilai Sosiologi</label>
                                    <select class="form-select" id="sosiologi_col" name="sosiologi_col">
                                        <option value="" selected disabled>-- Pilih Kolom --</option>
                                        <?php foreach($column_labels as $col_index => $label): ?>
                                        <option value="<?php echo $col_index; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="ekonomi_col" class="form-label">Kolom Nilai Ekonomi</label>
                                    <select class="form-select" id="ekonomi_col" name="ekonomi_col">
                                        <option value="" selected disabled>-- Pilih Kolom --</option>
                                        <?php foreach($column_labels as $col_index => $label): ?>
                                        <option value="<?php echo $col_index; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Data -->
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-table me-2"></i>Preview Data (<?php echo count($preview_data); ?> baris)
                        </h6>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <?php foreach($header_row as $col_index => $col_value): ?>
                                        <th><?php echo htmlspecialchars($col_value); ?> (Kolom <?php echo $col_index + 1; ?>)</th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($preview_data as $row_index => $row): ?>
                                    <tr>
                                        <td><?php echo $row_index + 1; ?></td>
                                        <?php foreach($row as $cell): ?>
                                        <td><?php echo $cell; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="import_siswa.php" class="btn btn-secondary me-2">
                                <i class="fas fa-undo me-1"></i> Batal
                            </a>
                            <button type="submit" name="import" class="btn btn-success">
                                <i class="fas fa-file-import me-1"></i> Import Data
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Include file JavaScript -->
<?php require_once 'import_siswa_script.php'; ?>
<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

<!-- Bootstrap CSS (fallback) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- jQuery (harus sebelum Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>