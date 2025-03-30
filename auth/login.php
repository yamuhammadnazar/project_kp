<?php
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        if (password_verify($password, $user["password"])) {
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            
            if ($_SESSION["role"] === "kabid") {
                header("Location: ../dashboard/kabid_dashboard.php");
            } else if ($_SESSION["role"] === "admin") {
                header("Location: ../dashboard/admin_dashboard.php");
            } else {
                header("Location: ../dashboard/anggota_dashboard.php");
            }
            exit();
        } else {
            $error_message = "❌ Password salah!";
        }
    } else {
        $error_message = "❌ Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth/login.css">
</head>
<body>
    <!-- Navbar untuk logo - menggunakan container-fluid untuk mentokkan ke kiri -->
    <nav class="navbar navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../assets/images/logo_diskominfo.png" alt="Logo" class="img-fluid">
            </a>
        </div>
    </nav>
    <div class="login-wrapper">
        <div class="container">
            <div class="login-row">
                <div class="login-container">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h2>Selamat Datang</h2>
                        <p class="login-subtitle">Masukkan Username dan Password Anda</p>
                    </div>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="error-alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <i class="fas fa-user form-icon"></i>
                            <input type="text" class="form-control form-control-icon" id="username" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock form-icon"></i>
                            <input type="password" class="form-control form-control-icon" id="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i> Masuk
                            </button>
                        </div>
                    </form>
                    
                    <div class="login-footer">
                        <p>Sistem Pencatatan dan Monitoring Tugas<br>Bidang Informasi Komunikasi dan Publikasi &copy; 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5.3.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="../assets/js/auth/login.js"></script>
</body>
</html>
