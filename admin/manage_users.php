<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Manage Users";
$current_page = "manage_users";

// Include necessary files
include '../includes/session.php';
include '../includes/db.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Helper function to safely fetch data from database
function safeQuery($conn, $query) {
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return false;
    }
    return $result;
}

// Helper function to check if table exists
function tableExists($conn, $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($check && mysqli_num_rows($check) > 0);
}

// Handle actions
$success_msg = "";
$error_msg = "";

// Handle block/unblock action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Check if trying to modify admin
    $check_query = "SELECT role FROM users WHERE user_id = $user_id";
    $check_result = safeQuery($conn, $check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $user_data = mysqli_fetch_assoc($check_result);

        // Don't allow blocking other admins
        if ($_GET['action'] == 'block' && $user_data['role'] == 'admin') {
            $error_msg = "Cannot block administrator accounts.";
        } else {
            if ($_GET['action'] == 'block') {
                // Block user
                $update_query = "UPDATE users SET status = 'blocked' WHERE user_id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success_msg = "User blocked successfully.";
                } else {
                    $error_msg = "Error blocking user: " . mysqli_error($conn);
                }
            } else if ($_GET['action'] == 'unblock') {
                // Unblock user
                $update_query = "UPDATE users SET status = 'active' WHERE user_id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success_msg = "User activated successfully.";
                } else {
                    $error_msg = "Error activating user: " . mysqli_error($conn);
                }
            } else if ($_GET['action'] == 'delete') {
                // Check if user is the current admin
                if ($user_id == $_SESSION['user_id']) {
                    $error_msg = "You cannot delete your own account.";
                }
                // Check if user has any adoption requests
                else {
                    $has_adoptions = false;
                    if (tableExists($conn, 'adoption_requests')) {
                        $has_adoptions_query = "SELECT COUNT(*) as count FROM adoption_requests WHERE user_id = $user_id";
                        $has_adoptions_result = safeQuery($conn, $has_adoptions_query);
                        if ($has_adoptions_result && mysqli_num_rows($has_adoptions_result) > 0) {
                            $has_adoptions = mysqli_fetch_assoc($has_adoptions_result)['count'] > 0;
                        }
                    }

                    $has_registrations = false;
                    if (tableExists($conn, 'child_registrations')) {
                        $has_registrations_query = "SELECT COUNT(*) as count FROM child_registrations WHERE user_id = $user_id";
                        $has_registrations_result = safeQuery($conn, $has_registrations_query);
                        if ($has_registrations_result && mysqli_num_rows($has_registrations_result) > 0) {
                            $has_registrations = mysqli_fetch_assoc($has_registrations_result)['count'] > 0;
                        }
                    }

                    if ($has_adoptions || $has_registrations) {
                        $error_msg = "Cannot delete user. There are adoption requests or child registrations associated with this account.";
                    } else {
                        // Delete user
                        $delete_query = "DELETE FROM users WHERE user_id = $user_id";
                        if (mysqli_query($conn, $delete_query)) {
                            $success_msg = "User deleted successfully.";
                        } else {
                            $error_msg = "Error deleting user: " . mysqli_error($conn);
                        }
                    }
                }
            } else if ($_GET['action'] == 'make_admin') {
                // Make user an admin
                $update_query = "UPDATE users SET role = 'admin' WHERE user_id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success_msg = "User promoted to administrator successfully.";
                } else {
                    $error_msg = "Error promoting user: " . mysqli_error($conn);
                }
            } else if ($_GET['action'] == 'remove_admin') {
                // Check if user is the current admin
                if ($user_id == $_SESSION['user_id']) {
                    $error_msg = "You cannot demote yourself.";
                } else {
                    // Remove admin rights
                    $update_query = "UPDATE users SET role = 'user' WHERE user_id = $user_id";
                    if (mysqli_query($conn, $update_query)) {
                        $success_msg = "Administrator demoted to regular user successfully.";
                    } else {
                        $error_msg = "Error demoting administrator: " . mysqli_error($conn);
                    }
                }
            }
        }
    } else {
        $error_msg = "User not found.";
    }
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = !empty($search) ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR role LIKE '%$search%' OR status LIKE '%$search%'" : "";

// Filter by role or status
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

if (!empty($role_filter)) {
    $search_condition = !empty($search_condition) ? $search_condition . " AND role = '$role_filter'" : "WHERE role = '$role_filter'";
}

