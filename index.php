<?php
// Set page title and current page for active menu
global $conn;
$page_title = "Home";
$current_page = "home";

include 'includes/session.php'; // Include session.php first
include 'includes/db.php';

// Get count of available children for display
$query = "SELECT COUNT(*) AS child_count FROM children WHERE status = 'available'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$available_children = $row['child_count'];

include 'includes/header.php'; // Include header after session.php
?>

    <!-- Hero Section with improved design -->
    <section class="hero-section">
        <div class="overlay"></div>
        <div class="container text-center hero-content">
            <h1 class="display-3 fw-bold text-white animate-character">Help the children When They Need</h1>
            <p class="lead text-white hero-subtitle">Providing care, love and hope to orphaned children. Join us in making a difference.</p>
            <div class="hero-stats">
                <div class="stat-item">
                    <i class="fas fa-child fa-2x"></i>
                    <p><span class="counter"><?php echo $available_children; ?></span> children waiting</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-home fa-2x"></i>
                    <p><span class="counter">120</span> successful adoptions</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-hands-helping fa-2x"></i>
                    <p><span class="counter">50</span> volunteers</p>
                </div>
            </div>
            <div class="mt-5">
                <a href="#about" class="btn btn-primary btn-lg rounded-pill px-4 me-3 hero-btn">
                    <i class="fas fa-info-circle me-2"></i>Learn More
                </a>
                <a href="register.php" class="btn btn-outline-light btn-lg rounded-pill px-4 hero-btn">
                    <i class="fas fa-hand-holding-heart me-2"></i>Join Us
                </a>
            </div>
        </div>
    </section>

    <!-- About Section with improved design -->
    <section class="py-5 about-section" id="about">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-title"><i class="fas fa-heart text-danger me-2"></i> Our Mission</h2>
                    <div class="section-divider"></div>
                    <p class="lead mt-4">We are committed to providing a safe, nurturing environment for orphaned children. Our goal is to ensure every child receives proper care, education, and the opportunity for adoption into a loving family.</p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-4 mb-4">
                    <div class="feature-card h-100">
                        <div class="card-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h4>Safe Environment</h4>
                        <p>We provide a safe and loving home for children who need it most, ensuring they grow in a nurturing atmosphere.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card h-100">
                        <div class="card-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h4>Quality Education</h4>
                        <p>Every child receives access to quality education and development opportunities to build a brighter future.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card h-100">
                        <div class="card-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h4>Adoption Services</h4>
                        <p>We work to connect children with loving families through our comprehensive adoption program.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="stat-box">
                        <i class="fas fa-child"></i>
                        <h2 class="counter">150</h2>
                        <p>Children Helped</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-box">
                        <i class="fas fa-home"></i>
                        <h2 class="counter">120</h2>
                        <p>Successful Adoptions</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-box">
                        <i class="fas fa-user-friends"></i>
                        <h2 class="counter">50</h2>
                        <p>Staff Members</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-box">
                        <i class="fas fa-calendar-check"></i>
                        <h2 class="counter">10</h2>
                        <p>Years of Service</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-5 testimonials-section">
        <div class="container">
            <h2 class="text-center section-title"><i class="fas fa-quote-left text-primary me-2"></i> Success Stories</h2>
            <div class="section-divider mx-auto"></div>

            <div class="row mt-5">
                <div class="col-lg-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-img">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Testimonial">
                        </div>
                        <div class="testimonial-content">
                            <p>"Adopting our daughter through this organization changed our lives forever. The process was smooth and the staff was incredibly supportive."</p>
                            <h5>Sarah & John</h5>
                            <small>Adoptive Parents</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-img">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Testimonial">
                        </div>
                        <div class="testimonial-content">
                            <p>"I grew up in this orphanage and received amazing care and education. Today, I'm a doctor helping others, all thanks to their support."</p>
                            <h5>Michael R.</h5>
                            <small>Former Resident</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-img">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Testimonial">
                        </div>
                        <div class="testimonial-content">
                            <p>"Volunteering here has been the most rewarding experience. Seeing the children thrive and grow gives me immense satisfaction."</p>
                            <h5>Emily T.</h5>
                            <small>Volunteer</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 cta-section">
        <div class="container text-center">
            <h2 class="mb-4 text-white"><i class="fas fa-heart me-2"></i> Ready to make a difference?</h2>
            <p class="lead text-white mb-4">Join us in our mission to provide every child with a loving home</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                        <a href="register.php" class="btn btn-light btn-lg px-4 me-md-2 rounded-pill">
                            <i class="fas fa-user-plus me-2"></i>Register Now
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>