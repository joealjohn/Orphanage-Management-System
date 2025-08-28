<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Admin Dashboard";
$current_page = "admin_dashboard";

// Include necessary files
include '../includes/session.php';
include '../includes/db.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: " . $base_path . "login.php");
    exit();
}

// Helper function to safely fetch counts from database
function getCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return isset($row['count']) ? $row['count'] : 0;
    }
    return 0;
}

// Helper function to check if table exists
function tableExists($conn, $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($check && mysqli_num_rows($check) > 0);
}

// Get counts for dashboard statistics
// Children count by status
$children_stats = array(
        'available' => 0,
        'adopted' => 0,
        'pending' => 0
);

// Check if the children table exists before querying
if (tableExists($conn, 'children')) {
    foreach ($children_stats as $status => $count) {
        $children_stats[$status] = getCount($conn, "SELECT COUNT(*) as count FROM children WHERE status = '$status'");
    }
}

// Total children count
$total_children = array_sum($children_stats);

// Adoption requests count by status
$adoption_stats = array(
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
);

// Check if the adoption_requests table exists before querying
if (tableExists($conn, 'adoption_requests')) {
    foreach ($adoption_stats as $status => $count) {
        $adoption_stats[$status] = getCount($conn, "SELECT COUNT(*) as count FROM adoption_requests WHERE status = '$status'");
    }
}

// Total adoption requests
$total_adoptions = array_sum($adoption_stats);

// Child registrations count by status
$registration_stats = array(
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
);

// Check if the child_registrations table exists before querying
if (tableExists($conn, 'child_registrations')) {
    foreach ($registration_stats as $status => $count) {
        $registration_stats[$status] = getCount($conn, "SELECT COUNT(*) as count FROM child_registrations WHERE status = '$status'");
    }
}

// Total child registrations
$total_registrations = array_sum($registration_stats);

// User count by role
$user_stats = array(
        'admin' => 0,
        'user' => 0
);

// Check if the users table exists before querying
if (tableExists($conn, 'users')) {
    foreach ($user_stats as $role => $count) {
        $user_stats[$role] = getCount($conn, "SELECT COUNT(*) as count FROM users WHERE role = '$role'");
    }
}

// Total users
$total_users = array_sum($user_stats);

// Calculate pending approvals total for notification count
$pending_approvals = $adoption_stats['pending'] + $registration_stats['pending'];

// Recent adoption requests (last 5)
$recent_adoptions = array();
if (tableExists($conn, 'adoption_requests') && tableExists($conn, 'users') && tableExists($conn, 'children')) {
    $recent_adoptions_query = "SELECT ar.request_id, ar.request_date, ar.status, u.name as user_name, c.name as child_name,
                            c.gender, c.age
                            FROM adoption_requests ar
                            JOIN users u ON ar.user_id = u.user_id
                            JOIN children c ON ar.child_id = c.child_id
                            ORDER BY ar.request_date DESC LIMIT 5";
    $recent_adoptions_result = mysqli_query($conn, $recent_adoptions_query);

    if ($recent_adoptions_result) {
        while ($row = mysqli_fetch_assoc($recent_adoptions_result)) {
            $recent_adoptions[] = $row;
        }
    }
}

// Recent child registrations (last 5)
$recent_registrations = array();
if (tableExists($conn, 'child_registrations') && tableExists($conn, 'users')) {
    $recent_registrations_query = "SELECT cr.reg_id, cr.name, cr.submitted_at, cr.status, cr.gender, cr.age, u.name as user_name 
                                  FROM child_registrations cr
                                  JOIN users u ON cr.user_id = u.user_id
                                  ORDER BY cr.submitted_at DESC LIMIT 5";
    $recent_registrations_result = mysqli_query($conn, $recent_registrations_query);

    if ($recent_registrations_result) {
        while ($row = mysqli_fetch_assoc($recent_registrations_result)) {
            $recent_registrations[] = $row;
        }
    }
}

