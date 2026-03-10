<?php
session_start();
if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if($_SESSION['role'] === 'staff_bk') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../user/index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMA Negeri 1 Ciwaru - Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #3498db, #8e44ad);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-attachment: fixed;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 360px;
            text-align: center;
        }
        .logo {
            width: 120px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .school-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        .login-title {
            font-size: 20px;
            font-weight: 500;
            color: #444;
            margin-bottom: 25px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-login:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 5px;
            font-weight: 500;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="../image/logo.png" alt="Logo SMA Negeri 1 Ciwaru" class="logo">
        <div class="school-name">SMA NEGERI 1 CIWARU</div>
        <div class="school-address">Jl. Raya Ciwaru, Kec. Ciwaru, Kab. Kuningan, Jawa Barat</div>
        <div class="login-title">SISTEM INFORMASI AKADEMIK</div>
        
        <?php if(isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="error">Username atau password yang Anda masukkan salah!</div>
        <?php endif; ?>
        
        <form action="proses_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
            </div>
            <button type="submit" class="btn-login">MASUK</button>
        </form>
        <div class="footer">
            &copy; <?php echo date('Y'); ?> SMA Negeri 1 Ciwaru - Semua Hak Dilindungi
        </div>
    </div>
</body>
</html>