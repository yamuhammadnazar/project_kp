<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Sistem Pencatatan dan Monitoring Tugas</title>
    <!-- Font Google -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #f8f9fc;
            --dark: #5a5c69;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --body-font: 'Poppins', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--body-font);
            color: var(--dark);
            overflow-x: hidden;
            background-color: #fff;
        }

        /* Header & Navigation */
        header {
            background-color: #fff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        header.scrolled {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 30px;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--dark);
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            padding: 150px 0 100px;
            background: linear-gradient(135deg, #f8f9fc 0%, #e8eaf6 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .hero-text {
            flex: 1;
            max-width: 600px;
        }

        .hero-text h1 {
            font-size: 48px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #333;
        }

        .hero-text h1 span {
            color: var(--primary);
            position: relative;
        }

        .hero-text h1 span::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 8px;
            bottom: 5px;
            left: 0;
            background-color: rgba(78, 115, 223, 0.2);
            z-index: -1;
        }

        .hero-text p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #666;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
        }

        .hero-image {
            flex: 1;
            text-align: right;
            position: relative;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transform: perspective(1000px) rotateY(-5deg);
            transition: all 0.5s ease;
        }

        .hero-image img:hover {
            transform: perspective(1000px) rotateY(0deg);
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .floating-element {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
            filter: blur(10px);
        }

        .element-1 {
            width: 100px;
            height: 100px;
            background-color: rgba(78, 115, 223, 0.3);
            top: 20%;
            left: 10%;
            animation: float 8s ease-in-out infinite;
        }

        .element-2 {
            width: 150px;
            height: 150px;
            background-color: rgba(28, 200, 138, 0.3);
            bottom: 10%;
            right: 15%;
            animation: float 10s ease-in-out infinite;
        }

        .element-3 {
            width: 80px;
            height: 80px;
            background-color: rgba(246, 194, 62, 0.3);
            top: 60%;
            left: 20%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(5deg);
            }

            100% {
                transform: translateY(0) rotate(0deg);
            }
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background-color: #fff;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            width: 70px;
            height: 4px;
            background-color: var(--primary);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .section-title p {
            font-size: 18px;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .feature-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary);
            transition: all 0.3s ease;
            z-index: -1;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-card:hover::before {
            width: 100%;
            opacity: 0.05;
        }

        .feature-icon {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-card p {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
        }

        /* How It Works Section */
        .how-it-works {
            padding: 100px 0;
            background-color: var(--secondary);
            position: relative;
            overflow: hidden;
        }

        .steps-container {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            position: relative;
        }

        .steps-container::before {
            content: '';
            position: absolute;
            top: 50px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: rgba(78, 115, 223, 0.2);
            z-index: 1;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            margin: 0 auto 20px;
            position: relative;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }

        .step h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .step p {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
        }

        /* Testimonials Section */
        .testimonials {
            padding: 100px 0;
            background-color: #fff;
        }

        .testimonials-container {
            display: flex;
            gap: 30px;
            margin-top: 50px;
            overflow-x: auto;
            padding-bottom: 30px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) #f1f1f1;
        }

        .testimonials-container::-webkit-scrollbar {
            height: 8px;
        }

        .testimonials-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .testimonials-container::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 4px;
        }

        .testimonial-card {
            min-width: 350px;
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .testimonial-card::before {
            content: '\201C';
            font-size: 80px;
            position: absolute;
            top: -10px;
            left: 20px;
            color: rgba(78, 115, 223, 0.1);
            font-family: serif;
        }

        .testimonial-content {
            font-size: 16px;
            line-height: 1.6;
                        color: #666;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .author-info h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .author-info p {
            font-size: 14px;
            color: #666;
        }
        
        /* CTA Section */
        .cta {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }
        
        .cta .btn {
            background-color: white;
            color: var(--primary);
            font-size: 18px;
            padding: 12px 30px;
            border-radius: 30px;
        }
        
        .cta .btn:hover {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        /* Footer */
        footer {
            background-color: #2c3e50;
            color: white;
            padding: 60px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 3px;
            background-color: var(--primary);
        }
        
        .footer-column p {
            margin-bottom: 15px;
            line-height: 1.6;
            opacity: 0.8;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            opacity: 1;
            color: var(--primary);
            padding-left: 5px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-bottom p {
            opacity: 0.7;
            font-size: 14px;
        }
        
        /* Responsive Styles */
        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-text {
                margin-bottom: 40px;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .steps-container {
                flex-direction: column;
                gap: 40px;
            }
            
            .steps-container::before {
                display: none;
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .nav-links {
                display: none;
                position: absolute;
                top: 80px;
                left: 0;
                width: 100%;
                background-color: white;
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .nav-links li {
                margin: 10px 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
        }
        
        @media (max-width: 576px) {
            .hero-text h1 {
                font-size: 36px;
            }
            
            .section-title h2 {
                font-size: 28px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .testimonial-card {
                min-width: 280px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header id="header">
        <div class="container">
            <nav>
                <a href="#" class="logo">
                    <i class="bi bi-check2-square"></i>
                    TaskFlow
                </a>
                <ul class="nav-links" id="navLinks">
                    <li><a href="#features">Fitur</a></li>
                    <li><a href="#how-it-works">Cara Kerja</a></li>
                    <li><a href="#testimonials">Testimoni</a></li>
                    <li><a href="#" class="btn btn-outline">Daftar</a></li>
                    <li><a href="login.php" class="btn">Masuk</a></li>
                </ul>
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="bi bi-list"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="floating-elements">
            <div class="floating-element element-1"></div>
            <div class="floating-element element-2"></div>
            <div class="floating-element element-3"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text" data-aos="fade-right" data-aos-duration="1000">
                    <h1>Kelola Tugas dengan <span>Mudah dan Efisien</span></h1>
                    <p>TaskFlow membantu Anda mencatat, memonitor, dan menyelesaikan tugas dengan lebih terorganisir. Tingkatkan produktivitas dan kolaborasi tim Anda sekarang juga!</p>
                    <div class="hero-buttons">
                        <a href="login.php" class="btn">Mulai Sekarang</a>
                        <a href="#how-it-works" class="btn btn-outline">Pelajari Lebih Lanjut</a>
                    </div>
                </div>
                <div class="hero-image" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80" alt="TaskFlow Dashboard">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Fitur Unggulan</h2>
                <p>Nikmati berbagai fitur yang dirancang untuk memudahkan pengelolaan tugas dan meningkatkan produktivitas Anda</p>
            </div>
            <div class="features-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <h3>Pencatatan Tugas</h3>
                    <p>Catat semua tugas dengan detail lengkap termasuk deskripsi, tenggat waktu, dan prioritas.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h3>Monitoring Progres</h3>
                    <p>Pantau kemajuan tugas secara real-time dengan tampilan visual yang mudah dipahami.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3>Kolaborasi Tim</h3>
                    <p>Berkolaborasi dengan anggota tim, bagikan tugas, dan komunikasikan dengan mudah.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon">
                        <i class="bi bi-bell"></i>
                    </div>
                    <h3>Notifikasi & Pengingat</h3>
                    <p>Dapatkan pengingat untuk tenggat waktu dan pembaruan penting agar tidak ada tugas yang terlewat.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3>Laporan & Analitik</h3>
                    <p>Hasilkan laporan komprehensif untuk menganalisis kinerja dan produktivitas tim.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>Keamanan Data</h3>
                    <p>Data Anda selalu aman dengan sistem keamanan berlapis dan enkripsi tingkat tinggi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Cara Kerja</h2>
                <p>Mulai gunakan TaskFlow dengan langkah-langkah sederhana</p>
            </div>
            <div class="steps-container">
                <div class="step" data-aos="fade-up" data-aos-delay="100">
                    <div class="step-number">1</div>
                    <h3>Buat Akun</h3>
                    <p>Daftar dan buat akun TaskFlow Anda dalam hitungan menit.</p>
                </div>
                <div class="step" data-aos="fade-up" data-aos-delay="200">
                    <div class="step-number">2</div>
                    <h3>Tambahkan Tugas</h3>
                    <p>Mulai tambahkan tugas-tugas Anda dengan detail yang diperlukan.</p>
                </div>
                <div class="step" data-aos="fade-up" data-aos-delay="300">
                    <div class="step-number">3</div>
                    <h3>Monitor Progres</h3>
                    <p>Pantau kemajuan tugas dan perbarui status secara berkala.</p>
                </div>
                <div class="step" data-aos="fade-up" data-aos-delay="400">
                    <div class="step-number">4</div>
                    <h3>Analisis Kinerja</h3>
                    <p>Lihat laporan dan analisis untuk meningkatkan produktivitas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Apa Kata Mereka</h2>
                <p>Dengarkan pengalaman pengguna TaskFlow dari berbagai industri</p>
            </div>
            <div class="testimonials-container">
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-content">
                        <p>TaskFlow telah mengubah cara tim kami bekerja. Sekarang kami dapat dengan mudah melacak kemajuan proyek dan memastikan semua tenggat waktu terpenuhi.</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                                                        <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Sarah Johnson">
                        </div>
                        <div class="author-info">
                            <h4>Sarah Johnson</h4>
                            <p>Project Manager, Tech Solutions</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-content">
                        <p>Sebagai seorang freelancer, TaskFlow membantu saya tetap terorganisir dan fokus pada prioritas. Antarmuka yang intuitif membuat pengelolaan tugas menjadi menyenangkan!</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="David Chen">
                        </div>
                        <div class="author-info">
                            <h4>David Chen</h4>
                            <p>Graphic Designer, Freelancer</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-content">
                        <p>Kami menggunakan TaskFlow untuk mengelola seluruh operasi perusahaan. Fitur kolaborasi tim sangat membantu dalam koordinasi antar departemen.</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Amanda Rodriguez">
                        </div>
                        <div class="author-info">
                            <h4>Amanda Rodriguez</h4>
                            <p>COO, Innovate Inc.</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="testimonial-content">
                        <p>Laporan dan analitik TaskFlow memberikan wawasan berharga tentang produktivitas tim kami. Kami dapat mengidentifikasi bottleneck dan meningkatkan efisiensi.</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="Michael Thompson">
                        </div>
                        <div class="author-info">
                            <h4>Michael Thompson</h4>
                            <p>Team Lead, Global Services</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2 data-aos="fade-up">Siap Meningkatkan Produktivitas?</h2>
            <p data-aos="fade-up" data-aos-delay="100">Bergabunglah dengan ribuan pengguna yang telah mengoptimalkan pengelolaan tugas mereka dengan TaskFlow</p>
            <a href="login.php" class="btn" data-aos="fade-up" data-aos-delay="200">Mulai Sekarang - Gratis!</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>TaskFlow</h3>
                    <p>Sistem pencatatan dan monitoring tugas terbaik untuk meningkatkan produktivitas individu dan tim.</p>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Tautan</h3>
                    <ul class="footer-links">
                        <li><a href="#features">Fitur</a></li>
                        <li><a href="#how-it-works">Cara Kerja</a></li>
                        <li><a href="#testimonials">Testimoni</a></li>
                        <li><a href="#">Harga</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Kontak</h3>
                    <ul class="footer-links">
                        <li><i class="bi bi-geo-alt"></i> Jl. Teknologi No. 123, Jakarta</li>
                        <li><i class="bi bi-envelope"></i> info@taskflow.com</li>
                        <li><i class="bi bi-telephone"></i> +62 123 4567 890</li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Newsletter</h3>
                    <p>Berlangganan untuk mendapatkan tips produktivitas dan update terbaru.</p>
                    <form>
                        <input type="email" placeholder="Email Anda" style="padding: 10px; width: 100%; border-radius: 5px; border: none; margin-bottom: 10px;">
                        <button type="submit" class="btn" style="width: 100%;">Berlangganan</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 TaskFlow. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            once: true,
            duration: 800,
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            
            // Change icon based on menu state
            const icon = mobileMenuBtn.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-x-lg');
            } else {
                icon.classList.remove('bi-x-lg');
                icon.classList.add('bi-list');
            }
        });

        // Close mobile menu when clicking on a link
        const navLinksItems = document.querySelectorAll('.nav-links a');
        navLinksItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    navLinks.classList.remove('active');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.remove('bi-x-lg');
                    icon.classList.add('bi-list');
                }
            });
        });
    </script>
</body>
</html>
