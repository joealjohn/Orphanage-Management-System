<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Adoption Request Details";
$current_page = "dashboard";

// Include necessary files
include '../includes/session.php';
include '../includes/db.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

// Check if request ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$request_id = (int)$_GET['id'];

// Get adoption request details - ensure user can only view their own requests
$query = "SELECT ar.*, c.name as child_name, c.age, c.gender, c.photo, c.education_level, c.health_status, c.admission_date 
          FROM adoption_requests ar 
          JOIN children c ON ar.child_id = c.child_id 
          WHERE ar.request_id = $request_id AND ar.user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $error = "Request not found or you don't have permission to view it.";
} else {
    $request = mysqli_fetch_assoc($result);
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

        /* Status card */
        .status-card {
            border-left: 4px solid;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .status-card.pending {
            border-left-color: #ffc107;
        }

        .status-card.approved {
            border-left-color: #28a745;
        }

        .status-card.rejected {
            border-left-color: #dc3545;
        }

        .status-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .status-icon.pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .status-icon.approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-icon.rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        /* Child info styling */
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

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
            border-radius: 8px;
        }

        /* Status badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
        }

        .status-badge.pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .status-badge.approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-badge.rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .status-badge i {
            margin-right: 0.5rem;
        }

        /* Request details */
        dt {
            font-weight: 600;
            color: #495057;
        }

        dd {
            margin-bottom: 1rem;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-left: 1rem;
            border-left: 2px solid #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-point {
            position: absolute;
            left: -2.65rem;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .timeline-point.bg-success {
            background-color: #28a745;
        }

        .timeline-point.bg-warning {
            background-color: #ffc107;
        }

        .timeline-point.bg-danger {
            background-color: #dc3545;
        }

        .timeline-content {
            background-color: white;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .timeline-date {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        /* Help section */
        .help-card {
            border-left: 4px solid #28a745;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .contact-item:last-child {
            margin-bottom: 0;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
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
                    <a class="nav-link active" href="dashboard.php">
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
        <h1><i class="fas fa-file-alt me-2"></i>Adoption Request Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Request Details</li>
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
                    <a href="dashboard.php" class="btn btn-green">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Request Status Card -->
            <div class="card mb-4 status-card <?php echo $request['status']; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="status-icon <?php echo $request['status']; ?>">
                            <?php if ($request['status'] == 'pending'): ?>
                                <i class="fas fa-clock"></i>
                            <?php elseif ($request['status'] == 'approved'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="mb-1">
                                Request Status:
                                <?php if ($request['status'] == 'pending'): ?>
                                    <span class="status-badge pending">
                                        <i class="fas fa-clock"></i> Under Review
                                    </span>
                                <?php elseif ($request['status'] == 'approved'): ?>
                                    <span class="status-badge approved">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge rejected">
                                        <i class="fas fa-times-circle"></i> Not Approved
                                    </span>
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0">
                                Adoption request for <strong><?php echo htmlspecialchars($request['child_name']); ?></strong>
                                submitted on <?php echo date('F j, Y', strtotime($request['request_date'])); ?> at
                                <?php echo date('g:i A', strtotime($request['request_date'])); ?>
                            </p>
                        </div>
                        <div class="ms-auto">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column: Child Information -->
                <div class="col-md-4 mb-4">
                    <div class="card child-info-card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-child me-2"></i>Child Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="child-photo-container">
                                <?php if (!empty($request['photo']) && file_exists("../" . $request['photo'])): ?>
                                    <img src="<?php echo "../" . $request['photo']; ?>" class="child-photo" alt="<?php echo $request['child_name']; ?>">
                                <?php else: ?>
                                    <img src="../assets/img/child-placeholder.jpg" class="child-photo" alt="<?php echo $request['child_name']; ?>">
                                <?php endif; ?>
                                <h4 class="child-name"><?php echo htmlspecialchars($request['child_name']); ?></h4>
                            </div>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-birthday-cake me-2 text-success"></i> Age:</span>
                                    <span class="fw-bold"><?php echo $request['age']; ?> years</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-venus-mars me-2 text-success"></i> Gender:</span>
                                    <span class="fw-bold"><?php echo ucfirst($request['gender']); ?></span>
                                </li>
                                <?php if (!empty($request['education_level'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-book me-2 text-success"></i> Education:</span>
                                        <span class="fw-bold"><?php echo $request['education_level']; ?></span>
                                    </li>
                                <?php endif; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-calendar me-2 text-success"></i> Admitted:</span>
                                    <span class="fw-bold"><?php echo date('M d, Y', strtotime($request['admission_date'])); ?></span>
                                </li>
                            </ul>

                            <?php if (!empty($request['health_status'])): ?>
                                <div class="mt-4">
                                    <h6 class="text-success"><i class="fas fa-heartbeat me-2"></i>Health Status:</h6>
                                    <p class="mb-0"><?php echo $request['health_status']; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Adoption Request Details -->
                <div class="col-md-8">
                    <!-- Adoption Request Details -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Your Application Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-success mb-3"><i class="fas fa-comment me-2"></i>Reason for Adoption</h6>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="text-success mb-2"><i class="fas fa-wallet me-2"></i>Financial Status</h6>
                                    <p><?php echo htmlspecialchars($request['financial_status']); ?></p>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-success mb-2"><i class="fas fa-phone me-2"></i>Contact Number</h6>
                                    <p><?php echo htmlspecialchars($request['contact_number']); ?></p>
                                </div>

                                <div class="col-md-12">
                                    <h6 class="text-success mb-2"><i class="fas fa-home me-2"></i>Living Situation</h6>
                                    <p><?php echo nl2br(htmlspecialchars($request['living_situation'])); ?></p>
                                </div>

                                <div class="col-md-12">
                                    <h6 class="text-success mb-2"><i class="fas fa-map-marker-alt me-2"></i>Address</h6>
                                    <p><?php echo nl2br(htmlspecialchars($request['address'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Information -->
                    <?php if ($request['status'] == 'approved'): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Congratulations!</h5>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title text-success">Your Adoption Request Has Been Approved</h5>
                                <p>Our team will contact you soon to discuss the next steps in the adoption process. Please ensure your contact information is up-to-date.</p>

                                <div class="timeline mt-4">
                                    <div class="timeline-item">
                                        <div class="timeline-point bg-success">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="timeline-date">Within 1 week</div>
                                            <h6>Detailed Interview</h6>
                                            <p class="mb-0">An adoption specialist will schedule a detailed interview with you to discuss various aspects of adoption.</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-point bg-warning">
                                            <i class="fas fa-home"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="timeline-date">Within 2-3 weeks</div>
                                            <h6>Home Assessment Visit</h6>
                                            <p class="mb-0">Our team will conduct a home visit to ensure the environment is suitable for the child.</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-point bg-primary">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="timeline-date">Within 4-6 weeks</div>
                                            <h6>Legal Documentation</h6>
                                            <p class="mb-0">Preparation of all necessary legal documents required for the adoption process.</p>
                                        </div>
                                    </div>

                                    <div class="timeline-item">
                                        <div class="timeline-point bg-success">
                                            <i class="fas fa-handshake"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="timeline-date">Within 8-12 weeks</div>
                                            <h6>Adoption Finalization</h6>
                                            <p class="mb-0">Final steps to complete the adoption process and welcome the child into your home.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($request['status'] == 'rejected'): ?>
                        <div class="card mb-4 border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Request Not Approved</h5>
                            </div>
                            <div class="card-body">
                                <p>We're sorry to inform you that your adoption request has not been approved at this time. This doesn't mean you can't apply again in the future or for another child.</p>

                                <h6 class="mt-3 mb-2">Common reasons for requests not being approved include:</h6>
                                <ul class="mb-4">
                                    <li>Incomplete information provided</li>
                                    <li>Financial or living situation concerns</li>
                                    <li>A better match was found for this specific child</li>
                                    <li>Multiple applications for the same child</li>
                                </ul>

                                <div class="alert alert-warning">
                                    <i class="fas fa-lightbulb me-2"></i> We encourage you to contact our adoption team for specific feedback on your application, which can help with future requests.
                                </div>

                                <div class="mt-4">
                                    <a href="view_children.php" class="btn btn-outline-primary">
                                        <i class="fas fa-search me-2"></i>Browse Other Children
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card mb-4 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Application Under Review</h5>
                            </div>
                            <div class="card-body">
                                <p>Your adoption request is currently under review by our team. The review process typically takes 1-2 weeks. We'll notify you once a decision has been made.</p>

                                <div class="alert alert-info mt-3">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <i class="fas fa-info-circle fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6 class="alert-heading">What happens during the review?</h6>
                                            <p class="mb-0">Our adoption committee carefully evaluates all information provided in your application. This includes financial stability assessment, living conditions evaluation, and determining the best match for the child.</p>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-3">If you have any questions or would like to provide additional information, please contact our adoption team using the details below.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Contact Information -->
                    <div class="card help-card">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="fas fa-headset me-2"></i>Need Help?</h5>
                            <p>If you have any questions about your adoption request or the adoption process, please don't hesitate to contact us:</p>

                            <div class="mt-4">
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Call us at</div>
                                        <div class="fw-bold">+1 234 567 8900</div>
                                    </div>
                                </div>

                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Email us at</div>
                                        <div class="fw-bold">adoptions@orphanage.org</div>
                                    </div>
                                </div>

                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Office hours</div>
                                        <div class="fw-bold">Monday to Friday, 9:00 AM - 5:00 PM</div>
                                    </div>
                                </div>
                            </div>
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
            <small class="mt-2 text-muted">Last updated: 2025-08-24 13:09:46 | User: joealjohn</small>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="../assets/js/script.js"></script>
</body>
</html>