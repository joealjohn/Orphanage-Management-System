<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "User Dashboard";
$current_page = "dashboard";

// Include necessary files
include '../includes/session.php';
include '../includes/db.php';

// Ensure user is logged in and not admin
if (!isLoggedIn() || isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get user's adoption requests
$adoption_query = "SELECT ar.*, c.name as child_name, c.age, c.gender, c.photo 
                   FROM adoption_requests ar 
                   JOIN children c ON ar.child_id = c.child_id 
                   WHERE ar.user_id = $user_id 
                   ORDER BY ar.request_date DESC";
$adoption_result = mysqli_query($conn, $adoption_query);

// Get user's child registrations
$registration_query = "SELECT * FROM child_registrations 
                       WHERE user_id = $user_id 
                       ORDER BY submitted_at DESC";
$registration_result = mysqli_query($conn, $registration_query);

// Count pending adoption requests
$pending_adoptions = 0;
$approved_adoptions = 0;
$rejected_adoptions = 0;
$total_adoptions = 0;

// Reset pointer and count statuses
mysqli_data_seek($adoption_result, 0);
while ($row = mysqli_fetch_assoc($adoption_result)) {
    $total_adoptions++;
    if ($row['status'] == 'pending') {
        $pending_adoptions++;
    } else if ($row['status'] == 'approved') {
        $approved_adoptions++;
    } else if ($row['status'] == 'rejected') {
        $rejected_adoptions++;
    }
}
// Reset pointer again for display
mysqli_data_seek($adoption_result, 0);

// Count child registration statuses
$pending_registrations = 0;
$approved_registrations = 0;
$rejected_registrations = 0;
$total_registrations = 0;

if ($registration_result) {
    while ($row = mysqli_fetch_assoc($registration_result)) {
        $total_registrations++;
        if ($row['status'] == 'pending') {
            $pending_registrations++;
        } else if ($row['status'] == 'approved') {
            $approved_registrations++;
        } else if ($row['status'] == 'rejected') {
            $rejected_registrations++;
        }
    }
    // Reset pointer for display
    mysqli_data_seek($registration_result, 0);
}

// Get the active tab from query string
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'adoptions';

// Custom header with dashboard styles
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

        /* Green theme dashboard header */
        .dashboard-header {
            background: linear-gradient(120deg, #28a745, #20883b);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .user-welcome {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            font-size: 1.8rem;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        /* Simple white cards */
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            height: 100%;
            position: relative;
        }

        .stat-card .count {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #212529;
        }

        .stat-card .title {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        /* Green progress bars */
        .progress {
            height: 8px;
            border-radius: 4px;
            margin-bottom: 0.75rem;
        }

        .progress-bar {
            background-color: #28a745;
        }

        .progress-bar-warning {
            background-color: #ffc107;
        }

        /* Action cards */
        .action-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            text-align: center;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: #212529;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            color: #28a745;
        }

        .action-card .icon {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 1rem;
        }

        .action-card .title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .action-card .description {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Tab styling */
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            padding: 0.75rem 1rem;
            font-weight: 500;
            margin-right: 0.5rem;
        }

        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #28a745;
            color: #28a745;
            background-color: transparent;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #28a745;
        }

        /* Table styling */
        .table-dashboard {
            width: 100%;
            margin-bottom: 0;
        }

        .table-dashboard th {
            font-weight: 600;
            color: #495057;
            border-top: none;
            border-bottom: 1px solid #e9ecef;
            padding: 0.75rem 1rem;
        }

        .table-dashboard td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        .table-dashboard tr:hover {
            background-color: #f8f9fa;
        }

        /* Child entry styling */
        .child-entry {
            display: flex;
            align-items: center;
        }

        .child-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.75rem;
            background-color: #e9ecef;
        }

        /* Status badges */
        .badge-green {
            background-color: #28a745;
            color: white;
        }

        .badge-yellow {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-red {
            background-color: #dc3545;
            color: white;
        }

        /* Section headers */
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header .icon {
            margin-right: 0.5rem;
            color: #28a745;
        }

        .section-header .title {
            font-weight: 600;
            color: #343a40;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
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
        .alert-custom-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            border-radius: 4px;
        }

        .alert-custom-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-radius: 4px;
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
                    <a class="nav-link active" href="user_dashboard.php">
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

<!-- Dashboard Header -->
<header class="dashboard-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="user-welcome">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h2 class="mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                        <p class="mb-0">Manage your adoption requests and child registrations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="view_children.php" class="btn btn-light me-2">
                    <i class="fas fa-search me-1"></i> Find Children
                </a>
                <a href="register_child.php" class="btn btn-outline-light">
                    <i class="fas fa-plus me-1"></i> Register a Child
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Dashboard Content -->
<section class="py-4">
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-custom-success p-3 mb-4">
                <i class="fas fa-check-circle me-2"></i><?php echo $_GET['success']; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-custom-danger p-3 mb-4">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_GET['error']; ?>
            </div>
        <?php endif; ?>

        <!-- Activity Summary -->
        <div class="mb-4">
            <div class="section-header">
                <i class="fas fa-chart-bar icon"></i>
                <span class="title">YOUR ACTIVITY SUMMARY</span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <div class="count"><?php echo $total_adoptions; ?></div>
                    <div class="title">Adoption Requests</div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                    <div class="mt-3">
                        <?php if ($pending_adoptions > 0): ?>
                            <span class="badge bg-warning me-1"><?php echo $pending_adoptions; ?> Pending</span>
                        <?php endif; ?>
                        <?php if ($approved_adoptions > 0): ?>
                            <span class="badge bg-success"><?php echo $approved_adoptions; ?> Approved</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <div class="count"><?php echo $total_registrations; ?></div>
                    <div class="title">Child Registrations</div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                    <div class="mt-3">
                        <?php if ($pending_registrations > 0): ?>
                            <span class="badge bg-warning me-1"><?php echo $pending_registrations; ?> Pending</span>
                        <?php endif; ?>
                        <?php if ($approved_registrations > 0): ?>
                            <span class="badge bg-success"><?php echo $approved_registrations; ?> Approved</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <div class="count"><?php echo $pending_adoptions; ?></div>
                    <div class="title">Pending Requests</div>
                    <?php if ($pending_adoptions > 0): ?>
                        <p class="text-muted mb-0">You have pending adoption requests</p>
                    <?php else: ?>
                        <p class="text-muted mb-0">You have no pending adoption requests</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <div class="count"><?php echo $approved_adoptions; ?></div>
                    <div class="title">Approved Adoptions</div>
                    <?php if ($approved_adoptions > 0): ?>
                        <p class="text-muted mb-0">Congratulations on your approved adoptions!</p>
                    <?php else: ?>
                        <p class="text-muted mb-0">No adoptions have been approved yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-4">
            <div class="section-header">
                <i class="fas fa-bolt icon"></i>
                <span class="title">QUICK ACTIONS</span>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <a href="view_children.php" class="action-card">
                    <div class="icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h5 class="title">Find Children</h5>
                    <p class="description">Browse children available for adoption</p>
                </a>
            </div>

            <div class="col-md-3 mb-4">
                <a href="register_child.php" class="action-card">
                    <div class="icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h5 class="title">Register a Child</h5>
                    <p class="description">Submit information about a child in need</p>
                </a>
            </div>

            <div class="col-md-3 mb-4">
                <a href="#profile" class="action-card" data-bs-toggle="tab" role="tab" aria-controls="profile" aria-selected="false">
                    <div class="icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h5 class="title">Update Profile</h5>
                    <p class="description">Edit your account information</p>
                </a>
            </div>

            <div class="col-md-3 mb-4">
                <a href="../contact.php" class="action-card">
                    <div class="icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h5 class="title">Get Help</h5>
                    <p class="description">Contact support for assistance</p>
                </a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="card shadow-sm">
            <div class="card-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($active_tab == 'adoptions') ? 'active' : ''; ?>" id="adoptions-tab" data-bs-toggle="tab" data-bs-target="#adoptions" type="button" role="tab" aria-controls="adoptions" aria-selected="<?php echo ($active_tab == 'adoptions') ? 'true' : 'false'; ?>">
                            <i class="fas fa-heart me-2"></i>My Adoption Requests
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($active_tab == 'registrations') ? 'active' : ''; ?>" id="registrations-tab" data-bs-toggle="tab" data-bs-target="#registrations" type="button" role="tab" aria-controls="registrations" aria-selected="<?php echo ($active_tab == 'registrations') ? 'true' : 'false'; ?>">
                            <i class="fas fa-user-plus me-2"></i>My Child Registrations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($active_tab == 'profile') ? 'active' : ''; ?>" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="<?php echo ($active_tab == 'profile') ? 'true' : 'false'; ?>">
                            <i class="fas fa-user-cog me-2"></i>My Profile
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-4" id="myTabContent">
                    <!-- Adoptions Tab -->
                    <div class="tab-pane fade <?php echo ($active_tab == 'adoptions') ? 'show active' : ''; ?>" id="adoptions" role="tabpanel" aria-labelledby="adoptions-tab">
                        <h4 class="mb-4">Your Adoption Requests</h4>

                        <?php if (mysqli_num_rows($adoption_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-dashboard">
                                    <thead>
                                    <tr>
                                        <th>Child</th>
                                        <th>Details</th>
                                        <th>Request Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($adoption = mysqli_fetch_assoc($adoption_result)): ?>
                                        <tr>
                                            <td>
                                                <div class="child-entry">
                                                    <?php if (!empty($adoption['photo']) && file_exists("../" . $adoption['photo'])): ?>
                                                        <img src="<?php echo '../' . htmlspecialchars($adoption['photo']); ?>" alt="<?php echo htmlspecialchars($adoption['child_name']); ?>" class="child-photo">
                                                    <?php else: ?>
                                                        <div class="child-photo d-flex align-items-center justify-content-center">
                                                            <i class="fas <?php echo $adoption['gender'] === 'male' ? 'fa-boy' : 'fa-girl'; ?>"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($adoption['child_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $adoption['age']; ?> years old,
                                                <?php echo ucfirst($adoption['gender']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($adoption['request_date'])); ?>
                                                <div class="small text-muted">
                                                    <?php echo date('g:i A', strtotime($adoption['request_date'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($adoption['status'] == 'pending'): ?>
                                                    <span class="badge badge-yellow">Pending</span>
                                                <?php elseif ($adoption['status'] == 'approved'): ?>
                                                    <span class="badge badge-green">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge badge-red">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_request.php?id=<?php echo $adoption['request_id']; ?>" class="btn btn-sm btn-outline-green">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                <h5>You haven't submitted any adoption requests yet</h5>
                                <p class="text-muted mb-4">Start your journey towards adoption by browsing available children</p>
                                <a href="view_children.php" class="btn btn-green">
                                    <i class="fas fa-search me-2"></i> Find Children to Adopt
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Registrations Tab -->
                    <div class="tab-pane fade <?php echo ($active_tab == 'registrations') ? 'show active' : ''; ?>" id="registrations" role="tabpanel" aria-labelledby="registrations-tab">
                        <h4 class="mb-4">Your Child Registration Submissions</h4>

                        <?php if ($registration_result && mysqli_num_rows($registration_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-dashboard">
                                    <thead>
                                    <tr>
                                        <th>Child Name</th>
                                        <th>Age/Gender</th>
                                        <th>Submitted Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($registration = mysqli_fetch_assoc($registration_result)): ?>
                                        <tr>
                                            <td>
                                                <div class="child-entry">
                                                    <div class="child-photo d-flex align-items-center justify-content-center">
                                                        <i class="fas <?php echo $registration['gender'] === 'male' ? 'fa-boy' : 'fa-girl'; ?>"></i>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($registration['name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $registration['age']; ?> years old,
                                                <?php echo ucfirst($registration['gender']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($registration['submitted_at'])); ?>
                                                <div class="small text-muted">
                                                    <?php echo date('g:i A', strtotime($registration['submitted_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($registration['status'] == 'pending'): ?>
                                                    <span class="badge badge-yellow">Pending</span>
                                                <?php elseif ($registration['status'] == 'approved'): ?>
                                                    <span class="badge badge-green">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge badge-red">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_registration.php?id=<?php echo $registration['reg_id']; ?>" class="btn btn-sm btn-outline-green">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                <h5>You haven't registered any children yet</h5>
                                <p class="text-muted mb-4">Help a child in need by submitting their information to our system</p>
                                <a href="register_child.php" class="btn btn-green">
                                    <i class="fas fa-plus me-2"></i> Register a Child
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Profile Tab -->
                    <div class="tab-pane fade <?php echo ($active_tab == 'profile') ? 'show active' : ''; ?>" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <h4 class="mb-4">Your Profile Information</h4>
                                <form id="profile-form" method="POST" action="update_profile.php" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        <div class="invalid-feedback">Please enter your full name</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        <div class="invalid-feedback">Please enter a valid email address</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>">
                                        <div class="form-text">Add your contact number for easier communication</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($user['address']) ? htmlspecialchars($user['address']) : ''; ?></textarea>
                                        <div class="form-text">This information helps in processing adoption requests</div>
                                    </div>

                                    <hr class="my-4">
                                    <h5>Change Password</h5>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                        <div class="form-text">Leave blank if you don't want to change your password</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                    <button type="submit" class="btn btn-green">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
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
            <small class="mt-2 text-muted">Last updated: 2025-08-24 12:29:16 | User: joealjohn</small>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="../assets/js/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle tab state from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            const triggerEl = document.querySelector(`#${tab}-tab`);
            if (triggerEl) {
                bootstrap.Tab.getOrCreateInstance(triggerEl).show();
            }
        }

        // Form validation
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

        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        if (newPassword && confirmPassword) {
            confirmPassword.addEventListener('input', () => {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords don't match");
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        }
    });
</script>
</body>
</html>