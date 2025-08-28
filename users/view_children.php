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

// Include header
include '../includes/header.php';
?>

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
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <div class="mt-3">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Registration Status Card -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        Registration Status:
                                        <?php if ($registration['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($registration['status'] == 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="mb-0 text-muted">Submitted on <?php echo date('F j, Y', strtotime($registration['submitted_at'])); ?></p>
                                </div>
                                <a href="dashboard.php?tab=registrations" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Child Information -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-child me-2"></i>Child Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <?php if (!empty($registration['photo']) && file_exists("../" . $registration['photo'])): ?>
                                        <img src="<?php echo "../" . $registration['photo']; ?>" class="img-fluid rounded mb-3" style="max-height: 200px; object-fit: cover;" alt="<?php echo $registration['name']; ?>">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/200x200?text=No+Photo" class="img-fluid rounded mb-3" alt="No Photo">
                                    <?php endif; ?>
                                    <h4><?php echo $registration['name']; ?></h4>
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
                                        <span class="fw-bold"><?php echo $registration['education_level'] ? $registration['education_level'] : 'Not specified'; ?></span>
                                    </li>
                                </ul>

                                <?php if (!empty($registration['health_status'])): ?>
                                    <div class="mt-3">
                                        <h6 class="mb-2"><i class="fas fa-heartbeat me-2 text-success"></i> Health Status:</h6>
                                        <p class="mb-0"><?php echo $registration['health_status']; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Details -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Registration Details</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <?php if (isset($registration['found_location']) && !empty($registration['found_location'])): ?>
                                        <dt class="col-sm-4">Found Location:</dt>
                                        <dd class="col-sm-8"><?php echo $registration['found_location']; ?></dd>
                                    <?php endif; ?>

                                    <?php if (isset($registration['additional_info']) && !empty($registration['additional_info'])): ?>
                                        <dt class="col-sm-4">Additional Information:</dt>
                                        <dd class="col-sm-8"><?php echo $registration['additional_info']; ?></dd>
                                    <?php endif; ?>

                                    <?php if (!isset($registration['found_location']) && !isset($registration['additional_info'])): ?>
                                        <p class="text-muted">No additional details provided.</p>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>

                        <?php if ($registration['status'] == 'approved'): ?>
                            <div class="card border-success mb-4">
                                <div class="card-body">
                                    <h5 class="card-title text-success">
                                        <i class="fas fa-check-circle me-2"></i>Registration Approved
                                    </h5>
                                    <p>This child registration has been approved. The child is now under our care and has been added to our system. Thank you for helping this child find safety and support.</p>
                                    <p>The child may now be available for adoption through our regular adoption process.</p>
                                </div>
                            </div>
                        <?php elseif ($registration['status'] == 'rejected'): ?>
                            <div class="card border-danger mb-4">
                                <div class="card-body">
                                    <h5 class="card-title text-danger">
                                        <i class="fas fa-times-circle me-2"></i>Registration Rejected
                                    </h5>
                                    <p>We're sorry to inform you that this registration has been rejected. This may be due to:</p>
                                    <ul>
                                        <li>Incomplete or inaccurate information</li>
                                        <li>Inability to verify the child's situation</li>
                                        <li>The child may be better served by a different organization</li>
                                        <li>Legal complications with the registration</li>
                                    </ul>
                                    <p>For more information, please contact our child welfare team.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card border-warning mb-4">
                                <div class="card-body">
                                    <h5 class="card-title text-warning">
                                        <i class="fas fa-clock me-2"></i>Registration Under Review
                                    </h5>
                                    <p>Your child registration is currently under review by our team. We take child welfare very seriously and need to verify all information provided.</p>
                                    <p>Our team may contact you for additional information or to arrange an assessment of the child's situation. Thank you for your patience.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-phone me-2 text-success"></i>Need Help?</h5>
                                <p>If you have any questions about this registration or need to provide additional information:</p>
                                <p><strong>Child Welfare Services:</strong> +1 234 567 8901<br>
                                    <strong>Email:</strong> childwelfare@orphanage.org</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php include '../includes/footer.php'; ?>