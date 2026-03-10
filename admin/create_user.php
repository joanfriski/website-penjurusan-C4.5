<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buat User Baru – SMA Negeri 1 Ciwaru</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        h1 { color: #333; }
        form { max-width: 500px; margin: 0 auto; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"], input[type="email"], input[type="tel"], select { width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>Buat User Baru</h1>
    <form action="insert_user.php" method="post">
        <label for="username">Username (wajib):</label>
        <input type="text" id="username" name="username" required />
        <label for="password">Password (wajib):</label>
        <input type="password" id="password" name="password" required />
        <label for="nama_lengkap">Nama Lengkap (wajib):</label>
        <input type="text" id="nama_lengkap" name="nama_lengkap" required />
        <label for="role">Role (wajib):</label>
        <select id="role" name="role" required>
            <option value="">-- Pilih Role --</option>
            <option value="staff_bk">Staff BK</option>
            <option value="kepsek">Kepsek</option>
        </select>
        <label for="email">Email (opsional):</label>
        <input type="email" id="email" name="email" />
        <label for="no_telp">No. Telp (opsional):</label>
        <input type="tel" id="no_telp" name="no_telp" />
        <button type="submit">Simpan</button>
    </form>
</body>
</html> 