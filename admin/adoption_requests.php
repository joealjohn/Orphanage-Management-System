<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Adoption Requests";
$current_page = "adoption_requests";

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

// Helper function to check if column exists
function columnExists($conn, $table, $column) {
    if (!tableExists($conn, $table)) {
        return false;
    }
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($check && mysqli_num_rows($check) > 0);
}

// Helper function to add column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition) {
    if (!columnExists($conn, $table, $column)) {
        $query = "ALTER TABLE $table ADD COLUMN $column $definition";
        $result = mysqli_query($conn, $query);
        return $result ? "Added column $column to $table" : "Failed to add column $column: " . mysqli_error($conn);
    }
    return null;
}

// Variables to store messages
$success_msg = "";
$error_msg = "";

// Create adoption_requests table if it doesn't exist
if (!tableExists($conn, 'adoption_requests')) {
    $create_table = "CREATE TABLE adoption_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        child_id INT NOT NULL,
        user_id INT NOT NULL,
        request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reason TEXT,
        admin_notes TEXT,
        processed_by INT,
        processed_date DATETIME
    )";

    if (!mysqli_query($conn, $create_table)) {
        $error_msg = "Error creating adoption_requests table: " . mysqli_error($conn);
    }
} else {
    // Check and add missing columns individually
    addColumnIfNotExists($conn, 'adoption_requests', 'reason', 'TEXT');
    addColumnIfNotExists($conn, 'adoption_requests', 'admin_notes', 'TEXT');
    addColumnIfNotExists($conn, 'adoption_requests', 'processed_by', 'INT');
    addColumnIfNotExists($conn, 'adoption_requests', 'processed_date', 'DATETIME');
}

// Create children table if it doesn't exist
if (!tableExists($conn, 'children')) {
    $create_children_table = "CREATE TABLE children (
        child_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        age INT,
        gender ENUM('male', 'female') NOT NULL,
        education_level VARCHAR(255),
        health_status TEXT,
        status ENUM('available', 'adopted', 'pending') DEFAULT 'available',
        photo VARCHAR(255),
        admission_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    if (!mysqli_query($conn, $create_children_table)) {
        $error_msg = "Error creating children table: " . mysqli_error($conn);
    } else {
        // Add sample child data
        $insert_child = "INSERT INTO children (name, age, gender, status) 
                          VALUES ('Sample Child', 5, 'male', 'available')";
        mysqli_query($conn, $insert_child);
    }
}

// Ensure users table has correct structure
if (tableExists($conn, 'users')) {
    // Add phone column if it doesn't exist
    addColumnIfNotExists($conn, 'users', 'phone', 'VARCHAR(50)');
} else {
    // Create users table
    $create_users = "CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        address TEXT,
        role ENUM('admin', 'user') DEFAULT 'user',
        status ENUM('active', 'blocked') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    if (mysqli_query($conn, $create_users)) {
        // Add a sample admin user
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin = "INSERT INTO users (name, email, password, role) 
                         VALUES ('Admin User', 'admin@example.com', '$hashed_password', 'admin')";
        mysqli_query($conn, $insert_admin);

        // Add a sample regular user
        $hashed_password = password_hash('user123', PASSWORD_DEFAULT);
        $insert_user = "INSERT INTO users (name, email, password, phone) 
                        VALUES ('Regular User', 'user@example.com', '$hashed_password', '555-1234')";
        mysqli_query($conn, $insert_user);
    }
}

