# Analisis Struktur Database untuk Program Web Penjurusan Siswa

Tabel-tabel yang disediakan sudah cukup lengkap untuk membangun sistem web penjurusan siswa dengan algoritma C4.5. Database ini memiliki struktur yang baik dengan relasi antar tabel yang jelas.

## Urutan Pembuatan dan Pengisian Tabel

### 1. Tabel Pertama (Awal)

Tabel-tabel yang harus dibuat dan diisi pertama kali (tanpa dependensi):

1. **`users`** - Karena ini adalah sistem berbasis web yang membutuhkan autentikasi
   - Alasan: Tabel ini tidak memiliki ketergantungan pada tabel lain (tidak ada foreign key)
   - Diperlukan untuk menyimpan data admin, staff BK, dan kepala sekolah

2. **`kelas`** - Informasi dasar tentang kelas
   - Alasan: Tidak memiliki ketergantungan pada tabel lain
   - Diperlukan sebelum mengisi data siswa karena siswa akan terhubung ke kelas

3. **`mata_pelajaran`** - Daftar mata pelajaran
   - Alasan: Tidak memiliki ketergantungan dan diperlukan sebelum menginput nilai

4. **`settings`** - Pengaturan sistem
   - Alasan: Tidak memiliki ketergantungan dan berisi informasi dasar sekolah

### 2. Tabel Kedua (Tengah)

Tabel-tabel yang memiliki dependensi pada tabel awal:

5. **`siswa`** - Data siswa
   - Alasan: Tergantung pada tabel `kelas` (kelas_id)
   - Harus diisi setelah data kelas tersedia

6. **`model_metadata`** - Metadata model
   - Alasan: Tergantung pada tabel `users` (created_by)
   - Perlu dibuat sebelum mengisi data training atau evaluasi model

### 3. Tabel Ketiga (Akhir)

Tabel-tabel yang memiliki dependensi pada tabel tengah:

7. **`nilai`** - Nilai mata pelajaran siswa
   - Alasan: Tergantung pada tabel `siswa` dan `mata_pelajaran`
   - Harus diisi setelah data siswa dan mata pelajaran tersedia

8. **`test_iq`** - Hasil test IQ siswa
   - Alasan: Tergantung pada tabel `siswa`
   - Harus diisi setelah data siswa tersedia

9. **`minat_siswa`** - Minat jurusan siswa
   - Alasan: Tergantung pada tabel `siswa`
   - Harus diisi setelah data siswa tersedia

10. **`training_data`** - Data training
    - Alasan: Merupakan data untuk pelatihan model
    - Dapat diisi kapan saja setelah metadata model tersedia

11. **`log_aktivitas`** - Log aktivitas sistem
    - Alasan: Tergantung pada tabel `users`
    - Terisi secara otomatis saat sistem digunakan

### 4. Tabel Terakhir

Tabel-tabel yang berisi hasil dari proses utama:

12. **`prediksi_jurusan`** - Hasil prediksi jurusan
    - Alasan: Tergantung pada data siswa, nilai, test IQ, dan minat
    - Ini adalah output dari algoritma C4.5 yang diterapkan

13. **`decision_tree_nodes`** dan **`decision_rules`** - Struktur pohon keputusan dan aturan
    - Alasan: Dibuat berdasarkan hasil dari algoritma C4.5
    - Merupakan representasi dari model yang dibangun

14. **`model_evaluation`** - Evaluasi dari model yang dibangun
    - Alasan: Tergantung pada semua data dan hasil prediksi
    - Merupakan tahap akhir untuk mengevaluasi keakuratan model

## Hubungan dan Ketergantungan Antar Tabel

1. **Siswa dengan Kelas**: Anda harus mengisi data kelas terlebih dahulu sebelum mengisi data siswa karena tabel `siswa` memiliki foreign key `kelas_id` yang mengacu pada tabel `kelas`.

2. **Nilai dengan Siswa dan Mata Pelajaran**: Anda harus mengisi data siswa dan mata pelajaran terlebih dahulu sebelum mengisi nilai karena tabel `nilai` memiliki foreign key yang mengacu pada kedua tabel tersebut.

3. **Test IQ dan Minat dengan Siswa**: Anda harus mengisi data siswa terlebih dahulu sebelum mengisi hasil test IQ dan minat siswa.

4. **Prediksi Jurusan dengan Siswa**: Anda harus mengisi data siswa, nilai, test IQ, dan minat terlebih dahulu sebelum dapat menghasilkan prediksi jurusan yang valid.

5. **Model dan Pohon Keputusan**: Data training perlu diisi sebelum dapat membangun dan mengevaluasi model pohon keputusan.

## Kesimpulan

Struktur database ini sudah cukup baik untuk program web penjurusan siswa. Database ini mendukung:
- Manajemen pengguna dan hak akses
- Pengelolaan data siswa dan kelas
- Pencatatan nilai dan hasil test
- Pembuatan model prediksi dengan algoritma C4.5
- Evaluasi dan penyimpanan hasil prediksi

Penting untuk mengikuti urutan pembuatan dan pengisian tabel yang telah dijelaskan untuk menghindari masalah integritas referensial (foreign key constraints).