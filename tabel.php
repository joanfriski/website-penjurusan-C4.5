-- Tabel untuk menyimpan data pengguna (admin, staff BK, kepala sekolah)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('staff_bk', 'kepsek') NOT NULL,
    email VARCHAR(100),
    no_telp VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan data kelas
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(20) NOT NULL,
    tahun_ajaran VARCHAR(10) NOT NULL,
    wali_kelas VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY(nama_kelas, tahun_ajaran)
);

-- Tabel untuk menyimpan data siswa
CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(20) NOT NULL UNIQUE,
    nisn VARCHAR(20) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    alamat TEXT,
    no_telp VARCHAR(15),
    kelas_id INT,
    status ENUM('aktif', 'tidak_aktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
);

-- Tabel untuk menyimpan data mata pelajaran
CREATE TABLE mata_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    kategori ENUM('IPA', 'IPS') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan nilai mata pelajaran siswa
CREATE TABLE nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    mapel_id INT NOT NULL,
    nilai DECIMAL(5,2) NOT NULL,
    tahun_ajaran VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
    UNIQUE KEY(siswa_id, mapel_id, tahun_ajaran)
);

-- Tabel untuk menyimpan hasil test IQ siswa
CREATE TABLE test_iq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    skor INT NOT NULL,
    kategori ENUM('Tinggi', 'Sedang', 'Rendah') NOT NULL,
    tanggal_test DATE NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);

-- Tabel untuk menyimpan minat siswa
CREATE TABLE `minat_siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `minat` enum('IPA','IPS') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `siswa_id` (`siswa_id`),
  CONSTRAINT `minat_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- Tabel untuk menyimpan log aktivitas sistem
CREATE TABLE log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    aktivitas VARCHAR(255) NOT NULL,
    keterangan TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE prediksi_jurusan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    nilai_mapel_ipa DECIMAL(5,2),
    nilai_mapel_ips DECIMAL(5,2),
    kategori_nilai ENUM('Tinggi', 'Sedang', 'Rendah'),
    nilai_iq INT,
    kategori_iq ENUM('Tinggi', 'Sedang', 'Rendah'),
    minat ENUM('IPA', 'IPS'),
    hasil_prediksi ENUM('IPA', 'IPS') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `telepon` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `website` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `kepala_sekolah` varchar(100) NOT NULL,
  `nip_kepala_sekolah` varchar(20) NOT NULL,
  `tahun_ajaran` varchar(9) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `decision_tree_nodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `attribute_name` varchar(50) DEFAULT NULL,
  `attribute_value` varchar(50) DEFAULT NULL,
  `is_leaf` tinyint(1) DEFAULT 0,
  `class_value` enum('IPA','IPS') DEFAULT NULL,
  `entropy` decimal(10,6) DEFAULT NULL,
  `gain` decimal(10,6) DEFAULT NULL,
  PRIMARY KEY (`id`)
)

CREATE TABLE `decision_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_condition` text DEFAULT NULL,
  `class_result` enum('IPA','IPS') DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
)

ALTER TABLE `decision_tree_nodes` 
ADD COLUMN `model_version` VARCHAR(50) NOT NULL DEFAULT 'default',
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `decision_rules` 
ADD COLUMN `model_version` VARCHAR(50) NOT NULL DEFAULT 'default',
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE `model_evaluation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_version` VARCHAR(50) NOT NULL,
  `accuracy` DECIMAL(5,2),
  `precision` DECIMAL(5,2),
  `recall` DECIMAL(5,2),
  `f1_score` DECIMAL(5,2),
  `confusion_matrix` TEXT,
  `dataset_size` INT,
  `evaluation_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `training_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_nilai` ENUM('Tinggi', 'Sedang', 'Rendah') NOT NULL,
  `kategori_iq` ENUM('Tinggi', 'Sedang', 'Rendah') NOT NULL,
  `minat` ENUM('IPA', 'IPS'),
  `hasil_aktual` ENUM('IPA', 'IPS') NOT NULL,
  `model_version` VARCHAR(50) NOT NULL DEFAULT 'default',
  `is_training` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

ALTER TABLE `decision_tree_nodes` 
ADD INDEX `idx_parent_id` (`parent_id`),
ADD INDEX `idx_model_version` (`model_version`);

ALTER TABLE `decision_rules` 
ADD INDEX `idx_model_version` (`model_version`);

CREATE TABLE `model_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_version` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `parameters` TEXT,
  `training_data_size` INT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`model_version`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);