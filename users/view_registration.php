<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Registration Details";
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

// Check if registration ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$reg_id = (int)$_GET['id'];

// Get registration details - ensure user can only view their own registrations
$query = "SELECT * FROM child_registrations WHERE reg_id = $reg_id AND user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $error = "Registration not found or you don't have permission to view it.";
} else {
    $registration = mysqli_fetch_assoc($result);
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

        /* Info blocks */
        .info-block {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-block h6 {
            color: #28a745;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .info-block h6 i {
            margin-right: 0.5rem;
        }

        .info-block p:last-child {
            margin-bottom: 0;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-left: 1rem;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
            border-left: 2px solid #e9ecef;
            padding-left: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
            border-left: none;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.55rem;
            top: 0;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: #28a745;
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
        <h1><i class="fas fa-clipboard-list me-2"></i>Registration Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Registration Details</li>
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
            <!-- Registration Status Card -->
            <div class="card mb-4 status-card <?php echo $registration['status']; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="status-icon <?php echo $registration['status']; ?>">
                            <?php if ($registration['status'] == 'pending'): ?>
                                <i class="fas fa-clock"></i>
                            <?php elseif ($registration['status'] == 'approved'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="mb-1">
                                Registration Status:
                                <?php if ($registration['status'] == 'pending'): ?>
                                    <span class="status-badge pending">
                                        <i class="fas fa-clock"></i> Under Review
                                    </span>
                                <?php elseif ($registration['status'] == 'approved'): ?>
                                    <span class="status-badge approved">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge rejected">
                                        <i class="fas fa-times-circle"></i> Rejected
                                    </span>
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0">
                                Registration for <strong><?php echo htmlspecialchars($registration['name']); ?></strong>
                                submitted on <?php echo date('F j, Y', strtotime($registration['submitted_at'])); ?> at
                                <?php echo date('g:i A', strtotime($registration['submitted_at'])); ?>
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
                <!-- Child Information -->
                <div class="col-md-4 mb-4">
                    <div class="card child-info-card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-child me-2"></i>Child Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="child-photo-container">
                                <?php if (!empty($registration['photo']) && file_exists("../" . $registration['photo'])): ?>
                                    <img src="<?php echo "../" . $registration['photo']; ?>" class="child-photo" alt="<?php echo htmlspecialchars($registration['name']); ?>">
                                <?php else: ?>
                                    <img src="../assets/img/child-placeholder.jpg" class="child-photo" alt="No Photo">
                                <?php endif; ?>
                                <h4 class="child-name"><?php echo htmlspecialchars($registration['name']); ?></h4>
                            </div>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-birthday-cake me-2 text-success"></i> Age:</span>
                                    <span class="fw-bold"><?php echo $registration['age']; ?> years</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-venus-mars me-2 text-success"></i> Gender:</span>
                                    <span class="fw-bold"><?php echo ucfirst($registration['gender']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-book me-2 text-success"></i> Education:</span>
                                    <span class="fw-bold"><?php echo $registration['education_level'] ? htmlspecialchars($registration['education_level']) : 'Not specified'; ?></span>
                                </li>
                            </ul>

                            <?php if (!empty($registration['health_status'])): ?>
                                <div class="mt-4">
                                    <h6 class="text-success"><i class="fas fa-heartbeat me-2"></i>Health Status:</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($registration['health_status'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Registration Details -->
                <div class="col-md-8">
                    <!-- Registration Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Registration Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-block">
                                <h6><i class="fas fa-map-marker-alt"></i> Found Location</h6>
                                <p><?php echo nl2br(htmlspecialchars($registration['found_location'])); ?></p>
                            </div>

                            <?php if (!empty($registration['additional_info'])): ?>
                                <div class="info-block">
                                    <h6><i class="fas fa-info-circle"></i> Additional Information</h6>
                                    <p><?php echo nl2br(htmlspecialchars($registration['additional_info'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="info-block">
                                <h6><i class="fas fa-calendar-alt"></i> Registration Timeline</h6>
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <strong>Submitted</strong>
                                        <div class="text-muted"><?php echo date('F j, Y g:i A', strtotime($registration['submitted_at'])); ?></div>
                                    </div>

                                    <?php if ($registration['status'] != 'pending'): ?>
                                        <div class="timeline-item">
                                            <strong>
                                                <?php echo $registration['status'] == 'approved' ? 'Approved' : 'Rejected'; ?>
                                            </strong>
                                            <div class="text-muted">
                                                <?php
                                                if (!empty($registration['processed_at'])) {
                                                    echo date('F j, Y g:i A', strtotime($registration['processed_at']));
                                                } else {
                                                    echo 'Date not recorded';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Information -->
                    <?php if ($registration['status'] == 'approved'): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Registration Approved</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="me-3 text-success">
                                        <i class="fas fa-check-circle fa-3x"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title text-success mb-1">Thank You for Your Compassion</h5>
                                        <p class="mb-0">This child registration has been approved. The child is now under our care and has been added to our system.</p>
                                    </div>
                                </div>

                                <div class="alert alert-success">
                                    <i class="fas fa-info-circle me-2"></i> The child may now be available for adoption through our regular adoption process.
                                </div>

                                <h6 class="mt-4 mb-3">What happens next:</h6>
                                <div class="process-steps">
                                    <div class="process-step">
                                        <div class="step-number">1</div>
                                        <div class="step-text">Assessment</div>
                                    </div>
                                    <div class="process-step">
                                        <div class="step-number">2</div>
                                        <div class="step-text">Care Plan</div>
                                    </div>
                                    <div class="process-step">
                                        <div class="step-number">3</div>
                                        <div class="step-text">Integration</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($registration['status'] == 'rejected'): ?>
                        <div class="card mb-4 border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Registration Not Approved</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="me-3 text-danger">
                                        <i class="fas fa-exclamation-circle fa-3x"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title text-danger mb-1">Registration Could Not Be Processed</h5>
                                        <p class="mb-0">We're sorry to inform you that this registration has been rejected.</p>
                                    </div>
                                </div>

                                <h6 class="mt-3 mb-2">Common reasons for rejection include:</h6>
                                <ul class="mb-4">
                                    <li>Incomplete or inaccurate information provided</li>
                                    <li>Inability to verify the child's situation</li>
                                    <li>The child may be better served by a different organization</li>
                                    <li>Legal complications with the registration</li>
                                </ul>

                                <?php if (!empty($registration['rejection_reason'])): ?>
                                    <div class="alert alert-warning">
                                        <strong><i class="fas fa-comment-alt me-2"></i>Reason for rejection:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($registration['rejection_reason'])); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb me-2"></i> Please contact our child welfare team for specific feedback and to discuss alternative options for helping this child.
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card mb-4 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Registration Under Review</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="me-3 text-warning">
                                        <i class="fas fa-hourglass-half fa-3x"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1">Your Registration is Being Processed</h5>
                                        <p class="mb-0">Our team is currently reviewing this registration. We take child welfare very seriously and need to verify all information provided.</p>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <i class="fas fa-info-circle fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6 class="alert-heading">What happens during the review?</h6>
                                            <p class="mb-0">Our child welfare team reviews all information, may conduct verification of the details provided, and determines the best course of action for the child's welfare.</p>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-3">Our team may contact you for additional information or to arrange an assessment of the child's situation. Thank you for your patience and compassion in helping this child.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Contact Information -->
                    <div class="card help-card">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="fas fa-headset me-2"></i>Need Assistance?</h5>
                            <p>If you have any questions about this registration or need to provide additional information, our child welfare team is here to help:</p>

                            <div class="mt-4">
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Call us at</div>
                                        <div class="fw-bold">+1 234 567 8901</div>
                                    </div>
                                </div>

                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Email us at</div>
                                        <div class="fw-bold">childwelfare@orphanage.org</div>
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
            <small class="mt-2 text-muted">Last updated: 2025-08-24 14:23:22 | User: joealjohn</small>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="../assets/js/script.js"></script>
</body>
</html>