<?php
// Pemrosesan update untuk pengaturan sekolah dihandle di update_school.php
include_once 'update_school.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-school me-2"></i>Pengaturan Profil Sekolah</h5>
        <?php if(isset($school_data) && $school_data): ?>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSchoolModal">
                <i class="fas fa-edit me-1"></i>Edit Profil
            </button>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['school_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['school_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['school_success']);
        endif; 
        ?>
        
        <?php if(isset($_SESSION['school_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['school_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['school_error']);
        endif; 
        ?>
        
        <?php if(isset($school_data) && $school_data): ?>
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="text-center mb-4">
                    <?php if(isset($school_data['logo']) && $school_data['logo']): ?>
                        <img src="logo/<?php echo htmlspecialchars($school_data['logo'] ?? 'default_logo.png'); ?>" alt="Logo Sekolah" class="img-fluid" style="max-height: 150px;">

                    <?php else: ?>
                    <div class="bg-light rounded p-3 d-inline-block">
                        <i class="fas fa-school fa-4x text-primary"></i>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-item">
                    <div class="info-label">Nama Sekolah</div>
                    <div class="info-value"><?php echo $school_data['nama_sekolah']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Alamat</div>
                    <div class="info-value"><?php echo $school_data['alamat']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Telepon</div>
                    <div class="info-value"><?php echo $school_data['telepon']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo $school_data['email']; ?></div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-item">
                    <div class="info-label">Website</div>
                    <div class="info-value">
                        <a href="<?php echo $school_data['website']; ?>" target="_blank"><?php echo $school_data['website']; ?></a>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Kepala Sekolah</div>
                    <div class="info-value"><?php echo $school_data['kepala_sekolah']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">NIP Kepala Sekolah</div>
                    <div class="info-value"><?php echo $school_data['nip_kepala_sekolah']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tahun Ajaran</div>
                    <div class="info-value"><?php echo $school_data['tahun_ajaran']; ?></div>
                </div>
            </div>
            
            <div class="col-12 mt-3">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Terakhir diperbarui: <?php echo date('d F Y H:i', strtotime($school_data['updated_at'])); ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="fas fa-school fa-4x text-secondary"></i>
            </div>
            <h5>Belum ada data profil sekolah</h5>
            <p class="text-muted">Silakan tambahkan data profil sekolah</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSchoolModal">
                <i class="fas fa-plus-circle me-2"></i>Tambah Profil Sekolah
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Profil Sekolah -->
<div class="modal fade" id="addSchoolModal" tabindex="-1" aria-labelledby="addSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSchoolModalLabel">Tambah Profil Sekolah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_sekolah" class="form-label">Nama Sekolah</label>
                                <input type="text" class="form-control" id="nama_sekolah" name="nama_sekolah" required>
                            </div>
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="telepon" class="form-label">Telepon</label>
                                <input type="text" class="form-control" id="telepon" name="telepon" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website" required>
                            </div>
                            <div class="mb-3">
                                <label for="kepala_sekolah" class="form-label">Nama Kepala Sekolah</label>
                                <input type="text" class="form-control" id="kepala_sekolah" name="kepala_sekolah" required>
                            </div>
                            <div class="mb-3">
                                <label for="nip_kepala_sekolah" class="form-label">NIP Kepala Sekolah</label>
                                <input type="text" class="form-control" id="nip_kepala_sekolah" name="nip_kepala_sekolah" required>
                            </div>
                            <div class="mb-3">
                                <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                                <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" placeholder="contoh: 2023/2024" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="logo" class="form-label">Logo Sekolah</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <div class="form-text">Upload logo dalam format JPG, PNG, atau GIF (opsional)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_school" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Profil Sekolah -->
<div class="modal fade" id="editSchoolModal" tabindex="-1" aria-labelledby="editSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSchoolModalLabel">Edit Profil Sekolah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_nama_sekolah" class="form-label">Nama Sekolah</label>
                                <input type="text" class="form-control" id="edit_nama_sekolah" name="nama_sekolah" value="<?php echo isset($school_data['nama_sekolah']) ? $school_data['nama_sekolah'] : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="edit_alamat" name="alamat" rows="3" required><?php echo isset($school_data['alamat']) ? $school_data['alamat'] : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_telepon" class="form-label">Telepon</label>
                                <input type="text" class="form-control" id="edit_telepon" name="telepon" value="<?php echo isset($school_data['telepon']) ? $school_data['telepon'] : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo isset($school_data['email']) ? $school_data['email'] : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="edit_website" name="website" value="<?php echo isset($school_data['website']) ? $school_data['website'] : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_kepala_sekolah" class="form-label">Nama Kepala Sekolah</label>
                                <input type="text" class="form-control" id="edit_kepala_sekolah" name="kepala_sekolah" value="<?php echo isset($school_data['kepala_sekolah']) ? $school_data['kepala_sekolah'] : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_nip_kepala_sekolah" class="form-label">NIP Kepala Sekolah</label>
                                <input type="text" class="form-control" id="edit_nip_kepala_sekolah" name="nip_kepala_sekolah" value="<?php echo isset($school_data['nip_kepala_sekolah']) ? $school_data['nip_kepala_sekolah'] : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_tahun_ajaran" class="form-label">Tahun Ajaran</label>
                                <input type="text" class="form-control" id="edit_tahun_ajaran" name="tahun_ajaran" value="<?php echo isset($school_data['tahun_ajaran']) ? $school_data['tahun_ajaran'] : ''; ?>" placeholder="contoh: 2023/2024" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="edit_logo" class="form-label">Logo Sekolah</label>
                                <?php if(isset($school_data['logo']) && $school_data['logo']): ?>
                                <div class="mb-2">
                                    <img src="logo/<?php echo $school_data['logo']; ?>" alt="Logo Sekolah" style="max-height: 100px;" class="border p-1">
                                </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="edit_logo" name="logo" accept="image/*">
                                <div class="form-text">Upload logo baru dalam format JPG, PNG, atau GIF (biarkan kosong jika tidak ingin mengubah logo)</div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo">
                                    <label class="form-check-label" for="remove_logo">
                                        Hapus logo saat ini
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_school" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>