if (!empty($status_filter)) {
    $search_condition = !empty($search_condition) ? $search_condition . " AND status = '$status_filter'" : "WHERE status = '$status_filter'";
}

// Get total records for pagination
$total_records = 0;
if (tableExists($conn, 'users')) {
    $total_query = "SELECT COUNT(*) as total FROM users $search_condition";
    $total_result = safeQuery($conn, $total_query);
    if ($total_result && mysqli_num_rows($total_result) > 0) {
        $total_records = mysqli_fetch_assoc($total_result)['total'];
    }
}

$total_pages = ceil($total_records / $records_per_page);

// Get counts for roles and statuses
$admin_count = 0;
$user_count = 0;
$active_count = 0;
$blocked_count = 0;

if (tableExists($conn, 'users')) {
    // Count by role
    $role_counts_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
    $role_counts_result = safeQuery($conn, $role_counts_query);
    if ($role_counts_result) {
        while ($row = mysqli_fetch_assoc($role_counts_result)) {
            if ($row['role'] == 'admin') {
                $admin_count = $row['count'];
            } else {
                $user_count = $row['count'];
            }
        }
    }

    // Count by status
    $status_counts_query = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
    $status_counts_result = safeQuery($conn, $status_counts_query);
    if ($status_counts_result) {
        while ($row = mysqli_fetch_assoc($status_counts_result)) {
            if ($row['status'] == 'active') {
                $active_count = $row['count'];
            } else {
                $blocked_count = $row['count'];
            }
        }
    }
}