// Insert a test request if table is empty
if (tableExists($conn, 'adoption_requests')) {
    $count_query = "SELECT COUNT(*) as count FROM adoption_requests";
    $count_result = safeQuery($conn, $count_query);

    if ($count_result && mysqli_fetch_assoc($count_result)['count'] == 0) {
        // Get a child ID and user ID
        $child_id = 0;
        $user_id = 0;

        if (tableExists($conn, 'children')) {
            $child_query = "SELECT child_id FROM children WHERE status = 'available' LIMIT 1";
            $child_result = safeQuery($conn, $child_query);
            if ($child_result && mysqli_num_rows($child_result) > 0) {
                $child_id = mysqli_fetch_assoc($child_result)['child_id'];
            } else {
                // Create a child if none exists
                $insert_child = "INSERT INTO children (name, age, gender, status) 
                                 VALUES ('Sample Child', 5, 'male', 'available')";
                if (mysqli_query($conn, $insert_child)) {
                    $child_id = mysqli_insert_id($conn);
                }
            }
        }

        if (tableExists($conn, 'users')) {
            $user_query = "SELECT user_id FROM users WHERE role = 'user' LIMIT 1";
            $user_result = safeQuery($conn, $user_query);
            if ($user_result && mysqli_num_rows($user_result) > 0) {
                $user_id = mysqli_fetch_assoc($user_result)['user_id'];
            } else {
                // Create a regular user if none exists
                $hashed_password = password_hash('user123', PASSWORD_DEFAULT);
                $insert_user = "INSERT INTO users (name, email, password, phone, role) 
                                VALUES ('Sample User', 'sample@example.com', '$hashed_password', '555-5678', 'user')";
                if (mysqli_query($conn, $insert_user)) {
                    $user_id = mysqli_insert_id($conn);
                }
            }
        }

        if ($child_id > 0 && $user_id > 0) {
            // Check if reason column exists before inserting
            if (columnExists($conn, 'adoption_requests', 'reason')) {
                $insert_query = "INSERT INTO adoption_requests (child_id, user_id, reason, request_date) 
                               VALUES ($child_id, $user_id, 'This is a sample adoption request to demonstrate the system functionality.', NOW())";
            } else {
                $insert_query = "INSERT INTO adoption_requests (child_id, user_id, request_date) 
                               VALUES ($child_id, $user_id, NOW())";
            }

            mysqli_query($conn, $insert_query);
        }
    }
}

// Handle request processing (approve/reject)
if (isset($_POST['process_request']) && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $admin_notes = isset($_POST['admin_notes']) ? mysqli_real_escape_string($conn, $_POST['admin_notes']) : '';
    $admin_id = $_SESSION['user_id'];

    // Check if all required columns exist
    $admin_notes_exists = columnExists($conn, 'adoption_requests', 'admin_notes');
    $processed_by_exists = columnExists($conn, 'adoption_requests', 'processed_by');
    $processed_date_exists = columnExists($conn, 'adoption_requests', 'processed_date');

    if ($status == 'approved' || $status == 'rejected') {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Build update query based on which columns exist
            $update_parts = ["status = '$status'"];

            if ($admin_notes_exists) {
                $update_parts[] = "admin_notes = '$admin_notes'";
            }

            if ($processed_by_exists) {
                $update_parts[] = "processed_by = $admin_id";
            }

            if ($processed_date_exists) {
                $update_parts[] = "processed_date = NOW()";
            }

            $update_query = "UPDATE adoption_requests SET " . implode(", ", $update_parts) . " WHERE request_id = $request_id";

            if (!mysqli_query($conn, $update_query)) {
                throw new Exception("Error updating request status: " . mysqli_error($conn));
            }

            // If approved, get the child_id and update child status
            if ($status == 'approved') {
                $child_query = "SELECT child_id FROM adoption_requests WHERE request_id = $request_id";
                $child_result = safeQuery($conn, $child_query);

                if ($child_result && mysqli_num_rows($child_result) > 0) {
                    $child_id = mysqli_fetch_assoc($child_result)['child_id'];

                    // Update child status to adopted
                    $update_child_query = "UPDATE children SET status = 'adopted' WHERE child_id = $child_id";
                    if (!mysqli_query($conn, $update_child_query)) {
                        throw new Exception("Error updating child status: " . mysqli_error($conn));
                    }

                    // Reject all other pending requests for this child
                    if ($admin_notes_exists && $processed_by_exists && $processed_date_exists) {
                        $reject_other_requests = "UPDATE adoption_requests 
                                                SET status = 'rejected', 
                                                admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nAutomatically rejected because another adoption request was approved.'),
                                                processed_by = $admin_id,
                                                processed_date = NOW()
                                                WHERE child_id = $child_id AND request_id != $request_id AND status = 'pending'";
                    } else {
                        $reject_other_requests = "UPDATE adoption_requests 
                                                SET status = 'rejected'
                                                WHERE child_id = $child_id AND request_id != $request_id AND status = 'pending'";
                    }

                    mysqli_query($conn, $reject_other_requests);
                }
            }

            // Commit the transaction
            mysqli_commit($conn);

            $status_text = $status == 'approved' ? 'approved' : 'rejected';
            $success_msg = "Adoption request has been $status_text successfully.";

            // Redirect to filtered view
            if (!empty($_POST['status_filter'])) {
                header("Location: adoption_requests.php?status=" . urlencode($_POST['status_filter']));
                exit();
            }

        } catch (Exception $e) {
            // Rollback the transaction on error
            mysqli_rollback($conn);
            $error_msg = $e->getMessage();
        }
    } else {
        $error_msg = "Invalid status provided.";
    }
}

