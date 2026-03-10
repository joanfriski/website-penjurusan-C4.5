# Ringkasan Tabel untuk Implementasi Algoritma C4.5

Berdasarkan struktur database yang Anda miliki dan yang direkomendasikan, berikut adalah penjelasan masing-masing tabel tanpa mengulangi struktur tabelnya:

## Tabel yang Sudah Ada:

1. **`users`** - Menyimpan data pengguna sistem seperti admin, staff BK, dan kepala sekolah dengan informasi login dan peran mereka.

2. **`kelas`** - Menyimpan informasi kelas dengan nama kelas, tahun ajaran, dan wali kelas.

3. **`siswa`** - Menyimpan data lengkap siswa termasuk NIS, NISN, informasi personal, dan relasi ke kelas.

4. **`mata_pelajaran`** - Menyimpan daftar mata pelajaran dengan kode, nama, dan kategori (IPA/IPS).

5. **`nilai`** - Menyimpan nilai mata pelajaran untuk setiap siswa per tahun ajaran.

6. **`test_iq`** - Menyimpan hasil tes IQ siswa termasuk skor, kategori, dan tanggal tes.

7. **`minat_siswa`** - Menyimpan minat jurusan (IPA/IPS) dari siswa.

8. **`log_aktivitas`** - Menyimpan log aktivitas pengguna dalam sistem.

9. **`prediksi_jurusan`** - Menyimpan data gabungan dan hasil prediksi jurusan untuk setiap siswa.

## Tabel yang Sudah Ditambahkan untuk Algoritma C4.5:

10. **`decision_tree_nodes`** - Menyimpan struktur pohon keputusan yang dihasilkan dari algoritma C4.5, termasuk node, relasi parent-child, nilai atribut, entropy, dan gain.

11. **`decision_rules`** - Menyimpan aturan keputusan yang diekstrak dari pohon keputusan untuk mempermudah proses prediksi.

## Tabel yang Direkomendasikan untuk Ditambahkan:

12. **`model_evaluation`** - Untuk menyimpan metrik evaluasi model seperti accuracy, precision, recall, dan F1-score untuk mengukur performa model.

13. **`training_data`** - Untuk menyimpan dataset pelatihan yang digunakan dalam membangun model, memungkinkan validasi silang dan reproduksi hasil.

14. **`model_metadata`** - Untuk menyimpan informasi tentang model seperti versi, deskripsi, parameter yang digunakan, dan siapa yang membuatnya.

## Manfaat Struktur Database Ini:

1. **Komprehensif**: Mencakup seluruh alur kerja dari data siswa hingga prediksi dan evaluasi.

2. **Versioning**: Mendukung pembuatan dan penyimpanan beberapa versi model untuk periode atau kebutuhan berbeda.

3. **Traceabilitas**: Memungkinkan pelacakan dari prediksi hingga aturan dan model yang digunakan.

4. **Evaluasi**: Menyediakan struktur untuk mengukur dan membandingkan performa model.

5. **Pemeliharaan**: Memudahkan pembaruan model tanpa mengganggu data historis.

Dengan total 14 tabel ini, Anda memiliki struktur database yang lengkap untuk implementasi sistem prediksi jurusan menggunakan algoritma C4.5, termasuk penyimpanan data, pemrosesan, dan evaluasi model.