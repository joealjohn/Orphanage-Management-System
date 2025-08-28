<?php
// Set page title and current page for active menu
$page_title = "About Us";
$current_page = "about";

include 'includes/session.php';
include 'includes/db.php';
include 'includes/header.php';
?>

    <!-- About Hero Section -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-info-circle me-2"></i>About Our Orphanage</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">About</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- About Content -->
    <section class="about-content py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h2>Our Story</h2>
                    <p class="lead">A Decade of Making a Difference in Children's Lives</p>
                    <p>Our orphanage was founded in 2015 with a simple but powerful mission: to provide every orphaned and abandoned child with a safe home, quality education, and the love they deserve.</p>
                    <p>What started as a small shelter with just 5 children has grown into a comprehensive care facility that has helped over 500 children find new homes and build brighter futures.</p>
                    <p>We believe that every child deserves the opportunity to grow in a nurturing environment, receive quality education, and develop the skills needed to become confident, self-sufficient adults.</p>

                    <div class="mt-4">
                        <h4><i class="fas fa-heart me-2 text-success"></i>Our Values</h4>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Child-centered care</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Compassion and empathy</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Quality education</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Integrity and transparency</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Community engagement</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Cultural sensitivity</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image-container">
                        <img src="https://images.unsplash.com/photo-1597113366853-fea190b6cd82?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Children playing" class="img-fluid rounded shadow-lg">
                    </div>

                    <div class="mission-vision-card mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mission-box">
                                    <h4><i class="fas fa-bullseye me-2"></i>Our Mission</h4>
                                    <p>To provide a nurturing environment where orphaned and vulnerable children can heal, learn, and thrive until they find their forever families.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="vision-box">
                                    <h4><i class="fas fa-eye me-2"></i>Our Vision</h4>
                                    <p>A world where every child has a loving family, access to quality education, and the opportunity to reach their full potential.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title"><i class="fas fa-users me-2 text-success"></i>Our Team</h2>
            <div class="section-divider mx-auto mb-5"></div>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-card">
                        <div class="team-image">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Team Member">
                        </div>
                        <div class="team-info">
                            <h4>Sarah Johnson</h4>
                            <p class="designation">Director</p>
                            <p class="small">Leading our orphanage with 15+ years of experience in child welfare.</p>
                            <div class="social-links mt-3">
                                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-card">
                        <div class="team-image">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Team Member">
                        </div>
                        <div class="team-info">
                            <h4>Michael Brown</h4>
                            <p class="designation">Child Psychologist</p>
                            <p class="small">Specialized in trauma-informed care and child development.</p>
                            <div class="social-links mt-3">
                                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-card">
                        <div class="team-image">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Team Member">
                        </div>
                        <div class="team-info">
                            <h4>Emily Wilson</h4>
                            <p class="designation">Education Coordinator</p>
                            <p class="small">Ensuring every child receives quality education tailored to their needs.</p>
                            <div class="social-links mt-3">
                                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-card">
                        <div class="team-image">
                            <img src="https://randomuser.me/api/portraits/men/67.jpg" alt="Team Member">
                        </div>
                        <div class="team-info">
                            <h4>David Taylor</h4>
                            <p class="designation">Adoption Specialist</p>
                            <p class="small">Connecting children with loving families for over 10 years.</p>
                            <div class="social-links mt-3">
                                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Achievement Section -->
    <section class="achievements-section py-5">
        <div class="container">
            <h2 class="text-center section-title"><i class="fas fa-trophy me-2 text-success"></i>Our Achievements</h2>
            <div class="section-divider mx-auto mb-5"></div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="achievement-card">
                        <div class="achievement-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="achievement-content">
                            <h4>Community Service Excellence Award (2023)</h4>
                            <p>Recognized for outstanding contribution to child welfare and community development.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="achievement-card">
                        <div class="achievement-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="achievement-content">
                            <h4>Best Child Care Facility (2022)</h4>
                            <p>Awarded by the National Child Welfare Association for maintaining highest standards.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="achievement-card">
                        <div class="achievement-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <div class="achievement-content">
                            <h4>Education Innovation Award (2021)</h4>
                            <p>For implementing progressive educational programs for orphaned children.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="achievement-card">
                        <div class="achievement-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="achievement-content">
                            <h4>Best Adoption Facilitation (2020)</h4>
                            <p>Recognized for successfully connecting over 100 children with loving families.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title"><i class="fas fa-history me-2 text-success"></i>Our Journey</h2>
            <div class="section-divider mx-auto mb-5"></div>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3>2015</h3>
                        <p>Founded with a small facility housing 5 children and 3 staff members.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3>2017</h3>
                        <p>Expanded to accommodate 25 children and introduced educational programs.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3>2019</h3>
                        <p>Launched our adoption program, successfully placing 15 children with loving families.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3>2021</h3>
                        <p>Received community excellence award for our work with orphaned children.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3>2025</h3>
                        <p>Celebrating a decade of service with over 500 children helped and 120 successful adoptions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section py-5">
        <div class="container text-center">
            <h2 class="mb-4 text-white"><i class="fas fa-hands-helping me-2"></i> Want to Help?</h2>
            <p class="lead text-white mb-4">There are many ways to support our mission and make a difference in children's lives</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                        <?php if (!isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-light btn-lg px-4 me-md-2 rounded-pill">
                                <i class="fas fa-user-plus me-2"></i>Become a Member
                            </a>
                            <a href="login.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        <?php else: ?>
                            <a href="contact.php" class="btn btn-light btn-lg px-4 me-md-2 rounded-pill">
                                <i class="fas fa-envelope me-2"></i>Contact Us
                            </a>
                            <a href="children.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                                <i class="fas fa-child me-2"></i>View Children
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>