// Check if phone column exists in users table
$phoneColumnExists = columnExists($conn, 'users', 'phone');

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = !empty($search) ? "AND (c.name LIKE '%$search%' OR u.name LIKE '%$search%' OR u.email LIKE '%$search%')" : "";

// Filter by status
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$status_condition = !empty($status_filter) ? "AND ar.status = '$status_filter'" : "";

// Get total records for pagination
$total_records = 0;
if (tableExists($conn, 'adoption_requests') && tableExists($conn, 'children') && tableExists($conn, 'users')) {
    $total_query = "SELECT COUNT(*) as total 
                   FROM adoption_requests ar 
                   JOIN children c ON ar.child_id = c.child_id
                   JOIN users u ON ar.user_id = u.user_id
                   WHERE 1=1 $status_condition $search_condition";

    $total_result = safeQuery($conn, $total_query);
    if ($total_result && mysqli_num_rows($total_result) > 0) {
        $total_records = mysqli_fetch_assoc($total_result)['total'];
    }
} else {
    if (!tableExists($conn, 'adoption_requests')) {
        $error_msg = "Adoption requests table doesn't exist. Please set up the database first.";
    }
}

$total_pages = ceil($total_records / $records_per_page);

// Count requests by status for badge display
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

if (tableExists($conn, 'adoption_requests')) {
    $status_counts_query = "SELECT status, COUNT(*) as count FROM adoption_requests GROUP BY status";
    $status_counts_result = safeQuery($conn, $status_counts_query);
    if ($status_counts_result) {
        while ($row = mysqli_fetch_assoc($status_counts_result)) {
            if ($row['status'] == 'pending') {
                $pending_count = $row['count'];
            } elseif ($row['status'] == 'approved') {
                $approved_count = $row['count'];
            } elseif ($row['status'] == 'rejected') {
                $rejected_count = $row['count'];
            }
        }
    }
}

