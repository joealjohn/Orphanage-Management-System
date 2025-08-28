<?php
// Check if base_path is set
global $current_page;
if (!isset($base_path)) {
    $base_path = "../";
}

// Check if user is logged in and is admin
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: " . $base_path . "login.php");
    exit();
}

// Make sure the connection is established before executing queries
if (!isset($conn)) {
    include_once($base_path . 'includes/db.php');
}

// Function to safely get count from database
function getCountSafely($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return isset($row['count']) ? $row['count'] : 0;
    }
    return 0;
}

// Function to check if table exists
function tableExistsSafely($conn, $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($check && mysqli_num_rows($check) > 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?> - Orphanage Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <style>
        /* Admin Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .admin-wrapper {
            display: flex;
            flex: 1;
        }
        .admin-sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding-top: 15px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .admin-content {
            flex: 1;
            margin-left: 250px;
            padding-top: 72px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        .admin-sidebar-header {
            padding: 0 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        .admin-sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
        }
        .admin-sidebar-header .logo-icon {
            font-size: 1.8rem;
            margin-right: 10px;
            color: #28a745;
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .admin-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        .admin-sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
            border-left: 3px solid #28a745;
        }
        .admin-sidebar .nav-link.active {
            color: #fff;
            background-color: #28a745;
            border-left: 3px solid #fff;
        }
        .admin-topbar {
            background-color: #fff;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 99;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 72px;
            transition: all 0.3s;
        }
        .admin-topbar .toggle-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #343a40;
            cursor: pointer;
        }
        .admin-topbar .toggle-btn:hover {
            color: #28a745;
        }
        .admin-topbar .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .admin-topbar .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .admin-topbar .admin-user .dropdown-toggle::after {
            display: none;
        }
        .admin-stat-card {
            position: relative;
            overflow: hidden;
        }
        .admin-stat-card.border-left-primary {
            border-left: 4px solid #4e73df !important;
        }
        .admin-stat-card.border-left-success {
            border-left: 4px solid #1cc88a !important;
        }
        .admin-stat-card.border-left-info {
            border-left: 4px solid #36b9cc !important;
        }
        .admin-stat-card.border-left-warning {
            border-left: 4px solid #f6c23e !important;
        }
        .text-primary-light {
            color: rgba(78, 115, 223, 0.3);
        }
        .text-success-light {
            color: rgba(28, 200, 138, 0.3);
        }
        .text-info-light {
            color: rgba(54, 185, 204, 0.3);
        }
        .text-warning-light {
            color: rgba(246, 194, 62, 0.3);
        }
        .page-title {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 0.5rem;
        }
        .sidebar-collapsed .admin-sidebar {
            width: 70px;
        }
        .sidebar-collapsed .admin-content, .sidebar-collapsed .admin-topbar {
            margin-left: 70px;
            left: 70px;
        }
        .sidebar-collapsed .admin-sidebar-header h3, .sidebar-collapsed .nav-text {
            display: none;
        }
        .sidebar-collapsed .admin-sidebar-header {
            justify-content: center;
        }
        .sidebar-collapsed .admin-sidebar-header .logo-icon {
            margin-right: 0;
        }
        .sidebar-collapsed .nav-link {
            justify-content: center;
        }
        .sidebar-collapsed .nav-link i {
            margin-right: 0;
        }
        .admin-footer {
            margin-left: 250px;
            padding: 1rem;
            background-color: #fff;
            border-top: 1px solid #e3e6f0;
            text-align: center;
            transition: all 0.3s;
        }
        .sidebar-collapsed .admin-footer {
            margin-left: 70px;
        }
        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            .admin-content, .admin-topbar, .admin-footer {
                margin-left: 0;
                left: 0;
            }
            .sidebar-collapsed .admin-sidebar {
                transform: translateX(0);
            }
            .sidebar-collapsed .admin-content, .sidebar-collapsed .admin-topbar, .sidebar-collapsed .admin-footer {
                margin-left: 0;
                left: 0;
            }
            .sidebar-collapsed .admin-sidebar-header h3, .sidebar-collapsed .nav-text {
                display: inline;
            }
            .sidebar-collapsed .admin-sidebar-header {
                justify-content: flex-start;
            }
            .sidebar-collapsed .admin-sidebar-header .logo-icon {
                margin-right: 10px;
            }
            .sidebar-collapsed .nav-link {
                justify-content: flex-start;
            }
            .sidebar-collapsed .nav-link i {
                margin-right: 10px;
            }
        }
    </style>
</head>
<body>
<!-- Admin Layout -->
<div class="admin-wrapper" id="adminWrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <i class="fas fa-child logo-icon"></i>
            <h3>OMS Admin</h3>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'admin_dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'manage_children') ? 'active' : ''; ?>" href="manage_children.php">
                    <i class="fas fa-child"></i>
                    <span class="nav-text">Manage Children</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'adoption_requests') ? 'active' : ''; ?>" href="adoption_requests.php">
                    <i class="fas fa-heart"></i>
                    <span class="nav-text">Adoption Requests</span>
                    <?php
                    // Count pending adoption requests safely
                    $pending_adoptions = 0;
                    if (isset($conn) && tableExistsSafely($conn, 'adoption_requests')) {
                        $pending_adoptions_query = "SELECT COUNT(*) as count FROM adoption_requests WHERE status = 'pending'";
                        $pending_adoptions = getCountSafely($conn, $pending_adoptions_query);
                    }
                    if ($pending_adoptions > 0):
                        ?>
                        <span class="badge bg-danger ms-auto"><?php echo $pending_adoptions; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'child_registrations') ? 'active' : ''; ?>" href="child_registrations.php">
                    <i class="fas fa-user-plus"></i>
                    <span class="nav-text">Child Registrations</span>
                    <?php
                    // Count pending child registrations safely
                    $pending_registrations = 0;
                    if (isset($conn) && tableExistsSafely($conn, 'child_registrations')) {
                        $pending_registrations_query = "SELECT COUNT(*) as count FROM child_registrations WHERE status = 'pending'";
                        $pending_registrations = getCountSafely($conn, $pending_registrations_query);
                    }
                    if ($pending_registrations > 0):
                        ?>
                        <span class="badge bg-danger ms-auto"><?php echo $pending_registrations; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'manage_users') ? 'active' : ''; ?>" href="manage_users.php">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'view_messages') ? 'active' : ''; ?>" href="view_messages.php">
                    <i class="fas fa-envelope"></i>
                    <span class="nav-text">View Messages</span>
                    <?php
                    // Count unread messages safely
                    $unread_messages = 0;
                    if (isset($conn) && tableExistsSafely($conn, 'contact_messages')) {
                        $unread_messages_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
                        $unread_messages = getCountSafely($conn, $unread_messages_query);
                    }
                    if ($unread_messages > 0):
                        ?>
                        <span class="badge bg-danger ms-auto"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="<?php echo $base_path; ?>">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">View Website</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?php echo $base_path; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-content">
        <!-- Top Bar -->
        <div class="admin-topbar">
            <button id="sidebarToggle" class="toggle-btn">
                <i class="fas fa-bars"></i>
            </button>

            <div class="admin-user">
                <?php if (isset($_SESSION['name'])): ?>
                    <div class="dropdown">
                        <a class="dropdown-toggle d-flex align-items-center text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="me-2 d-none d-md-block">
                                <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                                <div class="small text-muted">Administrator</div>
                            </div>
                            <div class="d-flex align-items-center justify-content-center bg-success text-white rounded-circle" style="width: 40px; height: 40px;">
                                <i class="fas fa-user"></i>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>"><i class="fas fa-home me-2"></i>View Website</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>