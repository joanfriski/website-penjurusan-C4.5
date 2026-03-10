# Alur Proses dan Pengisian Tabel untuk Sistem Prediksi Jurusan

Berikut adalah alur proses dan langkah-langkah pengisian tabel untuk mengimplementasikan sistem prediksi jurusan dengan algoritma C4.5:

## 1. Pengaturan Awal dan Manajemen Pengguna

1. **Tabel `users`**:
   - Admin sistem melakukan setup awal dengan mendaftarkan akun admin
   - Staff BK dan kepala sekolah harus melakukan registrasi akun dengan memasukkan username, password, dan data diri
   - Setiap pengguna memiliki peran yang berbeda dengan hak akses sesuai peran

## 2. Pengisian Data Master

2. **Tabel `kelas`**:
   - Staff BK menambahkan data kelas dengan mengisi nama kelas, tahun ajaran, dan wali kelas
   - Data kelas ini menjadi referensi untuk pengelompokan siswa

3. **Tabel `mata_pelajaran`**:
   - Staff BK menambahkan daftar mata pelajaran dengan kode, nama, dan kategori (IPA/IPS)
   - Mata pelajaran dikategorikan untuk memudahkan perhitungan rata-rata nilai per kategori

## 3. Pengisian Data Siswa

4. **Tabel `siswa`**:
   - Staff BK mendaftarkan data siswa dengan mengisi NIS, NISN, nama lengkap, dan informasi lainnya
   - Siswa dihubungkan dengan kelas yang sudah didaftarkan sebelumnya

5. **Tabel `nilai`**:
   - Staff BK memasukkan nilai mata pelajaran untuk setiap siswa
   - Nilai diinput berdasarkan mata pelajaran dan tahun ajaran 
   - Sistem akan menghitung rata-rata nilai per kategori (IPA/IPS) secara otomatis

6. **Tabel `test_iq`**:
   - Staff BK mencatat hasil tes IQ siswa dengan memasukkan skor dan tanggal tes
   - Sistem secara otomatis mengkategorikan skor IQ menjadi Tinggi, Sedang, atau Rendah berdasarkan ketentuan

7. **Tabel `minat_siswa`**:
   - Staff BK mencatat minat jurusan yang disampaikan oleh siswa (IPA/IPS)
   - Data minat ini menjadi salah satu faktor pendukung dalam prediksi

## 4. Pembuatan dan Pelatihan Model

8. **Tabel `training_data`**:
   - Staff BK mengumpulkan data historis siswa dengan hasil jurusan yang sudah diketahui
   - Data ini dimasukkan sebagai data pelatihan (training data) untuk algoritma C4.5
   - Minimal diperlukan data dari beberapa tahun sebelumnya untuk hasil yang baik

9. **Tabel `model_metadata`**:
   - Staff BK membuat versi model baru dengan deskripsi dan parameter yang digunakan
   - System mencatat siapa dan kapan model dibuat

10. **Tabel `decision_tree_nodes`**:
    - Sistem menjalankan algoritma C4.5 untuk membuat pohon keputusan
    - Data node, atribut, nilai Entropy, dan Gain disimpan dalam tabel ini
    - Setiap node dihubungkan dengan parent-nya untuk membentuk struktur pohon

11. **Tabel `decision_rules`**:
    - Sistem secara otomatis mengekstrak aturan keputusan dari pohon yang terbentuk
    - Setiap aturan disimpan dengan kondisi dan hasil prediksinya

12. **Tabel `model_evaluation`**:
    - Sistem mengevaluasi model dengan data validasi
    - Metrik performa seperti akurasi, presisi, dan recall disimpan untuk referensi

## 5. Proses Prediksi

13. **Tabel `prediksi_jurusan`**:
    - Staff BK memilih siswa yang akan diprediksi jurusannya
    - Sistem secara otomatis mengambil data nilai, IQ, dan minat siswa
    - Algoritma menerapkan aturan dari model terbaru untuk memprediksi jurusan
    - Hasil prediksi disimpan bersama dengan atribut yang digunakan untuk prediksi

## 6. Monitoring dan Evaluasi

14. **Tabel `log_aktivitas`**:
    - Sistem mencatat setiap aktivitas pengguna seperti login, pembuatan model, atau prediksi
    - Berguna untuk audit dan pemantauan penggunaan sistem

## Alur Proses End-to-End:

1. **Setup Awal**: Admin → Registrasi pengguna → Input kelas dan mata pelajaran
2. **Pengumpulan Data**: Input siswa → Input nilai → Input hasil tes IQ → Input minat
3. **Pembuatan Model**: Pengumpulan data training → Pembuatan model C4.5 → Evaluasi model
4. **Prediksi**: Pilih siswa → Proses prediksi → Lihat hasil
5. **Pelaporan**: Generate laporan untuk kepala sekolah dan wali kelas

Dengan mengikuti alur ini, sistem dapat menggunakan algoritma C4.5 untuk memprediksi jurusan yang sesuai bagi siswa berdasarkan berbagai faktor yang telah dikumpulkan dalam database.