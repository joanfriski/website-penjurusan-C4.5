<?php
session_start();
if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if($_SESSION['role'] === 'staff_bk') {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMA Negeri 1 Ciwaru - Selamat Datang</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #3498db, #8e44ad);
            background-attachment: fixed;
            color: #333;
        }
        
        .header {
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            text-align: center;
            position: relative;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            width: 70px;
            margin-right: 15px;
        }
        
        .school-info {
            text-align: left;
        }
        
        .school-name {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            color: #2c3e50;
        }
        
        .school-address {
            font-size: 12px;
            color: #7f8c8d;
            margin: 5px 0 0;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
            padding: 20px;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .welcome-subtitle {
            font-size: 16px;
            color: #34495e;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-item {
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 30px;
            margin-bottom: 10px;
            color: #3498db;
        }
        
        .feature-title {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .btn-login {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-login:hover {
            background: #2980b9;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #7f8c8d;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
            }
            
            .logo {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .school-info {
                text-align: center;
            }
            
            .welcome-card {
                padding: 20px;
            }
            
            .welcome-title {
                font-size: 24px;
            }
        }
        
        /* Animasi */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-card {
            animation: fadeIn 0.8s ease-out;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <img src="image/logo.png" alt="Logo SMA Negeri 1 Ciwaru" class="logo">
            <div class="school-info">
                <h1 class="school-name">SMA NEGERI 1 CIWARU</h1>
                <p class="school-address">Jl. Raya Ciwaru, Kec. Ciwaru, Kab. Kuningan, Jawa Barat</p>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="welcome-card">
            <h2 class="welcome-title">Selamat Datang</h2>
            <p class="welcome-subtitle">Sistem Informasi Akademik SMA Negeri 1 Ciwaru Untuk Penjurusan Siswa.</p>
            

            
            <a href="login/login.php" class="btn-login">Masuk Sistem</a>
        </div>
    </div>
    
    <div class="footer">
        &copy; <?php echo date('Y'); ?> SMA Negeri 1 Ciwaru - Sistem Informasi Akademik
    </div>
</body>
</html>