// Get adoption requests with pagination
$requests = [];
if (tableExists($conn, 'adoption_requests') && tableExists($conn, 'children') && tableExists($conn, 'users')) {
    // Check which columns exist
    $processed_by_exists = columnExists($conn, 'adoption_requests', 'processed_by');
    $admin_notes_exists = columnExists($conn, 'adoption_requests', 'admin_notes');
    $reason_exists = columnExists($conn, 'adoption_requests', 'reason');

    // Build the query based on existing columns
    $query = "SELECT ar.request_id, ar.child_id, ar.user_id, ar.status, ar.request_date";

    // Add optional columns if they exist
    if ($reason_exists) {
        $query .= ", ar.reason";
    } else {
        $query .= ", '' as reason";
    }

    if ($admin_notes_exists) {
        $query .= ", ar.admin_notes";
    } else {
        $query .= ", '' as admin_notes";
    }

    if ($processed_by_exists) {
        $query .= ", ar.processed_by, ar.processed_date";
    } else {
        $query .= ", NULL as processed_by, NULL as processed_date";
    }

    // Add child and user information
    $query .= ", c.name as child_name, c.age as child_age, c.gender as child_gender, c.photo as child_photo,
               u.name as user_name, u.email as user_email";

    // Add phone column conditionally
    if ($phoneColumnExists) {
        $query .= ", u.phone as user_phone";
    } else {
        $query .= ", '' as user_phone";
    }

    // Add join for admin name only if processed_by exists
    if ($processed_by_exists) {
        $query .= ", admin.name as admin_name 
                  FROM adoption_requests ar 
                  JOIN children c ON ar.child_id = c.child_id
                  JOIN users u ON ar.user_id = u.user_id
                  LEFT JOIN users admin ON ar.processed_by = admin.user_id";
    } else {
        $query .= ", '' as admin_name 
                  FROM adoption_requests ar 
                  JOIN children c ON ar.child_id = c.child_id
                  JOIN users u ON ar.user_id = u.user_id";
    }

    $query .= " WHERE 1=1 $status_condition $search_condition
               ORDER BY ar.request_date DESC 
               LIMIT $offset, $records_per_page";

    $result = safeQuery($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $requests[] = $row;
        }
    }
}