// Recent contact messages (last 5)
$recent_messages = array();
$unread_message_count = 0;
if (tableExists($conn, 'contact_messages')) {
    // Get count of unread messages
    $unread_message_count = getCount($conn, "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");

    // Get recent messages
    $recent_messages_query = "SELECT message_id, name, email, subject, message, status, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5";
    $recent_messages_result = mysqli_query($conn, $recent_messages_query);

    if ($recent_messages_result) {
        while ($row = mysqli_fetch_assoc($recent_messages_result)) {
            $recent_messages[] = $row;
        }
    }
}

// Include admin header
include 'admin_header.php';
?>

    <!-- Dashboard Content -->
    <div class="container-fluid py-4">
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-success text-white shadow-lg">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
                                <p class="mb-0">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>! Here's what's happening in your orphanage management system.</p>
                                <?php if ($pending_approvals > 0): ?>
                                    <div class="mt-3">
                                        <a href="#pendingSection" class="btn btn-light btn-sm">
                                            <i class="fas fa-bell me-1"></i>
                                            You have <?php echo $pending_approvals; ?> items waiting for approval
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="d-inline-block p-3 bg-white bg-opacity-10 rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <h5 class="text-white mb-0">Current Time</h5>
                                            <div id="current-time" class="fw-bold">00:00:00</div>
                                        </div>
                                        <div class="display-5">
                                            <i class="far fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-12 mb-4">
                <h5 class="text-uppercase text-muted fw-bold"><i class="fas fa-chart-line me-2"></i>Key Metrics</h5>
            </div>

            <!-- Children Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 bg-primary bg-gradient text-white p-3 rounded">
                                <i class="fas fa-child fa-2x"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="card-title mb-0"><?php echo $total_children; ?></h5>
                                <div class="card-text text-muted small">Children</div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-between">
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-success mb-1"><?php echo $children_stats['available']; ?></h6>
                                <div class="small text-muted">Available</div>
                            </div>
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-info mb-1"><?php echo $children_stats['adopted']; ?></h6>
                                <div class="small text-muted">Adopted</div>
                            </div>
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-warning mb-1"><?php echo $children_stats['pending']; ?></h6>
                                <div class="small text-muted">Pending</div>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-3">
                            <?php
                            $available_percent = $total_children > 0 ? ($children_stats['available'] / $total_children) * 100 : 0;
                            $adopted_percent = $total_children > 0 ? ($children_stats['adopted'] / $total_children) * 100 : 0;
                            $pending_percent = $total_children > 0 ? ($children_stats['pending'] / $total_children) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $available_percent; ?>%" title="Available"></div>
                            <div class="progress-bar bg-info" style="width: <?php echo $adopted_percent; ?>%" title="Adopted"></div>
                            <div class="progress-bar bg-warning" style="width: <?php echo $pending_percent; ?>%" title="Pending"></div>
                        </div>
                        <div class="text-end mt-3">
                            <a href="manage_children.php" class="btn btn-sm btn-outline-primary">
                                Manage <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adoption Requests Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 bg-success bg-gradient text-white p-3 rounded">
                                <i class="fas fa-heart fa-2x"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="card-title mb-0"><?php echo $total_adoptions; ?></h5>
                                <div class="card-text text-muted small">Adoption Requests</div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-between">
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-warning mb-1"><?php echo $adoption_stats['pending']; ?></h6>
                                <div class="small text-muted">Pending</div>
                            </div>
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-success mb-1"><?php echo $adoption_stats['approved']; ?></h6>
                                <div class="small text-muted">Approved</div>
                            </div>
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-danger mb-1"><?php echo $adoption_stats['rejected']; ?></h6>
                                <div class="small text-muted">Rejected</div>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-3">
                            <?php
                            $pending_adopt_percent = $total_adoptions > 0 ? ($adoption_stats['pending'] / $total_adoptions) * 100 : 0;
                            $approved_adopt_percent = $total_adoptions > 0 ? ($adoption_stats['approved'] / $total_adoptions) * 100 : 0;
                            $rejected_adopt_percent = $total_adoptions > 0 ? ($adoption_stats['rejected'] / $total_adoptions) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-warning" style="width: <?php echo $pending_adopt_percent; ?>%" title="Pending"></div>
                            <div class="progress-bar bg-success" style="width: <?php echo $approved_adopt_percent; ?>%" title="Approved"></div>
                            <div class="progress-bar bg-danger" style="width: <?php echo $rejected_adopt_percent; ?>%" title="Rejected"></div>
                        </div>
                        <div class="text-end mt-3">
                            <a href="adoption_requests.php" class="btn btn-sm btn-outline-success">
                                Manage <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Child Registrations Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 bg-info bg-gradient text-white p-3 rounded">
                                <i class="fas fa-user-plus fa-2x"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="card-title mb-0"><?php echo $total_registrations; ?></h5>
                                <div class="card-text text-muted small">Child Registrations</div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-between">
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-warning mb-1"><?php echo $registration_stats['pending']; ?></h6>
                                <div class="small text-muted">Pending</div>
                            </div>
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-success mb-1"><?php echo $registration_stats['approved']; ?></h6>
                                <div class="small text-muted">Approved</div>
                            </div>
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-danger mb-1"><?php echo $registration_stats['rejected']; ?></h6>
                                <div class="small text-muted">Rejected</div>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-3">
                            <?php
                            $pending_reg_percent = $total_registrations > 0 ? ($registration_stats['pending'] / $total_registrations) * 100 : 0;
                            $approved_reg_percent = $total_registrations > 0 ? ($registration_stats['approved'] / $total_registrations) * 100 : 0;
                            $rejected_reg_percent = $total_registrations > 0 ? ($registration_stats['rejected'] / $total_registrations) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-warning" style="width: <?php echo $pending_reg_percent; ?>%" title="Pending"></div>
                            <div class="progress-bar bg-success" style="width: <?php echo $approved_reg_percent; ?>%" title="Approved"></div>
                            <div class="progress-bar bg-danger" style="width: <?php echo $rejected_reg_percent; ?>%" title="Rejected"></div>
                        </div>
                        <div class="text-end mt-3">
                            <a href="child_registrations.php" class="btn btn-sm btn-outline-info">
                                Manage <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 bg-warning bg-gradient text-white p-3 rounded">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="card-title mb-0"><?php echo $total_users; ?></h5>
                                <div class="card-text text-muted small">System Users</div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-around">
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-danger mb-1"><?php echo $user_stats['admin']; ?></h6>
                                <div class="small text-muted">Administrators</div>
                            </div>
                            <div class="text-center">
                                <h6 class="badge rounded-pill bg-secondary mb-1"><?php echo $user_stats['user']; ?></h6>
                                <div class="small text-muted">Regular Users</div>
                            </div>
                        </div>
                        <div class="progress progress-sm mt-3">
                            <?php
                            $admin_percent = $total_users > 0 ? ($user_stats['admin'] / $total_users) * 100 : 0;
                            $user_percent = $total_users > 0 ? ($user_stats['user'] / $total_users) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-danger" style="width: <?php echo $admin_percent; ?>%" title="Admins"></div>
                            <div class="progress-bar bg-secondary" style="width: <?php echo $user_percent; ?>%" title="Regular Users"></div>
                        </div>
                        <div class="text-end mt-3">
                            <a href="manage_users.php" class="btn btn-sm btn-outline-warning">
                                Manage <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-light py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="add_child.php" class="card text-center h-100 py-3 bg-light border-0 text-primary">
                                    <div class="card-body">
                                        <i class="fas fa-plus-circle fa-3x mb-3"></i>
                                        <h5 class="card-title">Add Child</h5>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="add_user.php" class="card text-center h-100 py-3 bg-light border-0 text-success">
                                    <div class="card-body">
                                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                                        <h5 class="card-title">Add User</h5>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="adoption_requests.php?status=pending" class="card text-center h-100 py-3 bg-light border-0 text-warning">
                                    <div class="card-body">
                                        <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                                        <h5 class="card-title">Pending Adoptions</h5>
                                        <?php if ($adoption_stats['pending'] > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?php echo $adoption_stats['pending']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="view_messages.php?status=unread" class="card text-center h-100 py-3 bg-light border-0 text-info">
                                    <div class="card-body">
                                        <i class="fas fa-envelope fa-3x mb-3"></i>
                                        <h5 class="card-title">Unread Messages</h5>
                                        <?php if ($unread_message_count > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?php echo $unread_message_count; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="row mb-4" id="pendingSection">
            <div class="col-12 mb-4">
                <h5 class="text-uppercase text-muted fw-bold"><i class="fas fa-history me-2"></i>Recent Activity</h5>
            </div>

            <!-- Recent Adoption Requests -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-success bg-gradient text-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-heart me-2"></i>Recent Adoption Requests</h6>
                        <a href="adoption_requests.php" class="btn btn-sm btn-light">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($recent_adoptions) > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_adoptions as $adoption): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas <?php echo $adoption['gender'] == 'male' ? 'fa-boy' : 'fa-girl'; ?>"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($adoption['child_name']); ?></h6>
                                                    <div class="small text-muted">
                                                        <?php echo $adoption['age']; ?> yrs | By: <?php echo htmlspecialchars($adoption['user_name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($adoption['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($adoption['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                                <div class="small text-muted"><?php echo date('M d', strtotime($adoption['request_date'])); ?></div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No recent adoption requests.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Child Registrations -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-info bg-gradient text-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-user-plus me-2"></i>Recent Child Registrations</h6>
                        <a href="child_registrations.php" class="btn btn-sm btn-light">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($recent_registrations) > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_registrations as $registration): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas <?php echo $registration['gender'] == 'male' ? 'fa-boy' : 'fa-girl'; ?>"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($registration['name']); ?></h6>
                                                    <div class="small text-muted">
                                                        <?php echo $registration['age']; ?> yrs | By: <?php echo htmlspecialchars($registration['user_name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($registration['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($registration['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                                <div class="small text-muted"><?php echo date('M d', strtotime($registration['submitted_at'])); ?></div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No recent child registrations.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Contact Messages -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-success bg-gradient text-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-envelope me-2"></i>Recent Messages</h6>
                        <a href="view_messages.php" class="btn btn-sm btn-light">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($recent_messages) > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_messages as $message): ?>
                                    <a href="view_message.php?id=<?php echo $message['message_id']; ?>" class="list-group-item list-group-item-action <?php echo ($message['status'] == 'unread') ? 'bg-light' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">
                                                        <?php echo htmlspecialchars($message['name']); ?>
                                                        <?php if ($message['status'] == 'unread'): ?>
                                                            <span class="ms-2 badge bg-success">New</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <div class="small text-muted">
                                                        <?php echo !empty($message['subject']) ? htmlspecialchars($message['subject']) : 'No subject'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="small text-muted"><?php echo date('M d', strtotime($message['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No recent contact messages.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status Card -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-light py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-server me-2"></i>System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card border-left-primary h-100">
                                    <div class="card-body">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">PHP Version</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo phpversion(); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-left-success h-100">
                                    <div class="card-body">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Server</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-left-warning h-100">
                                    <div class="card-body">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">System Date</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo date('Y-m-d'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-left-info h-100">
                                    <div class="card-body">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Last Login</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo date('H:i:s'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('current-time').textContent = timeString;
        }

        // Update time every second
        setInterval(updateTime, 1000);

        // Initialize time on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateTime();

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>

<?php
// Current date/time and user for footer
include 'admin_footer.php';
?>