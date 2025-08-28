<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Request Adoption";
$current_page = "adoption";

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

// Check if child_id is provided
if (!isset($_GET['child_id']) && !isset($_POST['child_id'])) {
    header("Location: view_children.php");
    exit();
}

$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (int)$_POST['child_id'];

// Check if the child exists and is available
$child_query = "SELECT * FROM children WHERE child_id = $child_id AND status = 'available'";
$child_result = mysqli_query($conn, $child_query);

if (mysqli_num_rows($child_result) == 0) {
    $error = "The child you're looking for is not available for adoption.";
} else {
    $child = mysqli_fetch_assoc($child_result);
}

// Check if user already has a pending request for this child
$check_query = "SELECT * FROM adoption_requests WHERE user_id = $user_id AND child_id = $child_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    $error = "You have already submitted an adoption request for this child.";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $living_situation = mysqli_real_escape_string($conn, $_POST['living_situation']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $financial_status = mysqli_real_escape_string($conn, $_POST['financial_status']);

    // Insert adoption request - Using only the essential columns to minimize errors
    $query = "INSERT INTO adoption_requests (user_id, child_id, reason, living_situation, contact_number, address, financial_status, status, request_date) 
              VALUES ($user_id, $child_id, '$reason', '$living_situation', '$contact_number', '$address', '$financial_status', 'pending', NOW())";

    if (mysqli_query($conn, $query)) {
        $success = "Your adoption request has been submitted successfully! We will review your application and contact you soon.";

        // Update child status to pending
        $update_query = "UPDATE children SET status = 'pending' WHERE child_id = $child_id";
        mysqli_query($conn, $update_query);
    } else {
        $error = "Error: " . mysqli_error($conn);
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
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            font-weight: 600;
        }

        /* Child info card */
        .child-info-card {
            border-top: 3px solid #28a745;
            height: 100%;
        }

        .child-photo-container {
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .child-photo {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .child-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            padding: 1rem;
            margin: 0;
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

        /* List group */
        .list-group-item {
            padding: 1rem 1.25rem;
            border-color: #f0f0f0;
        }

        .list-group-item i {
            width: 24px;
            text-align: center;
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

        /* Process steps */
        .process-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .process-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .process-step::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: #e9ecef;
            top: 25px;
            left: 50%;
            z-index: 1;
        }

        .process-step:last-child::after {
            display: none;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 2;
        }

        .step-text {
            text-align: center;
            font-weight: 500;
            font-size: 0.9rem;
            color: #495057;
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
                    <a class="nav-link" href="user_dashboard.php">
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
        <h1><i class="fas fa-heart me-2"></i>Request Adoption</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="user_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_children.php">Find Children</a></li>
                <li class="breadcrumb-item active" aria-current="page">Request Adoption</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="mb-1">Error</h5>
                        <div><?php echo $error; ?></div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="view_children.php" class="btn btn-green">
                        <i class="fas fa-arrow-left me-2"></i>Back to Children List
                    </a>
                </div>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="mb-1">Success!</h5>
                        <div><?php echo $success; ?></div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="user_dashboard.php" class="btn btn-green me-2">
                        <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                    </a>
                    <a href="view_children.php" class="btn btn-outline-green">
                        <i class="fas fa-search me-2"></i>Find More Children
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <h4>Adoption Process</h4>
                <div class="process-steps">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <div class="step-text">Submit Request</div>
                    </div>
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <div class="step-text">Application Review</div>
                    </div>
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <div class="step-text">Home Assessment</div>
                    </div>
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <div class="step-text">Final Approval</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Child Information -->
                <div class="col-lg-4 mb-4">
                    <div class="card child-info-card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-child me-2"></i>Child Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="child-photo-container">
                                <?php if (!empty($child['photo']) && file_exists("../" . $child['photo'])): ?>
                                    <img src="<?php echo "../" . $child['photo']; ?>" class="child-photo" alt="<?php echo $child['name']; ?>">
                                <?php else: ?>
                                    <img src="../assets/img/child-placeholder.jpg" class="child-photo" alt="<?php echo $child['name']; ?>">
                                <?php endif; ?>
                                <h4 class="child-name"><?php echo $child['name']; ?></h4>
                            </div>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-birthday-cake me-2 text-success"></i> Age:</span>
                                    <span class="fw-bold"><?php echo $child['age']; ?> years</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-venus-mars me-2 text-success"></i> Gender:</span>
                                    <span class="fw-bold"><?php echo ucfirst($child['gender']); ?></span>
                                </li>
                                <?php if (!empty($child['education_level'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-book me-2 text-success"></i> Education:</span>
                                        <span class="fw-bold"><?php echo $child['education_level']; ?></span>
                                    </li>
                                <?php endif; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-calendar me-2 text-success"></i> Admitted:</span>
                                    <span class="fw-bold"><?php echo date('M d, Y', strtotime($child['admission_date'])); ?></span>
                                </li>
                            </ul>

                            <?php if (!empty($child['health_status'])): ?>
                                <div class="mt-4">
                                    <h6 class="text-success"><i class="fas fa-heartbeat me-2"></i>Health Status:</h6>
                                    <p class="mb-0"><?php echo $child['health_status']; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Adoption Request Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white d-flex align-items-center">
                            <i class="fas fa-file-alt me-2"></i>
                            <h5 class="mb-0">Adoption Request Form</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-info mb-4">
                                <div class="d-flex">
                                    <i class="fas fa-info-circle fa-lg me-3 mt-1"></i>
                                    <p class="mb-0">Please complete all fields accurately. The information you provide will be used to evaluate your adoption request. All personal information will be kept confidential.</p>
                                </div>
                            </div>

                            <form method="POST" action="request_adoption.php" class="needs-validation" novalidate>
                                <input type="hidden" name="child_id" value="<?php echo $child_id; ?>">

                                <div class="mb-4">
                                    <label for="reason" class="form-label">Why do you want to adopt this child?</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Please explain your motivation and why you believe you can provide a suitable home for this child." required></textarea>
                                    <div class="invalid-feedback">Please provide a reason for your adoption request</div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <!-- Using standard column name -->
                                        <label for="financial_status" class="form-label">Financial Status</label>
                                        <select class="form-select" id="financial_status" name="financial_status" required>
                                            <option value="" selected disabled>Select your financial status</option>
                                            <option value="Low Income">Low Income</option>
                                            <option value="Middle Income">Middle Income</option>
                                            <option value="High Income">High Income</option>
                                        </select>
                                        <div class="invalid-feedback">Please select your financial status</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact_number" name="contact_number" placeholder="Your phone number" required>
                                        <div class="invalid-feedback">Please provide a contact number</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="living_situation" class="form-label">Living Situation</label>
                                    <textarea class="form-control" id="living_situation" name="living_situation" rows="3" placeholder="Describe your home environment, family members, available space for the child, etc." required></textarea>
                                    <div class="invalid-feedback">Please describe your living situation</div>
                                </div>

                                <div class="mb-4">
                                    <label for="address" class="form-label">Residential Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" placeholder="Your complete residential address" required></textarea>
                                    <div class="invalid-feedback">Please provide your residential address</div>
                                </div>

                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="agreement" required>
                                            <label class="form-check-label" for="agreement">
                                                I confirm that all information provided is true and accurate. I understand that providing false information may result in rejection of my application.
                                            </label>
                                            <div class="invalid-feedback">You must agree before submitting</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-green btn-lg flex-fill">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Adoption Request
                                    </button>
                                    <a href="view_children.php" class="btn btn-outline-secondary flex-fill">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Children List
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4 border-top border-success border-3">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="fas fa-info-circle me-2"></i>What happens next?</h5>
                            <ul class="mb-0 ps-3">
                                <li class="mb-2">Your application will be reviewed by our adoption committee (1-2 weeks)</li>
                                <li class="mb-2">If approved initially, you'll be invited for an in-person interview</li>
                                <li class="mb-2">A home assessment will be scheduled to evaluate the living environment</li>
                                <li class="mb-2">Final approval will be granted after all requirements are satisfied</li>
                                <li>You'll be notified of all updates via email and phone</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
            <small class="mt-2 text-muted">Last updated: 2025-08-24 12:52:45 | User: joealjohn</small>
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
    });
</script>
</body>
</html>