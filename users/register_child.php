<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Register a Child";
$current_page = "register_child";

// Include necessary files
include '../includes/session.php';
include '../includes/db.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$error = "";
$success = "";
$user_id = $_SESSION['user_id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $age = (int)$_POST['age'];
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $health_status = mysqli_real_escape_string($conn, $_POST['health_status']);
    $education_level = mysqli_real_escape_string($conn, $_POST['education_level']);
    $found_location = mysqli_real_escape_string($conn, $_POST['found_location']);
    $additional_info = mysqli_real_escape_string($conn, $_POST['additional_info']);

    // Photo upload handling
    $photo = "";
    if (!empty($_FILES['photo']['name'])) {
        $target_dir = "../assets/uploads/";

        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // Check if image file is valid
        $valid_extensions = array("jpg", "jpeg", "png", "gif");
        if (!in_array($file_extension, $valid_extensions)) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif ($_FILES["photo"]["size"] > 5000000) { // 5MB max
            $error = "File is too large. Maximum size is 5MB.";
        } elseif (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo = "assets/uploads/" . $new_filename;
        } else {
            $error = "There was an error uploading your file.";
        }
    }

    // If no errors, insert into database
    if (empty($error)) {
        $query = "INSERT INTO child_registrations (user_id, name, age, gender, health_status, education_level, photo, found_location, additional_info, status, submitted_at) 
                  VALUES ($user_id, '$name', $age, '$gender', '$health_status', '$education_level', '$photo', '$found_location', '$additional_info', 'pending', NOW())";

        if (mysqli_query($conn, $query)) {
            $success = "Child registration submitted successfully! Our team will review your submission and contact you if needed.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Orphanage Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        /* Green theme header */
        .page-header {
            background: linear-gradient(120deg, #28a745, #20883b);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            background: transparent;
            margin: 0;
            padding: 0;
        }

        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: white;
        }

        .breadcrumb-item+.breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form styling */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        /* Section headers */
        h5 {
            font-weight: 600;
            color: #333;
        }

        hr {
            opacity: 0.1;
        }

        /* Button styling */
        .btn-green {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .btn-green:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
        }

        .btn-outline-green {
            border-color: #28a745;
            color: #28a745;
        }

        .btn-outline-green:hover {
            background-color: #28a745;
            color: white;
        }

        /* Alert styling */
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            border-radius: 8px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-radius: 8px;
        }

        .alert-info {
            background-color: #e8f4f8;
            color: #0c5460;
            border-color: #d6e9f9;
            border-radius: 8px;
        }

        /* Feature cards */
        .feature-card {
            height: 100%;
            transition: transform 0.2s;
            border-top: 3px solid #28a745;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card i {
            color: #28a745;
        }

        /* Form sections */
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-section-title {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
            color: #28a745;
        }

        .form-section-title i {
            margin-right: 0.5rem;
        }

        /* Footer adjustments */
        .footer {
            background-color: #343a40;
        }
    </style>
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top bg-success">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <i class="fas fa-child fa-2x me-2 text-white"></i>
            <span>Orphanage Management</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../about.php">
                        <i class="fas fa-info-circle me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../children.php">
                        <i class="fas fa-users me-1"></i> Our Children
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../contact.php">
                        <i class="fas fa-envelope me-1"></i> Contact
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-user-plus me-2"></i>Register a Child</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Register a Child</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger shadow-sm mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Error</h5>
                                <div><?php echo $error; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success shadow-sm mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Success!</h5>
                                <div><?php echo $success; ?></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="dashboard.php" class="btn btn-green me-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                            </a>
                            <a href="register_child.php" class="btn btn-outline-green">
                                <i class="fas fa-plus me-2"></i>Register Another Child
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Child Registration Form</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-info mb-4">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-info-circle fa-2x"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Important Information</h6>
                                        <p class="mb-0">Please provide as much information as possible about the child you are registering. This will help us assess their situation and provide appropriate care.</p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="register_child.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <!-- Basic Information Section -->
                                <div class="form-section mb-4">
                                    <div class="form-section-title">
                                        <i class="fas fa-id-card fa-lg"></i>
                                        <h5 class="mb-0">Basic Information</h5>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Child's Name</label>
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" required>
                                            <div class="invalid-feedback">Please provide the child's name</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="age" class="form-label">Age (Years)</label>
                                            <input type="number" class="form-control" id="age" name="age" min="0" max="18" placeholder="0-18" required>
                                            <div class="invalid-feedback">Please provide a valid age (0-18)</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-select" id="gender" name="gender" required>
                                                <option value="" selected disabled>Select gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <div class="invalid-feedback">Please select a gender</div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="education_level" class="form-label">Education Level</label>
                                        <select class="form-select" id="education_level" name="education_level">
                                            <option value="">Select education level</option>
                                            <option value="None">None</option>
                                            <option value="Preschool">Preschool</option>
                                            <option value="Kindergarten">Kindergarten</option>
                                            <option value="Grade 1">Grade 1</option>
                                            <option value="Grade 2">Grade 2</option>
                                            <option value="Grade 3">Grade 3</option>
                                            <option value="Grade 4">Grade 4</option>
                                            <option value="Grade 5">Grade 5</option>
                                            <option value="Grade 6">Grade 6</option>
                                            <option value="Grade 7">Grade 7</option>
                                            <option value="Grade 8">Grade 8</option>
                                            <option value="Grade 9">Grade 9</option>
                                            <option value="Grade 10">Grade 10</option>
                                            <option value="Grade 11">Grade 11</option>
                                            <option value="Grade 12">Grade 12</option>
                                        </select>
                                        <div class="form-text">Select the child's current level of education</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="health_status" class="form-label">Health Status</label>
                                        <textarea class="form-control" id="health_status" name="health_status" rows="3" placeholder="Describe any health conditions, medications, allergies, etc."></textarea>
                                    </div>
                                </div>

                                <!-- Additional Information Section -->
                                <div class="form-section mb-4">
                                    <div class="form-section-title">
                                        <i class="fas fa-info-circle fa-lg"></i>
                                        <h5 class="mb-0">Additional Information</h5>
                                    </div>

                                    <div class="mb-3">
                                        <label for="found_location" class="form-label">Where was the child found?</label>
                                        <input type="text" class="form-control" id="found_location" name="found_location" placeholder="Location details" required>
                                        <div class="invalid-feedback">Please provide information about where the child was found</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="additional_info" class="form-label">Additional Information</label>
                                        <textarea class="form-control" id="additional_info" name="additional_info" rows="4" placeholder="Please provide any additional details that might help us understand the child's situation better"></textarea>
                                    </div>
                                </div>

                                <!-- Photo Upload Section -->
                                <div class="form-section mb-4">
                                    <div class="form-section-title">
                                        <i class="fas fa-camera fa-lg"></i>
                                        <h5 class="mb-0">Photo Upload</h5>
                                    </div>

                                    <div class="mb-3">
                                        <label for="photo" class="form-label">Child's Photo (Optional)</label>
                                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                        <div class="form-text">Maximum file size: 5MB. Allowed formats: JPG, PNG, GIF.</div>
                                    </div>
                                </div>

                                <!-- Declaration & Submit Section -->
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="declaration" required>
                                            <label class="form-check-label" for="declaration">
                                                I declare that all information provided is true to the best of my knowledge. I understand that providing false information may have legal consequences.
                                            </label>
                                            <div class="invalid-feedback">You must agree before submitting</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-green btn-lg flex-fill">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Registration
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary flex-fill">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Importance Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h3 class="text-success">Why Your Registration Matters</h3>
            <p class="text-muted">Every child registration contributes to our mission of providing care and support</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm feature-card">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-shield-alt fa-3x mb-3"></i>
                        <h5>Child Protection</h5>
                        <p class="mb-0">Every child registered with us receives immediate protection and care in a safe environment.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm feature-card">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                        <h5>Educational Support</h5>
                        <p class="mb-0">We ensure each child continues their education based on their current level and abilities.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm feature-card">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-hands-helping fa-3x mb-3"></i>
                        <h5>Adoption Opportunities</h5>
                        <p class="mb-0">Our adoption program helps connect children with loving families when appropriate.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer py-5 mt-auto">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="footer-info">
                    <h3><i class="fas fa-child me-2"></i> Orphanage Management</h3>
                    <p>Making a difference in children's lives through care, education, and finding loving homes.</p>
                    <div class="social-links mt-3">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="../index.php"><i class="fas fa-angle-right me-1"></i> Home</a></li>
                    <li><a href="../about.php"><i class="fas fa-angle-right me-1"></i> About Us</a></li>
                    <li><a href="../children.php"><i class="fas fa-angle-right me-1"></i> Our Children</a></li>
                    <li><a href="../contact.php"><i class="fas fa-angle-right me-1"></i> Contact</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Our Services</h5>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-angle-right me-1"></i> Child Care</a></li>
                    <li><a href="#"><i class="fas fa-angle-right me-1"></i> Education Support</a></li>
                    <li><a href="#"><i class="fas fa-angle-right me-1"></i> Adoption Services</a></li>
                    <li><a href="#"><i class="fas fa-angle-right me-1"></i> Volunteer Program</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Contact Us</h5>
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt me-2"></i> 123 Orphanage Road, City</p>
                    <p><i class="fas fa-phone me-2"></i> +1 234 567 8900</p>
                    <p><i class="fas fa-envelope me-2"></i> info@orphanage.org</p>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom text-center py-3 mt-4">
        <div class="container">
            <p class="mb-0">&copy; 2025 Orphanage Management System | All Rights Reserved</p>
            <small class="mt-2 text-muted">Last updated: 2025-08-24 12:33:57 | User: joealjohn</small>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="../assets/js/script.js"></script>
<script>
    // Enable form validation
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.needs-validation');

        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Image preview
        const photoInput = document.getElementById('photo');
        if (photoInput) {
            photoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const fileSize = file.size / 1024 / 1024; // in MB
                    if (fileSize > 5) {
                        alert('File size exceeds 5MB. Please select a smaller file.');
                        this.value = '';
                    }
                }
            });
        }
    });
</script>
</body>
</html>