// Include admin header
include 'admin_header.php';
?>

    <!-- Begin Page Content -->
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-heart text-danger"></i> Adoption Requests
                </h1>
                <p class="text-muted mt-2">
                    Review and manage adoption applications. Make informed decisions to find loving homes for children.
                </p>
            </div>
            <div class="d-flex align-items-center">
                <?php if ($pending_count > 0): ?>
                    <div class="me-3">
                        <a href="adoption_requests.php?status=pending" class="btn btn-warning btn-sm">
                            <i class="fas fa-clock me-2"></i>
                            <?php echo $pending_count; ?> Pending
                        </a>
                    </div>
                <?php endif; ?>
                <div>
                    <button type="button" class="btn btn-primary" id="createTestRequest">
                        <i class="fas fa-plus me-2"></i>Create Test Request
                    </button>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filters and Search Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-lg-4 mb-3 mb-lg-0">
                        <form action="adoption_requests.php" method="GET" class="d-flex">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" placeholder="Search by child or user name..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary btn-sm" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-lg-6 mb-3 mb-lg-0">
                        <div class="btn-group" role="group">
                            <a href="adoption_requests.php" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-secondary'; ?> btn-sm">
                                All Requests <span class="badge bg-secondary ms-1"><?php echo $total_records; ?></span>
                            </a>
                            <a href="adoption_requests.php?status=pending<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                               class="btn <?php echo ($status_filter == 'pending') ? 'btn-warning' : 'btn-outline-warning'; ?> btn-sm">
                                Pending <span class="badge bg-light text-dark ms-1"><?php echo $pending_count; ?></span>
                            </a>
                            <a href="adoption_requests.php?status=approved<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                               class="btn <?php echo ($status_filter == 'approved') ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                                Approved <span class="badge bg-light text-dark ms-1"><?php echo $approved_count; ?></span>
                            </a>
                            <a href="adoption_requests.php?status=rejected<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                               class="btn <?php echo ($status_filter == 'rejected') ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm">
                                Rejected <span class="badge bg-light text-dark ms-1"><?php echo $rejected_count; ?></span>
                            </a>
                        </div>
                        <?php if (!empty($search)): ?>
                            <a href="<?php echo !empty($status_filter) ? "adoption_requests.php?status=$status_filter" : "adoption_requests.php"; ?>"
                               class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i>Clear Search
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-2 text-end">
                    <span class="badge bg-info text-white p-2">
                        <i class="fas fa-list me-1"></i>
                        <?php echo $total_records; ?> Total Requests
                    </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Cards -->
        <?php if (count($requests) > 0): ?>
            <div class="row">
                <?php foreach ($requests as $index => $request): ?>
                    <div class="col-lg-12 mb-4">
                        <div class="card shadow-sm border-<?php
                        echo $request['status'] == 'pending' ? 'warning' :
                                ($request['status'] == 'approved' ? 'success' : 'danger');
                        ?> h-100">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between
                            <?php echo $request['status'] == 'pending' ? 'bg-warning bg-opacity-25' :
                                    ($request['status'] == 'approved' ? 'bg-success bg-opacity-25' : 'bg-danger bg-opacity-25'); ?>">
                                <h6 class="m-0 fw-bold">
                                    <i class="fas <?php
                                    echo $request['status'] == 'pending' ? 'fa-clock text-warning' :
                                            ($request['status'] == 'approved' ? 'fa-check-circle text-success' : 'fa-times-circle text-danger');
                                    ?> me-2"></i>
                                    Request #<?php echo $request['request_id']; ?> -
                                    <span class="<?php
                                    echo $request['status'] == 'pending' ? 'text-warning' :
                                            ($request['status'] == 'approved' ? 'text-success' : 'text-danger');
                                    ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                                </h6>
                                <div>
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                </span>
                                    <button class="btn btn-link btn-sm p-0 ms-2 toggle-details" data-id="<?php echo $request['request_id']; ?>">
                                        <i class="fas fa-chevron-down fa-lg text-dark"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <!-- Child Info -->
                                    <div class="col-md-4 border-end">
                                        <div class="d-flex align-items-center mb-3">
                                            <?php if (!empty($request['child_photo']) && file_exists("../" . $request['child_photo'])): ?>
                                                <img src="<?php echo '../' . htmlspecialchars($request['child_photo']); ?>" class="rounded-circle me-3" width="64" height="64" style="object-fit: cover;" alt="Child Photo">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 64px; height: 64px;">
                                                    <i class="fas fa-child fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($request['child_name']); ?></h5>
                                                <p class="text-muted mb-0">
                                                    <?php echo $request['child_age']; ?> years | <?php echo ucfirst($request['child_gender']); ?>
                                                </p>
                                                <a href="view_child.php?id=<?php echo $request['child_id']; ?>" class="btn btn-sm btn-outline-primary mt-1">
                                                    <i class="fas fa-info-circle me-1"></i>View Profile
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Applicant Info -->
                                    <div class="col-md-4 border-end">
                                        <h6 class="text-muted mb-2">Applicant Information</h6>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($request['user_name']); ?></h6>
                                                <p class="mb-0 text-muted">
                                                    <i class="fas fa-envelope me-1 small"></i> <?php echo htmlspecialchars($request['user_email']); ?>
                                                </p>
                                                <?php if (!empty($request['user_phone'])): ?>
                                                    <p class="mb-0 text-muted">
                                                        <i class="fas fa-phone me-1 small"></i> <?php echo htmlspecialchars($request['user_phone']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status/Actions -->
                                    <div class="col-md-4">
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <h6 class="text-muted mb-2">Actions Required</h6>
                                            <div class="d-flex justify-content-between">
                                                <button class="btn btn-success btn-sm me-2 approve-btn" data-id="<?php echo $request['request_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($request['child_name']); ?>"
                                                        data-notes="<?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?>">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <button class="btn btn-danger btn-sm me-2 reject-btn" data-id="<?php echo $request['request_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($request['child_name']); ?>"
                                                        data-notes="<?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?>">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                                <button class="btn btn-info btn-sm view-details-btn" data-id="request-details-<?php echo $request['request_id']; ?>">
                                                    <i class="fas fa-eye me-1"></i>Details
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <h6 class="text-muted mb-2">Status Information</h6>
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if (isset($request['processed_by']) && $request['processed_by']): ?>
                                                    <div class="small text-muted me-2">
                                                        <i class="fas fa-user-check me-1"></i> Processed by: <?php echo htmlspecialchars($request['admin_name'] ?? 'Admin'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <button class="btn btn-info btn-sm ms-auto view-details-btn" data-id="request-details-<?php echo $request['request_id']; ?>">
                                                    <i class="fas fa-eye me-1"></i>Details
                                                </button>
                                            </div>
                                            <?php if (!empty($request['admin_notes'])): ?>
                                                <div class="alert alert-light mb-0 p-2 small">
                                                    <i class="fas fa-clipboard-check me-1"></i>
                                                    <strong>Notes:</strong>
                                                    <?php echo nl2br(htmlspecialchars(substr($request['admin_notes'], 0, 100))); ?>
                                                    <?php if (strlen($request['admin_notes']) > 100): ?>
                                                        <span class="text-primary view-details-btn" data-id="request-details-<?php echo $request['request_id']; ?>" style="cursor:pointer;">
                                                        ...read more
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Collapsible Details Section -->
                                <div class="request-details-section" id="request-details-<?php echo $request['request_id']; ?>" style="display: none;">
                                    <hr>
                                    <div class="row">
                                        <!-- Reason for adoption -->
                                        <div class="col-md-8">
                                            <?php if (!empty($request['reason'])): ?>
                                                <div class="card border-0 bg-light mb-3">
                                                    <div class="card-header bg-light border-0">
                                                        <h6 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Reason for Adoption</h6>
                                                    </div>
                                                    <div class="card-body pt-0">
                                                        <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Administrative Details -->
                                        <div class="col-md-4">
                                            <?php if ($request['status'] != 'pending'): ?>
                                                <div class="card border-0 <?php echo $request['status'] == 'approved' ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10">
                                                    <div class="card-header border-0 <?php echo $request['status'] == 'approved' ? 'bg-success' : 'bg-danger'; ?> bg-opacity-25">
                                                        <h6 class="mb-0 <?php echo $request['status'] == 'approved' ? 'text-success' : 'text-danger'; ?>">
                                                            <i class="fas <?php echo $request['status'] == 'approved' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                                                            <?php echo ucfirst($request['status']); ?> Information
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php if (isset($request['processed_by']) && $request['processed_by']): ?>
                                                            <p class="small mb-1">
                                                                <strong>Processed By:</strong> <?php echo htmlspecialchars($request['admin_name'] ?? 'Unknown'); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if (!empty($request['processed_date'])): ?>
                                                            <p class="small mb-2">
                                                                <strong>Processed On:</strong> <?php echo date('F j, Y - g:i A', strtotime($request['processed_date'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if (!empty($request['admin_notes'])): ?>
                                                            <hr class="my-2">
                                                            <p class="small mb-0">
                                                                <strong>Admin Notes:</strong><br>
                                                                <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) .
                                    (!empty($search) ? '&search=' . urlencode($search) : '') .
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
                                    (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                    '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php
                        endfor;

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages .
                                    (!empty($search) ? '&search=' . urlencode($search) : '') .
                                    (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                    '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) .
                                    (!empty($search) ? '&search=' . urlencode($search) : '') .
                                    (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>"
                               aria-disabled="<?php echo ($page >= $total_pages) ? 'true' : 'false'; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <!-- No Results -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-5 text-center">
                    <div class="mb-3">
                        <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                        <h4 class="mb-3">No Adoption Requests Found</h4>
                        <?php if (!empty($search) || !empty($status_filter)): ?>
                            <p class="text-muted mb-4">No results match your search criteria. Try different keywords or filters.</p>
                            <a href="adoption_requests.php" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-1"></i>Clear All Filters
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-4">There are no adoption requests in the system yet.</p>
                            <button class="btn btn-primary" id="createEmptyRequest">
                                <i class="fas fa-plus-circle me-1"></i>Create Test Adoption Request
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="adoption_requests.php">
                    <div class="modal-header bg-success bg-opacity-10">
                        <h5 class="modal-title" id="approveModalLabel">
                            <i class="fas fa-check-circle text-success me-2"></i>Approve Adoption Request
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Approving this request will mark the child as adopted in the system.
                        </div>

                        <p>Are you sure you want to approve the adoption request for <strong id="approveChildName"></strong>?</p>

                        <div class="mb-3">
                            <label for="admin_notes_approve" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes_approve" name="admin_notes" rows="3" placeholder="Add any notes about this approval decision..."></textarea>
                            <div class="form-text">These notes will be stored with the request record.</div>
                        </div>

                        <input type="hidden" name="request_id" id="approve_request_id" value="">
                        <input type="hidden" name="status" value="approved">
                        <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="process_request" value="process" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Approve Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="adoption_requests.php">
                    <div class="modal-header bg-danger bg-opacity-10">
                        <h5 class="modal-title" id="rejectModalLabel">
                            <i class="fas fa-times-circle text-danger me-2"></i>Reject Adoption Request
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Please provide a reason for rejecting this adoption request.
                        </div>

                        <p>Are you sure you want to reject the adoption request for <strong id="rejectChildName"></strong>?</p>

                        <div class="mb-3">
                            <label for="admin_notes_reject" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="admin_notes_reject" name="admin_notes" rows="3" placeholder="Explain why this adoption request is being rejected..." required></textarea>
                            <div class="form-text">This information is important for record-keeping and may be shared with the applicant.</div>
                        </div>

                        <input type="hidden" name="request_id" id="reject_request_id" value="">
                        <input type="hidden" name="status" value="rejected">
                        <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="process_request" value="process" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Reject Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Test Request Modal -->
    <div class="modal fade" id="testRequestModal" tabindex="-1" aria-labelledby="testRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testRequestModalLabel">
                        <i class="fas fa-plus-circle text-primary me-2"></i>Create Test Adoption Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="testRequestForm" method="POST" action="adoption_requests.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="childSelect" class="form-label">Select Child</label>
                            <select class="form-select" id="childSelect" name="child_id" required>
                                <option value="">-- Select Child --</option>
                                <?php
                                if (tableExists($conn, 'children')) {
                                    $children_query = "SELECT child_id, name, age, gender FROM children WHERE status = 'available' ORDER BY name";
                                    $children_result = safeQuery($conn, $children_query);
                                    if ($children_result) {
                                        while ($child = mysqli_fetch_assoc($children_result)) {
                                            echo '<option value="' . $child['child_id'] . '">' .
                                                    htmlspecialchars($child['name']) . ' (' . $child['age'] . ' yrs, ' .
                                                    ucfirst($child['gender']) . ')</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="userSelect" class="form-label">Select Applicant</label>
                            <select class="form-select" id="userSelect" name="user_id" required>
                                <option value="">-- Select User --</option>
                                <?php
                                if (tableExists($conn, 'users')) {
                                    $users_query = "SELECT user_id, name, email FROM users WHERE role = 'user' ORDER BY name";
                                    $users_result = safeQuery($conn, $users_query);
                                    if ($users_result) {
                                        while ($user = mysqli_fetch_assoc($users_result)) {
                                            echo '<option value="' . $user['user_id'] . '">' .
                                                    htmlspecialchars($user['name']) . ' (' .
                                                    htmlspecialchars($user['email']) . ')</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reasonText" class="form-label">Reason for Adoption</label>
                            <textarea class="form-control" id="reasonText" name="reason" rows="3" required>This is a test adoption request created by an administrator for testing purposes.</textarea>
                        </div>
                        <input type="hidden" name="create_test_request" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitTestRequest">
                            <i class="fas fa-plus me-1"></i>Create Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Document ready function
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Collapsible details sections
            document.querySelectorAll('.view-details-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-id');
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        if (targetElement.style.display === 'none') {
                            targetElement.style.display = 'block';
                            this.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide Details';
                        } else {
                            targetElement.style.display = 'none';
                            this.innerHTML = '<i class="fas fa-eye me-1"></i>Details';
                        }
                    }
                });
            });

            // Toggle details with header chevron
            document.querySelectorAll('.toggle-details').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-id');
                    const detailsId = 'request-details-' + requestId;
                    const detailsElement = document.getElementById(detailsId);

                    if (detailsElement) {
                        if (detailsElement.style.display === 'none') {
                            detailsElement.style.display = 'block';
                            this.innerHTML = '<i class="fas fa-chevron-up fa-lg text-dark"></i>';
                        } else {
                            detailsElement.style.display = 'none';
                            this.innerHTML = '<i class="fas fa-chevron-down fa-lg text-dark"></i>';
                        }
                    }
                });
            });

            // Handle hash in URL to open specific request
            if (window.location.hash) {
                const requestId = window.location.hash.substring(1);
                const detailsId = 'request-details-' + requestId;
                const detailsElement = document.getElementById(detailsId);

                if (detailsElement) {
                    detailsElement.style.display = 'block';
                    document.getElementById(requestId)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Auto-refresh after successful action
            if (document.querySelector('.alert-success')) {
                if (!sessionStorage.getItem('justUpdated')) {
                    sessionStorage.setItem('justUpdated', 'true');
                    // Let the success message show for a moment
                    setTimeout(() => {
                        if (window.location.search.includes('status=')) {
                            window.location.reload(true);
                        }
                    }, 2000);
                } else {
                    sessionStorage.removeItem('justUpdated');
                }
            }

            // Approve button event handler
            document.querySelectorAll('.approve-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-id');
                    const childName = this.getAttribute('data-name');
                    const notes = this.getAttribute('data-notes') || '';

                    document.getElementById('approve_request_id').value = requestId;
                    document.getElementById('approveChildName').textContent = childName;
                    document.getElementById('admin_notes_approve').value = notes;

                    const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
                    approveModal.show();
                });
            });

            // Reject button event handler
            document.querySelectorAll('.reject-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-id');
                    const childName = this.getAttribute('data-name');
                    const notes = this.getAttribute('data-notes') || '';

                    document.getElementById('reject_request_id').value = requestId;
                    document.getElementById('rejectChildName').textContent = childName;
                    document.getElementById('admin_notes_reject').value = notes;

                    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
                    rejectModal.show();
                });
            });

            // Create test request button
            document.querySelectorAll('#createTestRequest, #createEmptyRequest').forEach(btn => {
                btn.addEventListener('click', function() {
                    const testModal = new bootstrap.Modal(document.getElementById('testRequestModal'));
                    testModal.show();
                });
            });

            // Validate test request form before submission
            document.getElementById('testRequestForm')?.addEventListener('submit', function(e) {
                const childSelect = document.getElementById('childSelect');
                const userSelect = document.getElementById('userSelect');

                let valid = true;

                if (!childSelect.value) {
                    childSelect.classList.add('is-invalid');
                    valid = false;
                } else {
                    childSelect.classList.remove('is-invalid');
                }

                if (!userSelect.value) {
                    userSelect.classList.add('is-invalid');
                    valid = false;
                } else {
                    userSelect.classList.remove('is-invalid');
                }

                if (!valid) {
                    e.preventDefault();
                    alert('Please select both a child and a user.');
                }
            });
        });
    </script>

<?php
// Create Test Request endpoint to handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_test_request'])) {
    $child_id = isset($_POST['child_id']) ? (int)$_POST['child_id'] : 0;
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : 'Test adoption request';

    if ($child_id && $user_id) {
        // Check if reason column exists
        if (columnExists($conn, 'adoption_requests', 'reason')) {
            $insert_query = "INSERT INTO adoption_requests (child_id, user_id, reason, request_date) 
                            VALUES ($child_id, $user_id, '$reason', NOW())";
        } else {
            $insert_query = "INSERT INTO adoption_requests (child_id, user_id, request_date) 
                            VALUES ($child_id, $user_id, NOW())";
        }

        if (mysqli_query($conn, $insert_query)) {
            header("Location: adoption_requests.php?status=pending");
            exit;
        } else {
            header("Location: adoption_requests.php?error=" . urlencode(mysqli_error($conn)));
            exit;
        }
    } else {
        header("Location: adoption_requests.php?error=Invalid child or user");
        exit;
    }
}
?>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>