// Get user records with pagination
$users = [];
if (tableExists($conn, 'users')) {
    $query = "SELECT * FROM users $search_condition ORDER BY user_id DESC LIMIT $offset, $records_per_page";
    $result = safeQuery($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
}

// Include admin header
include 'admin_header.php';
?>

    <!-- Begin Page Content -->
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-users text-primary me-2"></i> Manage Users
                </h1>
                <p class="mb-0 text-muted">Manage user accounts, set permissions, and control access to the system.</p>
            </div>
            <a href="add_user.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-user-plus fa-sm me-2"></i>Add New User
            </a>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- User Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_records; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Active Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Administrators</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $admin_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Blocked Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $blocked_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-lock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Search & Filter Users</h6>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Search Form -->
                    <div class="col-md-5 mb-3 mb-md-0">
                        <form action="manage_users.php" method="GET" class="d-flex">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by name, email..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <?php if (!empty($role_filter)): ?>
                                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                            <?php endif; ?>
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap gap-2">
                            <!-- Role Filter -->
                            <div class="btn-group me-2 mb-2 mb-md-0" role="group">
                                <a href="manage_users.php<?php echo !empty($search) || !empty($status_filter) ?
                                        '?' .
                                        (!empty($search) ? 'search=' . urlencode($search) . '&' : '') .
                                        (!empty($status_filter) ? 'status=' . $status_filter : '') : ''; ?>"
                                   class="btn <?php echo empty($role_filter) ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">
                                    All Roles
                                </a>
                                <a href="manage_users.php?role=admin<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>"
                                   class="btn <?php echo $role_filter == 'admin' ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm">
                                    <i class="fas fa-user-shield me-1"></i> Admins
                                    <span class="badge bg-white text-danger"><?php echo $admin_count; ?></span>
                                </a>
                                <a href="manage_users.php?role=user<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>"
                                   class="btn <?php echo $role_filter == 'user' ? 'btn-secondary' : 'btn-outline-secondary'; ?> btn-sm">
                                    <i class="fas fa-user me-1"></i> Users
                                    <span class="badge bg-white text-secondary"><?php echo $user_count; ?></span>
                                </a>
                            </div>

                            <!-- Status Filter -->
                            <div class="btn-group me-2 mb-2 mb-md-0">
                                <a href="manage_users.php?status=active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . $role_filter : ''; ?>"
                                   class="btn <?php echo $status_filter == 'active' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                                    <i class="fas fa-check-circle me-1"></i> Active
                                    <span class="badge bg-white text-success"><?php echo $active_count; ?></span>
                                </a>
                                <a href="manage_users.php?status=blocked<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . $role_filter : ''; ?>"
                                   class="btn <?php echo $status_filter == 'blocked' ? 'btn-warning' : 'btn-outline-warning'; ?> btn-sm">
                                    <i class="fas fa-ban me-1"></i> Blocked
                                    <span class="badge bg-white text-warning"><?php echo $blocked_count; ?></span>
                                </a>
                            </div>

                            <!-- Clear Filters Button -->
                            <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                                <a href="manage_users.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times-circle me-1"></i>Clear All
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-user-friends me-2"></i>User Accounts
                </h6>
                <span class="badge bg-primary"><?php echo $total_records; ?> Users</span>
            </div>
            <div class="card-body p-0">
                <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3" width="5%">ID</th>
                                <th scope="col" width="25%">User</th>
                                <th scope="col" width="25%">Contact</th>
                                <th scope="col" width="12%">Role</th>
                                <th scope="col" width="13%">Status</th>
                                <th scope="col" width="10%">Joined</th>
                                <th scope="col" class="text-center" width="10%">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="align-middle">
                                    <td class="ps-3"><?php echo $user['user_id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-container me-3">
                                                <?php if ($user['role'] == 'admin'): ?>
                                                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fas fa-user-shield fa-lg"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fas fa-user fa-lg"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($user['name']); ?>
                                                    <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-primary ms-2">You</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <?php if (!empty($user['address'])): ?>
                                                    <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($user['address']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div><i class="fas fa-envelope me-1 text-muted"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php if (!empty($user['phone'])): ?>
                                                <div class="small text-muted"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge bg-danger py-2 px-3">
                                                <i class="fas fa-user-shield me-1"></i>Administrator
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary py-2 px-3">
                                                <i class="fas fa-user me-1"></i>Regular User
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] == 'active'): ?>
                                            <span class="badge bg-success py-2 px-3">
                                                <i class="fas fa-check-circle me-1"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark py-2 px-3">
                                                <i class="fas fa-ban me-1"></i>Blocked
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($user['created_at']) && !empty($user['created_at'])): ?>
                                            <span data-bs-toggle="tooltip" title="<?php echo date('F j, Y - g:i A', strtotime($user['created_at'])); ?>">
                                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end pe-3">
                                            <div class="dropdown">
                                                <button class="btn btn-sm <?php echo $user['user_id'] == $_SESSION['user_id'] ? 'btn-primary' : 'btn-outline-primary'; ?> dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $user['user_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-cog"></i> Manage
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuButton<?php echo $user['user_id']; ?>">
                                                    <!-- Role Management -->
                                                    <?php if ($user['role'] == 'admin'): ?>
                                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                            <li><a class="dropdown-item text-primary" href="#" onclick="confirmAction('<?php echo htmlspecialchars($user['name']); ?>', 'remove_admin', <?php echo $user['user_id']; ?>);return false;">
                                                                    <i class="fas fa-user-minus me-2"></i>Remove Admin Rights
                                                                </a></li>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmAction('<?php echo htmlspecialchars($user['name']); ?>', 'make_admin', <?php echo $user['user_id']; ?>);return false;">
                                                                <i class="fas fa-user-shield me-2"></i>Make Administrator
                                                            </a></li>
                                                    <?php endif; ?>

                                                    <li><hr class="dropdown-divider"></li>

                                                    <!-- Status Management -->
                                                    <?php if ($user['status'] == 'active'): ?>
                                                        <li><a class="dropdown-item text-warning" href="#" onclick="confirmAction('<?php echo htmlspecialchars($user['name']); ?>', 'block', <?php echo $user['user_id']; ?>);return false;">
                                                                <i class="fas fa-ban me-2"></i>Block User
                                                            </a></li>
                                                    <?php else: ?>
                                                        <li><a class="dropdown-item text-success" href="#" onclick="confirmAction('<?php echo htmlspecialchars($user['name']); ?>', 'unblock', <?php echo $user['user_id']; ?>);return false;">
                                                                <i class="fas fa-check-circle me-2"></i>Activate User
                                                            </a></li>
                                                    <?php endif; ?>

                                                    <!-- Delete Option (not for self) -->
                                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmAction('<?php echo htmlspecialchars($user['name']); ?>', 'delete', <?php echo $user['user_id']; ?>);return false;">
                                                                <i class="fas fa-trash-alt me-2"></i>Delete User
                                                            </a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- No Records Found -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <img src="../assets/img/empty_users.svg" alt="No Users" style="max-width: 200px; opacity: 0.6;">
                        </div>
                        <h5 class="text-gray-700 mb-3">No Users Found</h5>
                        <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                            <p class="text-muted mb-4">No results match your search criteria. Try different keywords or filters.</p>
                            <a href="manage_users.php" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-1"></i>Clear Search
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-4">There are no users in the system yet. Get started by adding a new user account.</p>
                            <a href="add_user.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i>Add First User
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white py-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($role_filter) ? '&role=' . $role_filter : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>"
                                   aria-disabled="<?php echo ($page <= 1) ? 'true' : 'false'; ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>

                            <?php
                            // Show limited page numbers with ellipsis
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1' .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($role_filter) ? '&role=' . $role_filter : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                        '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i .
                                            (!empty($search) ? '&search=' . urlencode($search) : '') .
                                            (!empty($role_filter) ? '&role=' . $role_filter : '') .
                                            (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor;

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($role_filter) ? '&role=' . $role_filter : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                        '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($role_filter) ? '&role=' . $role_filter : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>"
                                   aria-disabled="<?php echo ($page >= $total_pages) ? 'true' : 'false'; ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Confirmation Modals -->
    <div class="modal fade" id="confirmAdminModal" tabindex="-1" aria-labelledby="confirmAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmAdminModalLabel">
                        <i class="fas fa-user-shield me-2"></i>Make Administrator
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to promote <strong id="makeAdminName" class="text-danger"></strong> to an administrator?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Administrators have full access to all features of the system including user management and sensitive data.
                    </div>
                    <p>Only promote trusted users who need administrative capabilities.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-danger" id="makeAdminBtn">
                        <i class="fas fa-user-shield me-1"></i>Make Administrator
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmRemoveAdminModal" tabindex="-1" aria-labelledby="confirmRemoveAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="confirmRemoveAdminModalLabel">
                        <i class="fas fa-user-minus me-2"></i>Remove Administrator Rights
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove administrator privileges from <strong id="removeAdminName" class="text-primary"></strong>?</p>
                    <p>This user will no longer have access to the admin panel or administrative functions.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-primary" id="removeAdminBtn">
                        <i class="fas fa-user-minus me-1"></i>Remove Admin Rights
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmBlockModal" tabindex="-1" aria-labelledby="confirmBlockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="confirmBlockModalLabel">
                        <i class="fas fa-ban me-2"></i>Block User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to block <strong id="blockUserName" class="text-warning"></strong>?</p>
                    <p>This user will no longer be able to log in to the system or access any features.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-warning" id="blockUserBtn">
                        <i class="fas fa-ban me-1"></i>Block User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmUnblockModal" tabindex="-1" aria-labelledby="confirmUnblockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="confirmUnblockModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Activate User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to activate <strong id="unblockUserName" class="text-success"></strong>?</p>
                    <p>This user will be able to log in to the system and access their account again.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-success" id="unblockUserBtn">
                        <i class="fas fa-check-circle me-1"></i>Activate User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteUserName" class="text-danger"></strong>?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. All data associated with this user will be permanently removed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-danger" id="deleteUserBtn">
                        <i class="fas fa-trash-alt me-1"></i>Delete User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Action confirmation handler
        function confirmAction(name, action, userId) {
            switch(action) {
                case 'make_admin':
                    document.getElementById('makeAdminName').textContent = name;
                    document.getElementById('makeAdminBtn').href = `manage_users.php?action=make_admin&id=${userId}`;
                    new bootstrap.Modal(document.getElementById('confirmAdminModal')).show();
                    break;

                case 'remove_admin':
                    document.getElementById('removeAdminName').textContent = name;
                    document.getElementById('removeAdminBtn').href = `manage_users.php?action=remove_admin&id=${userId}`;
                    new bootstrap.Modal(document.getElementById('confirmRemoveAdminModal')).show();
                    break;

                case 'block':
                    document.getElementById('blockUserName').textContent = name;
                    document.getElementById('blockUserBtn').href = `manage_users.php?action=block&id=${userId}`;
                    new bootstrap.Modal(document.getElementById('confirmBlockModal')).show();
                    break;

                case 'unblock':
                    document.getElementById('unblockUserName').textContent = name;
                    document.getElementById('unblockUserBtn').href = `manage_users.php?action=unblock&id=${userId}`;
                    new bootstrap.Modal(document.getElementById('confirmUnblockModal')).show();
                    break;

                case 'delete':
                    document.getElementById('deleteUserName').textContent = name;
                    document.getElementById('deleteUserBtn').href = `manage_users.php?action=delete&id=${userId}`;
                    new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
                    break;
            }
        }
    </script>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>