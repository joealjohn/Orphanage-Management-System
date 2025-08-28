<?php
// Set page title and current page for active menu
global $conn;
$page_title = "Contact Us";
$current_page = "contact";

include 'includes/session.php';
include 'includes/db.php';

// Check if user is logged in - only allow logged-in users to access contact form
if (!isLoggedIn()) {
    // Store intended destination for after login
    $_SESSION['redirect_url'] = 'contact.php';
    // Redirect to login page with a message
    header("Location: login.php?msg=login_required");
    exit();
}

$success = "";
$error = "";

// Process contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Validate input
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required!";
    } else {
        // Insert message into database
        $query = "INSERT INTO contact_messages (name, email, subject, message) 
                  VALUES ('$name', '$email', '$subject', '$message')";

        if (mysqli_query($conn, $query)) {
            $success = "Your message has been sent successfully! We will get back to you soon.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

include 'includes/header.php';
?>

    <!-- Contact Hero Section -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-envelope me-2"></i>Contact Us</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Contact Content -->
    <section class="contact-section py-5">
        <div class="container">
            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-4">
                    <div class="contact-info-card">
                        <h3><i class="fas fa-map-marker-alt text-light me-2"></i>Our Location</h3>
                        <p>123 Orphanage Road<br>City, State 12345<br>Country</p>

                        <h3 class="mt-4"><i class="fas fa-phone text-light me-2"></i>Call Us</h3>
                        <p>+1 234 567 8900<br>+1 234 567 8901</p>

                        <h3 class="mt-4"><i class="fas fa-envelope text-light me-2"></i>Email Us</h3>
                        <p>info@orphanage.org<br>support@orphanage.org</p>

                        <h3 class="mt-4"><i class="fas fa-clock text-light me-2"></i>Opening Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 5:00 PM<br>Saturday: 10:00 AM - 2:00 PM<br>Sunday: Closed</p>

                        <div class="social-media mt-4">
                            <h3><i class="fas fa-share-alt text-light me-2"></i>Follow Us</h3>
                            <div class="social-icons">
                                <a href="#" class="social-icon-light"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon-light"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon-light"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-icon-light"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form - Only visible to logged-in users -->
                <div class="col-lg-8">
                    <div class="contact-form-card">
                        <h3 class="mb-4">Send us a Message</h3>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="contact.php" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" value="<?php echo $_SESSION['name'] ?? ''; ?>" required>
                                    <div class="invalid-feedback">Please enter your name</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Your Email</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?php echo $_SESSION['email'] ?? ''; ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="" selected disabled>Select a subject</option>
                                    <option value="Adoption Inquiry">Adoption Inquiry</option>
                                    <option value="Volunteering">Volunteering</option>
                                    <option value="Donation">Donation</option>
                                    <option value="Child Registration">Child Registration</option>
                                    <option value="General Question">General Question</option>
                                </select>
                                <div class="invalid-feedback">Please select a subject</div>
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Enter your message" required></textarea>
                                <div class="invalid-feedback">Please enter your message</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Google Map -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.952912260219!2d3.375295414770757!3d6.5276300249594!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103b8b2ae68280c1%3A0xdc9e87a367c3d9cb!2sLagos%2C%20Nigeria!5e0!3m2!1sen!2sng!4v1629213896913!5m2!1sen!2sng"
                                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title"><i class="fas fa-question-circle me-2 text-success"></i>Frequently Asked Questions</h2>
            <div class="section-divider mx-auto mb-5"></div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="contactFAQ">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How can I adopt a child?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#contactFAQ">
                                <div class="accordion-body">
                                    To adopt a child, you need to register an account, browse our available children, and submit an adoption request. Our team will review your application and contact you for further steps, including home visits and background checks.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    How can I register a homeless child?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#contactFAQ">
                                <div class="accordion-body">
                                    If you know of a child in need, you can register them through our system. Create an account, fill out the child registration form with as much information as possible, and our team will review and follow up on the case.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Can I volunteer at the orphanage?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#contactFAQ">
                                <div class="accordion-body">
                                    Yes! We welcome volunteers who want to contribute their time and skills. Please use our contact form to express your interest in volunteering, detailing your availability and areas of expertise, and our volunteer coordinator will contact you.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    How long does the adoption process take?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#contactFAQ">
                                <div class="accordion-body">
                                    The adoption process typically takes 3-6 months from initial application to final approval. This includes background checks, home visits, interviews, and the necessary legal procedures to ensure the child is placed in a safe and loving home.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>