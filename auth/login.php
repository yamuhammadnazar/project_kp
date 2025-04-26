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
            // Simpan semua data session di sini
            $_SESSION["user_id"] = $user["id"];
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.95);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, rgb(128, 235, 249) 0%, rgb(100, 150, 250) 50%, #2575fc 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.1rem 0;
        }

        .navbar .container {
            display: flex;
            justify-content: flex-start;
            padding-left: 0;
            margin-left: 0;
            max-width: 100%;
        }

        .navbar-brand {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-left: 5px;
            padding-left: 0;
        }

        .navbar-brand img {
            height: auto;
            max-height: 55px;
            width: auto;
            margin-left: 0;
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 0;
            /* Reduced padding */
        }

        .login-container {
            max-width: 300px;
            /* Reduced width */
            width: 100%;
            padding: 1.5rem;
            /* Reduced padding */
            background-color: #fff;
            border-radius: 12px;
            /* Slightly reduced border radius */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            margin: 0 auto;
        }

        .login-container::before {
            content: "";
            position: absolute;
            top: -40px;
            right: -40px;
            width: 80px;
            /* Smaller decorative element */
            height: 80px;
            background-color: rgba(106, 17, 203, 0.1);
            border-radius: 50%;
            z-index: 0;
        }

        .login-container::after {
            content: "";
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 90px;
            /* Smaller decorative element */
            height: 90px;
            background-color: rgba(37, 117, 252, 0.1);
            border-radius: 50%;
            z-index: 0;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1rem;
            /* Reduced margin */
            position: relative;
            z-index: 1;
        }

        .login-icon {
            width: 45px;
            /* Smaller icon */
            height: 45px;
            background: linear-gradient(135deg, rgb(128, 235, 249) 0%, rgb(100, 150, 250) 50%, #2575fc 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.7rem;
            /* Reduced margin */
            box-shadow: 0 4px 12px rgba(106, 17, 203, 0.25);
        }

        .login-icon i {
            font-size: 1.4rem;
            /* Smaller icon */
            color: white;
        }

        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.3rem;
            /* Reduced margin */
            font-size: 1.3rem;
            /* Smaller heading */
        }

        .login-subtitle {
            color: #6c757d;
            font-size: 0.75rem;
            /* Smaller text */
            margin-bottom: 0;
        }

        .form-group {
            position: relative;
            margin-bottom: 0.9rem;
            /* Reduced margin */
            z-index: 1;
        }

        .form-control {
            height: 38px;
            /* Smaller input field */
            padding-left: 35px;
            /* Adjusted padding */
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            /* Smaller border radius */
            font-size: 0.85rem;
            /* Smaller text */
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.2);
        }

        .form-icon {
            position: absolute;
            left: 12px;
            /* Adjusted position */
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 0.9rem;
            /* Smaller icon */
        }

        .btn-login {
            height: 38px;
            /* Smaller button */
            background: linear-gradient(135deg, rgb(128, 235, 249) 0%, rgb(100, 150, 250) 50%, #2575fc 100%);
            border: none;
            border-radius: 8px;
            /* Smaller border radius */
            font-weight: 600;
            font-size: 0.9rem;
            /* Smaller text */
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(106, 17, 203, 0.15);
            transition: all 0.3s;
            color: #ffffff;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            /* Smaller hover effect */
            box-shadow: 0 6px 15px rgba(106, 17, 203, 0.25);
            color: #f8f9fa;
        }

        .btn-login i {
            margin-right: 6px;
            /* Reduced margin */
        }

        .error-alert {
            border-radius: 8px;
            /* Smaller border radius */
            animation: fadeIn 0.5s;
            border: none;
            background-color: #fff5f5;
            color: #e53e3e;
            padding: 0.6rem;
            /* Reduced padding */
            margin-bottom: 1rem;
            /* Reduced margin */
            position: relative;
            z-index: 1;
            box-shadow: 0 2px 8px rgba(229, 62, 62, 0.1);
            font-size: 0.8rem;
            /* Smaller text */
        }

        .error-alert i {
            margin-right: 6px;
            /* Reduced margin */
            font-size: 0.9rem;
            /* Smaller icon */
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-footer {
            text-align: center;
            margin-top: 0.9rem;
            /* Reduced margin */
            color: #6c757d;
            font-size: 0.7rem;
            /* Smaller text */
            position: relative;
            z-index: 1;
        }

        /* Pemusatan yang lebih baik untuk semua ukuran layar */
        .login-row {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            /* Adjusted height */
        }

        /* Responsif untuk layar kecil */
        @media (max-width: 576px) {
            .login-container {
                padding: 1.2rem;
                max-width: 280px;
                /* Even smaller on mobile */
            }

            .navbar-brand img {
                max-height: 45px;
            }

            .login-icon {
                width: 40px;
                height: 40px;
            }

            .login-icon i {
                font-size: 1.3rem;
            }

            .login-footer p {
                font-size: 0.65rem;
            }

            h2 {
                font-size: 1.2rem;
            }
        }

        /* Responsif untuk ukuran layar berbeda */
        @media (min-width: 768px) {
            .navbar-brand img {
                max-height: 60px;
            }
        }

        @media (min-width: 992px) {
            .navbar-brand img {
                max-height: 65px;
            }
        }

        @media (min-width: 1200px) {
            .navbar-brand img {
                max-height: 70px;
            }
        }

        @media (max-width: 400px) {
            .navbar-brand img {
                max-height: 35px;
            }
        }

        /* Override Bootstrap container untuk mentokkan logo ke kiri */
        .container-fluid {
            padding-left: 5px;
            padding-right: 15px;
        }
    </style>
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
                        <p class="login-subtitle">Masukkan Username dan Password</p>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="error-alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <i class="fas fa-user form-icon"></i>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                                required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock form-icon"></i>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i> Masuk
                            </button>
                        </div>
                    </form>

                    <div class="login-footer">
                        <p>Sistem Pencatatan dan Monitoring Tugas<br>Bidang Informasi Komunikasi dan Publikasi &copy;
                            2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5.3.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
    <!-- Tambahkan JavaScript untuk meningkatkan UX -->
    <script>
        // Menambahkan JavaScript untuk meningkatkan pengalaman pengguna
        document.addEventListener('DOMContentLoaded', function () {
            // Animasi fade-in untuk container login
            const loginContainer = document.querySelector('.login-container');
            loginContainer.style.opacity = '0';
            setTimeout(() => {
                loginContainer.style.transition = 'opacity 0.8s ease-in-out';
                loginContainer.style.opacity = '1';
            }, 100);

            // Animasi untuk form fields saat fokus
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(input => {
                input.addEventListener('focus', function () {
                    this.parentElement.style.transition = 'transform 0.3s ease';
                    this.parentElement.style.transform = 'translateY(-3px)';
                });

                input.addEventListener('blur', function () {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Tambahkan toggle untuk password visibility
            const passwordField = document.getElementById('password');
            const passwordGroup = passwordField.parentElement;

            // Buat tombol toggle password
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'btn btn-sm position-absolute';
            toggleBtn.style.right = '8px'; // Adjusted position
            toggleBtn.style.top = '50%';
            toggleBtn.style.transform = 'translateY(-50%)';
            toggleBtn.style.border = 'none';
            toggleBtn.style.background = 'transparent';
            toggleBtn.style.color = '#6c757d';
            toggleBtn.style.fontSize = '0.8rem'; // Smaller icon
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            toggleBtn.style.zIndex = '5';

            toggleBtn.addEventListener('click', function () {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordField.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });

            passwordGroup.appendChild(toggleBtn);

            // Form validation
            const loginForm = document.querySelector('form');
            const usernameField = document.getElementById('username');

            loginForm.addEventListener('submit', function (e) {
                let isValid = true;

                // Validasi username
                if (usernameField.value.trim() === '') {
                    showError(usernameField, 'Username tidak boleh kosong');
                    isValid = false;
                } else {
                    removeError(usernameField);
                }

                // Validasi password
                if (passwordField.value.trim() === '') {
                    showError(passwordField, 'Password tidak boleh kosong');
                    isValid = false;
                } else {
                    removeError(passwordField);
                }

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                // Tampilkan loading spinner saat submit
                const loginBtn = document.querySelector('.btn-login');
                const originalBtnText = loginBtn.innerHTML;
                loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
                loginBtn.disabled = true;

                // Kita tidak mencegah submit default karena form perlu diproses oleh PHP
                // Namun dalam kasus nyata, Anda mungkin ingin menambahkan timeout untuk mengembalikan tombol ke keadaan semula
                // jika server tidak merespons dalam waktu tertentu
                setTimeout(() => {
                    loginBtn.innerHTML = originalBtnText;
                    loginBtn.disabled = false;
                }, 3000); // Timeout 3 detik jika server tidak merespons
            });

            // Fungsi untuk menampilkan error
            function showError(input, message) {
                const formGroup = input.parentElement;
                let errorDiv = formGroup.querySelector('.error-feedback');

                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'error-feedback';
                    errorDiv.style.color = '#dc3545';
                    errorDiv.style.fontSize = '0.75rem'; // Smaller error text
                    errorDiv.style.marginTop = '3px'; // Reduced margin
                    errorDiv.style.animation = 'fadeIn 0.3s';
                    formGroup.appendChild(errorDiv);
                }

                input.style.borderColor = '#dc3545';
                errorDiv.textContent = message;
            }

            // Fungsi untuk menghapus error
            function removeError(input) {
                const formGroup = input.parentElement;
                const errorDiv = formGroup.querySelector('.error-feedback');

                input.style.borderColor = '#e1e5eb';
                if (errorDiv) {
                    formGroup.removeChild(errorDiv);
                }
            }

            // Efek hover untuk tombol login
            const loginButton = document.querySelector('.btn-login');
            loginButton.addEventListener('mouseenter', function () {
                this.style.transition = 'all 0.3s ease';
                this.style.transform = 'translateY(-2px)'; // Smaller hover effect
                this.style.boxShadow = '0 6px 15px rgba(106, 17, 203, 0.25)';
            });

            loginButton.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 12px rgba(106, 17, 203, 0.15)';
            });

            // Animasi untuk error alert jika ada
            const errorAlert = document.querySelector('.error-alert');
            if (errorAlert) {
                errorAlert.style.opacity = '0';
                errorAlert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    errorAlert.style.transition = 'all 0.5s ease';
                    errorAlert.style.opacity = '1';
                    errorAlert.style.transform = 'translateY(0)';
                }, 300);
            }
        });
    </script>
</body>

</html>