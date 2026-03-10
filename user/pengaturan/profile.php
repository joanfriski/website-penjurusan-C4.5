<?php
require_once '../include/header.php';
require_once '../../database/config.php';

// Cek session user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Inisialisasi variabel pesan
$success_message = $error_message = $success_password = $error_password = '';

// Include file proses
include 'update_profile.php';
include 'change_password.php';

// Jika user adalah kepala sekolah, ambil data sekolah
$school_data = null;
if ($user['role'] == 'kepsek') {
    $query_school = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
    $result_school = mysqli_query($conn, $query_school);
    $school_data = mysqli_fetch_assoc($result_school);
}
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        padding: 2rem;
        border-radius: 0.5rem;
        color: white;
        margin-bottom: 2rem;
    }
    
    .profile-info {
        display: flex;
        align-items: center;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: bold;
        margin-right: 1.5rem;
    }
    
    .profile-meta h3 {
        margin-bottom: 0.5rem;
    }
    
    .profile-meta p {
        margin-bottom: 0.25rem;
        opacity: 0.8;
    }
    
    .nav-pills .nav-link.active {
        background-color: #0d6efd;
    }
    
    .info-item {
        margin-bottom: 1.5rem;
    }
    
    .info-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        font-weight: 500;
    }
    
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
        
        .profile-info {
            flex-direction: column;
            text-align: center;
        }
        
        .profile-avatar {
            margin-right: 0;
            margin-bottom: 1rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-3 py-3 content-wrapper" id="content">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h2 class="h3 mb-0">
                        <i class="fas fa-user-cog me-2"></i>Pengaturan Profil
                    </h2>
                    <p class="text-muted small mt-1">Kelola informasi profil dan keamanan akun Anda</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <?php echo date('d F Y'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Profil Header -->
            <div class="profile-header shadow-sm">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                    </div>
                    <div class="profile-meta">
                        <h3><?php echo $user['nama_lengkap']; ?></h3>
                        <p><i class="fas fa-id-badge me-2"></i><?php echo ($user['role'] == 'kepsek') ? 'Kepala Sekolah' : 'Kepala Sekolah'; ?></p>
                        <p><i class="fas fa-user me-2"></i>@<?php echo $user['username']; ?></p>
                        <?php if($user['email']): ?>
                        <p><i class="fas fa-envelope me-2"></i><?php echo $user['email']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
          <!-- Notifikasi General -->
          <?php if(!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active text-start py-3 px-4 border-bottom" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                                    <i class="fas fa-user me-2"></i>Informasi Profil
                                </button>
                                <button class="nav-link text-start py-3 px-4 border-bottom" id="v-pills-password-tab" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab">
                                    <i class="fas fa-lock me-2"></i>Ubah Password
                                </button>
                                <?php if($user['role'] == 'kepsek'): ?>
                                <button class="nav-link text-start py-3 px-4 border-bottom" id="v-pills-school-tab" data-bs-toggle="pill" data-bs-target="#v-pills-school" type="button" role="tab">
                                    <i class="fas fa-school me-2"></i>Profil Sekolah
                                </button>
                                <?php endif; ?>
                                <button class="nav-link text-start py-3 px-4" id="v-pills-activity-tab" data-bs-toggle="pill" data-bs-target="#v-pills-activity" type="button" role="tab">
                                    <i class="fas fa-history me-2"></i>Riwayat Aktivitas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="tab-content" id="v-pills-tabContent">
                        <!-- Tab Informasi Profil -->
                        <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Informasi Profil</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username" value="<?php echo $user['username']; ?>" readonly disabled>
                                                <div class="form-text text-muted">Username tidak dapat diubah</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="role" class="form-label">Peran</label>
                                                <input type="text" class="form-control" id="role" value="<?php echo ($user['role'] == 'kepsek') ? 'Kepala Sekolah' : 'Kepala Sekolah'; ?>" readonly disabled>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo $user['nama_lengkap']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="no_telp" class="form-label">Nomor Telepon</label>
                                            <input type="text" class="form-control" id="no_telp" name="no_telp" value="<?php echo $user['no_telp']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="created_at" class="form-label">Tanggal Bergabung</label>
                                            <input type="text" class="form-control" id="created_at" value="<?php echo date('d F Y H:i', strtotime($user['created_at'])); ?>" readonly disabled>
                                        </div>
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Ubah Password -->
                        <div class="tab-pane fade" id="v-pills-password" role="tabpanel">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-key me-2"></i>Ubah Password</h5>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($success_password)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success_password; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if(isset($error_password)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_password; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Password Saat Ini</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Password Baru</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="form-text">Password minimal 8 karakter dan kombinasi huruf dan angka</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Setelah mengubah password, Anda perlu login kembali menggunakan password baru.
                                        </div>
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" name="change_password" class="btn btn-primary">
                                                <i class="fas fa-key me-2"></i>Ubah Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($user['role'] == 'kepsek'): ?>
                        <!-- Tab Profil Sekolah (Hanya untuk kepala sekolah) -->
                        <div class="tab-pane fade" id="v-pills-school" role="tabpanel">
                            <?php include 'school_settings.php'; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Tab Riwayat Aktivitas -->
                        <div class="tab-pane fade" id="v-pills-activity" role="tabpanel">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Aktivitas</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Ambil log aktivitas user
                                    $query_log = "SELECT * FROM log_aktivitas WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 20";
                                    $result_log = mysqli_query($conn, $query_log);
                                    
                                    if (mysqli_num_rows($result_log) > 0):
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Aktivitas</th>
                                                    <th>Waktu</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($log = mysqli_fetch_assoc($result_log)): ?>
                                                <tr>
                                                    <td><i class="fas fa-circle text-primary me-2" style="font-size: 8px;"></i><?php echo $log['aktivitas']; ?></td>
                                                    <td><?php echo date('d F Y H:i', strtotime($log['created_at'])); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Belum ada riwayat aktivitas yang tercatat.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Sidebar toggle handler
        $("#sidebarToggle").on("click", function() {
            $("#sidebar").toggleClass("collapsed");
            $("#content").toggleClass("expanded");
        });
        
        // Memeriksa ukuran layar saat halaman dimuat
        checkScreenSize();
        
        // Memeriksa ukuran layar saat window di-resize
        $(window).resize(function() {
            checkScreenSize();
        });
        
        // Fungsi untuk memeriksa ukuran layar
        function checkScreenSize() {
            if ($(window).width() < 768) {
                $("#sidebar").addClass("collapsed");
                $("#content").addClass("expanded");
            }
        }
    });
</script>

<?php require_once '../include/footer.php'; ?>