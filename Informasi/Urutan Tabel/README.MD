# Urutan Prioritas Pengisian Tabel untuk Sistem Prediksi Jurusan

Berikut adalah urutan prioritas tabel yang harus diisi dalam sistem prediksi jurusan, beserta alasannya:

## 1. **Tabel `users`**
**Alasan prioritas tertinggi:**
- Merupakan syarat awal untuk mengakses sistem
- Mendefinisikan hak akses pengguna (staff BK, kepala sekolah)
- Tanpa adanya pengguna, tidak ada yang bisa mengoperasikan sistem

## 2. **Tabel `kelas`**
**Alasan:**
- Menjadi referensi dasar untuk pengelompokan siswa
- Dibutuhkan sebelum registrasi siswa karena siswa harus ditempatkan di kelas tertentu
- Struktur data yang relatif stabil dan jarang berubah

## 3. **Tabel `mata_pelajaran`**
**Alasan:**
- Menjadi referensi untuk input nilai
- Mendefinisikan kategori mata pelajaran (IPA/IPS) yang penting untuk perhitungan nilai rata-rata
- Dibutuhkan sebelum input nilai siswa

## 4. **Tabel `siswa`**
**Alasan:**
- Data utama yang menjadi subjek prediksi
- Semua tabel terkait data siswa (nilai, test IQ, minat) mereferensikan tabel ini
- Tanpa data siswa, tidak mungkin melakukan prediksi

## 5. **Tabel `nilai`**
**Alasan:**
- Data nilai merupakan faktor penting dalam prediksi jurusan
- Memberikan informasi akademis siswa untuk kategori IPA dan IPS
- Dibutuhkan untuk menghitung kategori nilai (Tinggi, Sedang, Rendah)

## 6. **Tabel `test_iq`**
**Alasan:**
- Menyediakan data kemampuan kognitif siswa
- Menjadi salah satu atribut kunci dalam algoritma C4.5
- Kategori IQ (Tinggi, Sedang, Rendah) berpengaruh signifikan pada hasil prediksi

## 7. **Tabel `minat_siswa`**
**Alasan:**
- Menyediakan data preferensi siswa sendiri
- Bisa menjadi faktor pendukung dalam prediksi
- Dapat digunakan untuk validasi silang hasil prediksi

## 8. **Tabel `training_data`**
**Alasan:**
- Dibutuhkan untuk pelatihan model C4.5
- Berisi data historis dengan hasil jurusan yang sudah diketahui
- Kualitas data training menentukan akurasi model prediksi

## 9. **Tabel `model_metadata`**
**Alasan:**
- Menyimpan informasi tentang versi model yang dibuat
- Penting untuk pengelolaan dan pelacakan model
- Mendukung proses versioning model untuk evaluasi performa

## 10. **Tabel `decision_tree_nodes`**
**Alasan:**
- Menyimpan struktur pohon keputusan hasil algoritma C4.5
- Diperlukan untuk memvisualisasikan proses pengambilan keputusan
- Berisi nilai Entropy dan Gain yang menunjukkan proses perhitungan

## 11. **Tabel `decision_rules`**
**Alasan:**
- Menyimpan aturan keputusan yang diekstrak dari pohon keputusan
- Digunakan untuk proses prediksi yang lebih efisien
- Memudahkan interpretasi hasil algoritma

## 12. **Tabel `model_evaluation`**
**Alasan:**
- Menyimpan metrik evaluasi performa model
- Berguna untuk membandingkan efektivitas antar versi model
- Membantu dalam pengambilan keputusan untuk penggunaan model

## 13. **Tabel `prediksi_jurusan`**
**Alasan:**
- Menyimpan hasil prediksi jurusan untuk setiap siswa
- Mengintegrasikan data dari berbagai sumber (nilai, IQ, minat)
- Output utama dari proses prediksi

## 14. **Tabel `log_aktivitas`**
**Alasan prioritas terendah:**
- Berfungsi untuk monitoring dan audit, bukan fungsionalitas utama
- Diisi secara otomatis oleh sistem
- Tidak kritis untuk operasional prediksi

Dengan mengikuti urutan prioritas ini, pengembangan sistem dapat dilakukan secara bertahap dengan memastikan bahwa tabel-tabel yang fundamental dan paling dibutuhkan diimplementasikan terlebih dahulu. Hal ini juga memudahkan dalam proses pengujian dan validasi sistem secara bertahap.