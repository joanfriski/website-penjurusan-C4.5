 Tutorial Lengkap Menggunakan Sistem Decision Tree
LANGKAH 1: Kelola Data Training
Apa itu Data Training?
Data training adalah data historis yang digunakan untuk mengajarkan model cara membuat keputusan.
Tahap 1.1: Import Data dari Siswa

Klik "Kelola Data" di dashboard
Klik tombol "Import Data"
Sistem akan otomatis mengambil data siswa yang sudah ada:

Nilai IPA dan IPS → dikategorikan (Tinggi/Sedang/Rendah)
Test IQ → kategori (Tinggi/Sedang/Rendah)
Minat siswa → IPA/IPS
Hasil aktual → berdasarkan nilai tertinggi


Data akan tersimpan dengan label Training (70%) dan Test (30%)

Tahap 1.2: Tambah Data Manual (jika perlu)

Gunakan form "Tambah Data Training"
Isi:

Kategori Nilai: Tinggi/Sedang/Rendah
Kategori IQ: Tinggi/Sedang/Rendah
Minat: IPA/IPS (opsional)
Hasil Aktual: IPA/IPS
Tipe: Training atau Test


Klik "Tambah"

Minimal Data:

Training: minimal 10-15 data
Test: minimal 5 data
Seimbang antara IPA dan IPS


LANGKAH 2: Train Model
Apa itu Training Model?
Proses mengajarkan komputer untuk membuat pohon keputusan berdasarkan data training.
Tahap 2.1: Mulai Training

Klik "Train Model" di dashboard
Isi:

Versi Model: misal "v1.0"
Deskripsi: "Model pertama - data 2024"


Klik "Train Model"

Tahap 2.2: Proses yang Terjadi

Sistem mengambil data training
Menghitung Entropy dan Gain untuk setiap atribut
Memilih atribut terbaik sebagai root
Membuat cabang untuk setiap nilai atribut
Proses diulang untuk setiap cabang
Pohon tersimpan di database

Hasil:

Model baru terbuat
Akurasi otomatis dihitung
Pohon keputusan siap digunakan


LANGKAH 3: Prediksi
Apa itu Prediksi?
Menggunakan model untuk merekomendasikan jurusan siswa baru.
Tahap 3.1: Pilih Siswa

Klik "Prediksi" di dashboard
Pilih siswa dari dropdown
Pilih model yang akan digunakan

Tahap 3.2: Proses Prediksi

Sistem otomatis mengambil:

Nilai rata-rata IPA dan IPS
Hasil test IQ terakhir
Minat terakhir yang diinput


Mengkategorikan data:

Nilai → Tinggi/Sedang/Rendah
IQ sudah terkategorikan


Model "berjalan" melalui pohon keputusan
Hasil: IPA atau IPS

Output yang Ditampilkan:

Rekomendasi jurusan
Detail data yang digunakan
Alasan keputusan (path pohon)


LANGKAH 4: Evaluasi Model
Apa itu Evaluasi?
Mengetes seberapa akurat model dengan data yang tidak pernah dilihat.
Tahap 4.1: Test Model

Klik "Test Model" di dashboard
Pilih model yang akan dievaluasi
Klik "Test Model"

Tahap 4.2: Hasil Evaluasi
Sistem akan menampilkan:

Akurasi: Persentase prediksi yang benar

Contoh: 85% = 17 dari 20 prediksi benar


Precision: Dari yang diprediksi IPA, berapa % yang benar IPA

IPA: 90% = 9 dari 10 prediksi IPA memang IPA
IPS: 80% = 8 dari 10 prediksi IPS memang IPS


Recall: Dari yang seharusnya IPA, berapa % yang berhasil diprediksi

IPA: 85% = 9 dari 11 siswa IPA berhasil diprediksi
IPS: 88% = 8 dari 9 siswa IPS berhasil diprediksi


Confusion Matrix:
        Predicted
        IPA  IPS
Actual IPA  9    2
       IPS  1    8


Tahap 4.3: Interpretasi

Akurasi > 80%: Model bagus
Akurasi 70-80%: Model cukup baik
Akurasi < 70%: Perlu lebih banyak data atau review model


🔄 Workflow Lengkap
Skenario Praktis:
Minggu 1: Persiapan Data

Import data 30 siswa
Review dan koreksi data training
Pastikan ada 20 data training, 10 data test

Minggu 2: Buat Model Pertama

Train model v1.0
Evaluasi → dapat akurasi 75%
Analisis error

Minggu 3: Perbaikan

Tambah 10 data training lagi
Train model v1.1
Evaluasi → dapat akurasi 85%

Minggu 4: Implementasi

Gunakan model v1.1 untuk prediksi
Input 20 siswa baru
Analisis hasil prediksi
Dokumentasi untuk BK


⚠️ Tips Penting

Data Training Berkualitas

Pastikan data akurat
Seimbangkan jumlah IPA dan IPS
Update berkala dengan data terbaru


Multiple Models

Buat beberapa versi model
Bandingkan performanya
Gunakan yang terbaik


Monitoring

Cek akurasi rutin
Jika akurasi turun, retrain dengan data baru
Simpan log prediksi untuk evaluasi


Interpretasi Hasil

Prediksi adalah rekomendasi, bukan keputusan final
Pertimbangkan faktor lain (minat, bakat, dll)
Diskusikan dengan siswa dan orangtua