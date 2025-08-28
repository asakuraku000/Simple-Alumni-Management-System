<?php
require_once 'db_con.php';

// Get statistics for the about page
$alumni_count = fetchRow("SELECT COUNT(*) as count FROM alumni WHERE is_active = 1")['count'];
$colleges_count = fetchRow("SELECT COUNT(*) as count FROM colleges WHERE is_active = 1")['count'];
$programs_count = fetchRow("SELECT COUNT(*) as count FROM programs WHERE is_active = 1")['count'];
$batches_count = fetchRow("SELECT COUNT(*) as count FROM batches")['count'];

// Get recent achievements or highlights (you can modify this query based on your database structure)
$recent_highlights = fetchAll("
    SELECT title, content, created_at, featured_image 
    FROM announcements 
    WHERE announcement_type = 'Achievement' 
    AND status = 'Published' 
    AND is_public = 1 
    ORDER BY created_at DESC 
    LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - NISU Alumni System</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
       <link rel="icon" href="default/logo.png" type="image/x-icon" />
    
    
    <style>
        .hero-section {
            background: linear-gradient(rgba(21, 114, 232, 0.8), rgba(21, 114, 232, 0.8)), 
                        url('/placeholder.svg?height=500&width=1200') center/cover;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            font-weight: 700;
            color: #333;
        }
        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
        }
        .stat-card {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #1572e8;
        }
        .mission-vision-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            height: 100%;
        }
        .mission-vision-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #1572e8, #0d5ac3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
            box-shadow: 0 4px 12px rgba(21, 114, 232, 0.3);
        }
        .history-timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 30px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 5px;
            width: 20px;
            height: 20px;
            background: #1572e8;
            border-radius: 50%;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 24px;
            top: 25px;
            width: 2px;
            height: calc(100% + 10px);
            background: #e9ecef;
        }
        .timeline-item:last-child::after {
            display: none;
        }
        .timeline-year {
            font-weight: bold;
            color: #1572e8;
            font-size: 1.2rem;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(45deg, #1572e8, #0d5ac3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(21, 114, 232, 0.3);
        }
        .team-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        .team-card:hover {
            transform: translateY(-5px);
        }
        .team-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            object-fit: cover;
        }
        .highlight-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }
        .highlight-card:hover {
            transform: translateY(-5px);
        }
        /* Enhanced icon styling */
        .mission-vision-icon i,
        .feature-icon i {
            display: block;
        }
        /* Contact section icons */
        .contact-icon {
            color: #1572e8;
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="color:blue;">
                &nbsp;&nbsp;<img src="default/logo.png" alt="NISU" height="40" class="me-2" onerror="this.src='/placeholder.svg?height=40&width=40'">
                NISU Alumni
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="margin-right:10px;">
                <span style="color:black;text-align:center;font-size:1rem;line-height:1rem;padding:10px;">&#9776;</span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php" style="color:black;">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php" style="color:#1572e8;">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3 ms-2" href="admin_login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">About Northern Iloilo State University</h1>
            <p class="lead mb-0">Excellence in Education, Innovation, and Service</p>
        </div>
    </section>

    <!-- Mission, Vision, Goals Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Our Foundation</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="mission-vision-card">
                        <div class="mission-vision-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h4 class="mb-3">Vision</h4>
                        <p>To be a leading state university in the Philippines that produces globally competitive graduates and conducts relevant research and extension services for sustainable development.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="mission-vision-card">
                        <div class="mission-vision-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h4 class="mb-3">Mission</h4>
                        <p>To provide quality, accessible, and relevant higher education that develops competent and socially responsible individuals through excellent instruction, research, and extension services.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="mission-vision-card">
                        <div class="mission-vision-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h4 class="mb-3">Goals</h4>
                        <p>To foster academic excellence, promote research and innovation, strengthen community partnerships, and develop sustainable programs that contribute to national development.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <h2 class="section-title">Our Impact in Numbers</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $alumni_count ?></div>
                        <div class="stat-label">Alumni Registered</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $colleges_count ?></div>
                        <div class="stat-label">Colleges</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $programs_count ?></div>
                        <div class="stat-label">Academic Programs</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $batches_count ?></div>
                        <div class="stat-label">Graduation Batches</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- University History -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="section-title text-start">Our Rich History</h2>
                    <div class="history-timeline">
                        <div class="timeline-item">
                            <div class="timeline-year">1969</div>
                            <h5>Foundation</h5>
                            <p>Northern Iloilo State University was established as Northern Iloilo Polytechnic College through Republic Act No. 5635.</p>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">1998</div>
                            <h5>University Status</h5>
                            <p>Converted to university status through Republic Act No. 8596, becoming Northern Iloilo State University.</p>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2010</div>
                            <h5>Expansion</h5>
                            <p>Established multiple satellite campuses to serve more communities across Northern Iloilo.</p>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2020</div>
                            <h5>Digital Transformation</h5>
                            <p>Launched comprehensive digital learning platforms and modernized educational delivery systems.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <img src="/placeholder.svg?height=400&width=500" alt="NISU Campus" class="img-fluid rounded shadow"
                         onerror="this.src='default/logo.png'">
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values & Features -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Our Core Values</h2>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h5>Integrity</h5>
                        <p>We uphold honesty, transparency, and ethical conduct in all our endeavors.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5>Excellence</h5>
                        <p>We strive for the highest standards in education, research, and service delivery.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Inclusivity</h5>
                        <p>We embrace diversity and provide equal opportunities for all members of our community.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h5>Innovation</h5>
                        <p>We foster creativity and encourage innovative approaches to teaching and learning.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Leadership Team -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">University Leadership</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="team-card">
                        <img src="default/default-admin.jpg" alt="University President" class="team-avatar"
                             onerror="this.src='default/default-president.png'">
                        <h5>Dr. [President Name]</h5>
                        <p class="text-muted">University President</p>
                        <p class="small">Leading the university with vision and dedication to academic excellence and community service.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="team-card">
                        <img src="default/default-admin.jpg" alt="Vice President" class="team-avatar"
                             onerror="this.src='default/default-vp.png'">
                        <h5>Dr. [VP Name]</h5>
                        <p class="text-muted">Vice President for Academic Affairs</p>
                        <p class="small">Overseeing academic programs and ensuring quality education delivery across all campuses.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="team-card">
                        <img src="default/default-admin.jpg" alt="VP Admin" class="team-avatar"
                             onerror="this.src='default/default-admin.png'">
                        <h5>Dr. [Admin VP Name]</h5>
                        <p class="text-muted">Vice President for Administration</p>
                        <p class="small">Managing administrative operations and supporting institutional development initiatives.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Achievements -->
    <?php if (!empty($recent_highlights)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Recent Achievements</h2>
            <div class="row">
                <?php foreach ($recent_highlights as $highlight): ?>
                <div class="col-md-4 mb-4">
                    <div class="highlight-card">
                        <?php if ($highlight['featured_image']): ?>
                        <img src="<?= htmlspecialchars($highlight['featured_image']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;"
                             onerror="this.src='default/default-achievement.png'">
                        <?php endif; ?>
                        <div class="card-body p-4">
                            <h5 class="card-title"><?= htmlspecialchars($highlight['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars(substr($highlight['content'], 0, 120)) ?>...</p>
                            <small class="text-muted">
                                <?= date('M j, Y', strtotime($highlight['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact Information -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Get in Touch</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="feature-card text-start">
                        <h5><i class="fas fa-map-marker-alt contact-icon me-2"></i>Main Campus</h5>
                        <p>Northern Iloilo State University<br>
                        Estancia, Iloilo, Philippines 5017</p>
                        
                        <h5 class="mt-4"><i class="fas fa-phone contact-icon me-2"></i>Contact Numbers</h5>
                        <p>Phone: +63 (33) 331-9447<br>
                        Mobile: +63 917-XXX-XXXX</p>
                        
                        <h5 class="mt-4"><i class="fas fa-envelope contact-icon me-2"></i>Email Address</h5>
                        <p>info@nisu.edu.ph<br>
                        alumni@nisu.edu.ph</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="feature-card text-start">
                        <h5><i class="fas fa-globe contact-icon me-2"></i>Online Presence</h5>
                        <div class="d-flex mb-3">
                            <a href="#" class="btn btn-outline-primary me-2">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                            <a href="#" class="btn btn-outline-info me-2">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                        </div>
                        
                        <h5 class="mt-4"><i class="fas fa-clock contact-icon me-2"></i>Office Hours</h5>
                        <p>Monday - Friday: 8:00 AM - 5:00 PM<br>
                        Saturday: 8:00 AM - 12:00 PM<br>
                        Sunday: Closed</p>
                        
                        <h5 class="mt-4"><i class="fas fa-graduation-cap contact-icon me-2"></i>Alumni Relations</h5>
                        <p>For alumni-related inquiries, please contact our Alumni Relations Office during regular business hours.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Northern Iloilo State University</h5>
                    <p>Alumni Information System</p>
                    <p>Estancia, Iloilo, Philippines 5017</p>
                    <p>Phone: +63 (33) 331-9447</p>
                    <p>Email: info@nisu.edu.ph</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50">Home</a></li>
                        <li><a href="about.php" class="text-white-50">About</a></li>
                        <li><a href="admin_login.php" class="text-white-50">Admin Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="d-flex">
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-facebook fa-2x"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-twitter fa-2x"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-linkedin fa-2x"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-instagram fa-2x"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2024 Northern Iloilo State University Alumni System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animation on scroll
        window.addEventListener('scroll', function() {
            const cards = document.querySelectorAll('.feature-card, .team-card, .highlight-card');
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (cardTop < windowHeight * 0.8) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        });

        // Initialize cards with animation-ready state
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card, .team-card, .highlight-card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            });
        });
    </script>
</body>
</html>