<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Child Registrations";
$current_page = "child_registrations";

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

// Check if child_registrations table exists, create it if not
if (!tableExists($conn, 'child_registrations')) {
    $create_table_query = "CREATE TABLE child_registrations (
        reg_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        age INT,
        gender ENUM('male', 'female') NOT NULL,
        education_level VARCHAR(255),
        health_status TEXT,
        found_location TEXT,
        additional_info TEXT,
        photo VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_notes TEXT,
        user_id INT NOT NULL,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    if (!mysqli_query($conn, $create_table_query)) {
        $error_msg = "Error creating child registrations table: " . mysqli_error($conn);
    }
} else {
    // Check and add missing columns if needed
    if (!columnExists($conn, 'child_registrations', 'admin_notes')) {
        $add_result = addColumnIfNotExists($conn, 'child_registrations', 'admin_notes', 'TEXT');
        if (strpos($add_result, 'Failed') === 0) {
            $error_msg = $add_result;
        }
    }
}

// Variables to store messages
$success_msg = "";
$error_msg = "";

// Handle approve/reject action
if (isset($_POST['update_status']) && isset($_POST['reg_id']) && isset($_POST['status'])) {
    $reg_id = (int)$_POST['reg_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $admin_notes = isset($_POST['admin_notes']) ? mysqli_real_escape_string($conn, $_POST['admin_notes']) : '';

    // Check if admin_notes column exists
    $admin_notes_exists = columnExists($conn, 'child_registrations', 'admin_notes');

    if ($status == 'approved' || $status == 'rejected' || $status == 'pending') {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Update registration status
            if ($admin_notes_exists) {
                $update_query = "UPDATE child_registrations SET status = '$status', admin_notes = '$admin_notes' WHERE reg_id = $reg_id";
            } else {
                $update_query = "UPDATE child_registrations SET status = '$status' WHERE reg_id = $reg_id";
            }

            if (!mysqli_query($conn, $update_query)) {
                throw new Exception("Error updating registration status: " . mysqli_error($conn));
            }

            // If approved, also add child to children table
            if ($status == 'approved') {
                // First get registration details
                $reg_query = "SELECT * FROM child_registrations WHERE reg_id = $reg_id";
                $reg_result = safeQuery($conn, $reg_query);

                if ($reg_result && mysqli_num_rows($reg_result) > 0) {
                    $reg_data = mysqli_fetch_assoc($reg_result);

                    // Check if children table exists, create if not
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
                        mysqli_query($conn, $create_children_table);
                    }

                    // Insert into children table
                    $insert_query = "INSERT INTO children (name, age, gender, education_level, health_status, status, photo, admission_date) 
                                     VALUES (?, ?, ?, ?, ?, 'available', ?, NOW())";

                    $stmt = mysqli_prepare($conn, $insert_query);

                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'sissss',
                                $reg_data['name'],
                                $reg_data['age'],
                                $reg_data['gender'],
                                $reg_data['education_level'],
                                $reg_data['health_status'],
                                $reg_data['photo']
                        );
                        mysqli_stmt_execute($stmt);
                    }
                }
            }

            // Commit transaction
            mysqli_commit($conn);

            $success_msg = "Child registration has been " . ($status == 'approved' ? 'approved' : ($status == 'rejected' ? 'rejected' : 'set to pending')) . " successfully.";
            if ($status == 'approved') {
                $success_msg .= " The child has been added to the system.";
            }

            // Redirect to the appropriate page after status update
            $redirect_url = "child_registrations.php?status_updated=true&message=" . urlencode($success_msg);

            // If there's a search term, include it
            if (!empty($_POST['search'])) {
                $redirect_url .= "&search=" . urlencode($_POST['search']);
            }

            // If we're filtering, ensure we stay on the appropriate view
            if ($status === 'pending') {
                // If set to pending, direct to pending filter
                $redirect_url .= "&status=pending";
            } else if ($status === 'approved') {
                // If approved and not filtering or filtering by pending, go to approved view
                if (empty($_POST['current_filter']) || $_POST['current_filter'] === 'pending') {
                    $redirect_url .= "&status=approved";
                } else {
                    // Otherwise maintain current filter
                    $redirect_url .= "&status=" . urlencode($_POST['current_filter']);
                }
            } else if ($status === 'rejected') {
                // If rejected and not filtering or filtering by pending, go to rejected view
                if (empty($_POST['current_filter']) || $_POST['current_filter'] === 'pending') {
                    $redirect_url .= "&status=rejected";
                } else {
                    // Otherwise maintain current filter
                    $redirect_url .= "&status=" . urlencode($_POST['current_filter']);
                }
            }

            header("Location: $redirect_url");
            exit();

        } catch (Exception $e) {
            // Rollback in case of error
            mysqli_rollback($conn);
            $error_msg = $e->getMessage();
        }
    } else {
        $error_msg = "Invalid status provided.";
    }
}

// Check for status update message from redirect
if (isset($_GET['status_updated']) && $_GET['status_updated'] === 'true' && isset($_GET['message'])) {
    $success_msg = urldecode($_GET['message']);
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = !empty($search) ? "WHERE cr.name LIKE '%$search%' OR u.name LIKE '%$search%'" : "";

// Filter by status
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

if (!empty($status_filter)) {
    $search_condition = !empty($search_condition) ? $search_condition . " AND cr.status = '$status_filter'" : "WHERE cr.status = '$status_filter'";
}

// Get total records for pagination
$total_records = 0;
if (tableExists($conn, 'child_registrations')) {
    $total_query = "SELECT COUNT(*) as total FROM child_registrations cr";

    if (tableExists($conn, 'users')) {
        $total_query = "SELECT COUNT(*) as total 
                        FROM child_registrations cr 
                        LEFT JOIN users u ON cr.user_id = u.user_id 
                        $search_condition";
    }

    $total_result = safeQuery($conn, $total_query);

    if ($total_result && mysqli_num_rows($total_result) > 0) {
        $total_records = mysqli_fetch_assoc($total_result)['total'];
    }
}

$total_pages = ceil($total_records / $records_per_page);

// Get registrations with pagination
$registrations = [];
if (tableExists($conn, 'child_registrations')) {
    $query = "SELECT cr.*, 'Unknown' as user_name, '' as email 
              FROM child_registrations cr
              $search_condition 
              ORDER BY cr.submitted_at DESC LIMIT $offset, $records_per_page";

    if (tableExists($conn, 'users')) {
        $query = "SELECT cr.*, u.name as user_name, u.email 
                 FROM child_registrations cr 
                 LEFT JOIN users u ON cr.user_id = u.user_id 
                 $search_condition 
                 ORDER BY cr.submitted_at DESC LIMIT $offset, $records_per_page";
    }

    $result = safeQuery($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $registrations[] = $row;
        }
    }
}

// Count number of registrations by status for the filter badges
$status_counts = [];
if (tableExists($conn, 'child_registrations')) {
    $counts_query = "SELECT status, COUNT(*) as count FROM child_registrations GROUP BY status";
    $counts_result = safeQuery($conn, $counts_query);
    if ($counts_result) {
        while ($row = mysqli_fetch_assoc($counts_result)) {
            $status_counts[$row['status']] = $row['count'];
        }
    }
}

// Include admin header
include 'admin_header.php';
?>

    <!-- Content Container -->
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2 class="page-title"><i class="fas fa-user-plus me-2"></i>Child Registrations</h2>
                <p class="text-muted">Review and manage child registration submissions. Approve to add children to the system.</p>
            </div>
        </div>

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

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <form action="child_registrations.php" method="GET" class="d-flex">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by child or user name" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <!-- If there's a status filter, maintain it in the search form -->
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2">
                            <div class="btn-group" role="group">
                                <a href="child_registrations.php<?php echo !empty($search) ? '?search='.urlencode($search) : ''; ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                    All
                                    <span class="badge bg-light text-dark ms-1">
                                        <?php
                                        $total_count = 0;
                                        foreach ($status_counts as $count) {
                                            $total_count += $count;
                                        }
                                        echo $total_count;
                                        ?>
                                    </span>
                                </a>
                                <a href="child_registrations.php?status=pending<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                                   class="btn <?php echo ($status_filter == 'pending') ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                    Pending
                                    <span class="badge bg-light text-dark ms-1"><?php echo isset($status_counts['pending']) ? $status_counts['pending'] : 0; ?></span>
                                </a>
                                <a href="child_registrations.php?status=approved<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                                   class="btn <?php echo ($status_filter == 'approved') ? 'btn-success' : 'btn-outline-success'; ?>">
                                    Approved
                                    <span class="badge bg-light text-dark ms-1"><?php echo isset($status_counts['approved']) ? $status_counts['approved'] : 0; ?></span>
                                </a>
                                <a href="child_registrations.php?status=rejected<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                                   class="btn <?php echo ($status_filter == 'rejected') ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                    Rejected
                                    <span class="badge bg-light text-dark ms-1"><?php echo isset($status_counts['rejected']) ? $status_counts['rejected'] : 0; ?></span>
                                </a>
                            </div>
                            <?php if (!empty($search)): ?>
                                <a href="<?php echo !empty($status_filter) ? "child_registrations.php?status=$status_filter" : "child_registrations.php"; ?>"
                                   class="btn btn-sm btn-outline-secondary">
                                    Clear Search
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="text-muted">Total: <?php echo $total_records; ?> registrations</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Child</th>
                            <th>Registered By</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($registrations) > 0): ?>
                            <?php foreach ($registrations as $index => $registration): ?>
                                <tr>
                                    <td><?php echo $registration['reg_id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($registration['photo']) && file_exists("../" . $registration['photo'])): ?>
                                                <img src="<?php echo '../' . htmlspecialchars($registration['photo']); ?>" alt="<?php echo htmlspecialchars($registration['name']); ?>" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-child"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($registration['name']); ?></div>
                                                <div class="small text-muted"><?php echo $registration['age']; ?> years, <?php echo ucfirst($registration['gender']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($registration['user_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($registration['email']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($registration['submitted_at'])); ?></td>
                                    <td>
                                        <?php if ($registration['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($registration['status'] == 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="#" class="btn btn-primary view-btn" data-index="<?php echo $index; ?>" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($registration['status'] == 'pending'): ?>
                                                <a href="#" class="btn btn-success approve-btn" data-id="<?php echo $registration['reg_id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($registration['name']); ?>"
                                                   data-notes="<?php echo isset($registration['admin_notes']) ? htmlspecialchars($registration['admin_notes']) : ''; ?>" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="#" class="btn btn-danger reject-btn" data-id="<?php echo $registration['reg_id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($registration['name']); ?>"
                                                   data-notes="<?php echo isset($registration['admin_notes']) ? htmlspecialchars($registration['admin_notes']) : ''; ?>" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="#" class="btn btn-warning reset-btn" data-id="<?php echo $registration['reg_id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($registration['name']); ?>"
                                                   data-status="<?php echo $registration['status']; ?>"
                                                   data-notes="<?php echo isset($registration['admin_notes']) ? htmlspecialchars($registration['admin_notes']) : ''; ?>" title="Reset to Pending">
                                                    <i class="fas fa-redo"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                        <h5>No child registrations found</h5>
                                        <?php if (!empty($search) || !empty($status_filter)): ?>
                                            <p class="text-muted">No results match your search criteria. Try different keywords or filters.</p>
                                            <a href="child_registrations.php" class="btn btn-sm btn-outline-secondary mt-2">Clear Search</a>
                                        <?php else: ?>
                                            <p class="text-muted">There are no child registrations in the system yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>"
                                   aria-disabled="<?php echo ($page <= 1) ? 'true' : 'false'; ?>">Previous</a>
                            </li>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i .
                                            (!empty($search) ? '&search=' . urlencode($search) : '') .
                                            (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : ''); ?>"
                                   aria-disabled="<?php echo ($page >= $total_pages) ? 'true' : 'false'; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Store registrations data for JavaScript access -->
    <script>
        const registrations = <?php echo json_encode($registrations); ?>;
    </script>

    <!-- View Registration Modal -->
    <div class="modal" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Child Registration Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <div id="childPhotoContainer">
                                    <!-- Photo will be inserted here by JavaScript -->
                                </div>
                                <h5 id="childName"></h5>
                                <p class="text-muted mb-0" id="childDetails"></p>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Registered By</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> <span id="registeredByName"></span></p>
                                    <p class="mb-0"><strong>Email:</strong> <span id="registeredByEmail"></span></p>
                                </div>
                            </div>

                            <div id="statusAlert" class="alert">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas me-2" id="statusIcon"></i>
                                    <strong>Status: <span id="statusText"></span></strong>
                                </div>
                                <div class="small" id="submittedDate"></div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Child Information</h6>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0" id="childInformation">
                                        <!-- Child information will be inserted here by JavaScript -->
                                    </dl>
                                </div>
                            </div>

                            <div id="adminNotesCard" class="card border-info mb-0">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Admin Notes</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0" id="adminNotes"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <div id="actionButtons">
                        <!-- Action buttons will be inserted here by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Registration Modal -->
    <div class="modal" id="approveModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="child_registrations.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Child Registration</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to approve the registration for <strong id="approveChildName"></strong>?</p>
                        <p>Approving this registration will add the child to the system.</p>
                        <div class="mb-3">
                            <label for="admin_notes_approve" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" id="admin_notes_approve" name="admin_notes" rows="3" placeholder="Add any notes about this approval"></textarea>
                        </div>
                        <input type="hidden" name="reg_id" id="approve_reg_id" value="">
                        <input type="hidden" name="status" value="approved">
                        <!-- Store current status filter to return to the same view after approval -->
                        <input type="hidden" name="current_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-success">Approve Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Registration Modal -->
    <div class="modal" id="rejectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="child_registrations.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Child Registration</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reject the registration for <strong id="rejectChildName"></strong>?</p>
                        <div class="mb-3">
                            <label for="admin_notes_reject" class="form-label">Reason for Rejection (Recommended)</label>
                            <textarea class="form-control" id="admin_notes_reject" name="admin_notes" rows="3" placeholder="Provide a reason for rejection"></textarea>
                        </div>
                        <input type="hidden" name="reg_id" id="reject_reg_id" value="">
                        <input type="hidden" name="status" value="rejected">
                        <!-- Store current status filter to return to the same view after rejection -->
                        <input type="hidden" name="current_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-danger">Reject Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Registration Modal -->
    <div class="modal" id="resetModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="child_registrations.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Reset to Pending</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reset this registration back to "pending" status?</p>
                        <div id="resetWarning" class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This will not automatically remove the child from the system.
                            You will need to manually remove the child record if needed.
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes_reset" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" id="admin_notes_reset" name="admin_notes" rows="3" placeholder="Add any notes about this status change"></textarea>
                        </div>
                        <input type="hidden" name="reg_id" id="reset_reg_id" value="">
                        <input type="hidden" name="status" value="pending">
                        <!-- Store current status filter to return to the same view after reset -->
                        <input type="hidden" name="current_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-warning">Reset to Pending</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Force reload the page to ensure we see the updated data
        // This helps avoid caching issues
        window.onload = function() {
            // Check if we just processed a status update
            if (document.querySelector('.alert-success') &&
                document.querySelector('.alert-success').textContent.includes('successfully')) {
                // Reload only if it's the first time loading after a status change
                if (!sessionStorage.getItem('justUpdated')) {
                    sessionStorage.setItem('justUpdated', 'true');
                    // Add a slight delay before reloading to let the user see the message
                    setTimeout(function() {
                        window.location.reload(true); // Force reload from server, not cache
                    }, 1500); // Give user time to see the success message
                } else {
                    // Clear the flag for next time
                    sessionStorage.removeItem('justUpdated');
                }
            }
        };

        // Apply filters when select boxes change
        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const searchParam = '<?php echo !empty($search) ? "&search=" . urlencode($search) : ""; ?>';

            let url = 'child_registrations.php?';
            if (statusFilter) {
                url += 'status=' + statusFilter;
            }
            url += searchParam;

            window.location.href = url;
        }

        // Wait until the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize modals manually
            const viewModalEl = document.getElementById('viewModal');
            const approveModalEl = document.getElementById('approveModal');
            const rejectModalEl = document.getElementById('rejectModal');
            const resetModalEl = document.getElementById('resetModal');

            // Create modal instances
            const viewModal = new bootstrap.Modal(viewModalEl);
            const approveModal = new bootstrap.Modal(approveModalEl);
            const rejectModal = new bootstrap.Modal(rejectModalEl);
            const resetModal = new bootstrap.Modal(resetModalEl);

            // Format date for display
            function formatDate(dateString) {
                const date = new Date(dateString);
                const options = { month: 'long', day: 'numeric', year: 'numeric' };
                return date.toLocaleDateString('en-US', options);
            }

            // View registration handlers
            document.querySelectorAll('.view-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const index = parseInt(this.getAttribute('data-index'));
                    const registration = registrations[index];

                    // Set child photo
                    const photoContainer = document.getElementById('childPhotoContainer');
                    if (registration.photo && registration.photo.length > 0) {
                        photoContainer.innerHTML = `<img src="../${registration.photo}" alt="${registration.name}" class="img-fluid rounded mb-2" style="max-height: 200px; object-fit: cover;">`;
                    } else {
                        photoContainer.innerHTML = `<div class="rounded bg-light d-flex align-items-center justify-content-center mb-2" style="height: 200px;">
                                          <i class="fas fa-child fa-5x text-secondary"></i>
                                        </div>`;
                    }

                    // Set child name and details
                    document.getElementById('childName').textContent = registration.name;
                    document.getElementById('childDetails').textContent = `${registration.age} years, ${registration.gender.charAt(0).toUpperCase() + registration.gender.slice(1)}`;

                    // Set registered by info
                    document.getElementById('registeredByName').textContent = registration.user_name;
                    document.getElementById('registeredByEmail').textContent = registration.email;

                    // Set status information
                    const statusAlert = document.getElementById('statusAlert');
                    const statusIcon = document.getElementById('statusIcon');
                    const statusText = document.getElementById('statusText');

                    statusText.textContent = registration.status.charAt(0).toUpperCase() + registration.status.slice(1);

                    if (registration.status === 'pending') {
                        statusAlert.className = 'alert alert-warning';
                        statusIcon.className = 'fas fa-clock me-2';
                    } else if (registration.status === 'approved') {
                        statusAlert.className = 'alert alert-success';
                        statusIcon.className = 'fas fa-check-circle me-2';
                    } else {
                        statusAlert.className = 'alert alert-danger';
                        statusIcon.className = 'fas fa-times-circle me-2';
                    }

                    document.getElementById('submittedDate').textContent = `Submitted: ${formatDate(registration.submitted_at)}`;

                    // Set child information
                    let infoHtml = '';

                    if (registration.education_level) {
                        infoHtml += `<dt class="col-sm-4">Education Level:</dt>
                         <dd class="col-sm-8">${registration.education_level}</dd>`;
                    }

                    if (registration.health_status) {
                        infoHtml += `<dt class="col-sm-4">Health Status:</dt>
                         <dd class="col-sm-8">${registration.health_status}</dd>`;
                    }

                    if (registration.found_location) {
                        infoHtml += `<dt class="col-sm-4">Found Location:</dt>
                         <dd class="col-sm-8">${registration.found_location}</dd>`;
                    }

                    if (registration.additional_info) {
                        infoHtml += `<dt class="col-sm-4">Additional Info:</dt>
                         <dd class="col-sm-8">${registration.additional_info.replace(/\n/g, '<br>')}</dd>`;
                    }

                    document.getElementById('childInformation').innerHTML = infoHtml || '<p class="text-muted mb-0">No additional information available.</p>';

                    // Set admin notes
                    const adminNotesCard = document.getElementById('adminNotesCard');
                    const adminNotes = document.getElementById('adminNotes');

                    if (registration.admin_notes && registration.admin_notes.trim().length > 0) {
                        adminNotesCard.style.display = 'block';
                        adminNotes.innerHTML = registration.admin_notes.replace(/\n/g, '<br>');
                    } else {
                        adminNotesCard.style.display = 'none';
                    }

                    // Set action buttons based on status
                    const actionButtons = document.getElementById('actionButtons');
                    if (registration.status === 'pending') {
                        actionButtons.innerHTML = `
                    <button type="button" class="btn btn-success" data-reg-id="${registration.reg_id}" data-name="${registration.name}" id="approveBtn">
                        <i class="fas fa-check me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" data-reg-id="${registration.reg_id}" data-name="${registration.name}" id="rejectBtn">
                        <i class="fas fa-times me-1"></i>Reject
                    </button>
                `;

                        // Add event listeners for the buttons
                        setTimeout(() => {
                            document.getElementById('approveBtn').addEventListener('click', function() {
                                viewModal.hide();
                                document.getElementById('approveChildName').textContent = this.getAttribute('data-name');
                                document.getElementById('approve_reg_id').value = this.getAttribute('data-reg-id');
                                document.getElementById('admin_notes_approve').value = registration.admin_notes || '';
                                setTimeout(() => {
                                    approveModal.show();
                                }, 500);
                            });

                            document.getElementById('rejectBtn').addEventListener('click', function() {
                                viewModal.hide();
                                document.getElementById('rejectChildName').textContent = this.getAttribute('data-name');
                                document.getElementById('reject_reg_id').value = this.getAttribute('data-reg-id');
                                document.getElementById('admin_notes_reject').value = registration.admin_notes || '';
                                setTimeout(() => {
                                    rejectModal.show();
                                }, 500);
                            });
                        }, 300);
                    } else if (registration.status === 'approved') {
                        actionButtons.innerHTML = `
                    <button type="button" class="btn btn-warning" data-reg-id="${registration.reg_id}" data-name="${registration.name}" data-status="${registration.status}" id="resetBtnModal">
                        <i class="fas fa-redo me-1"></i>Reset to Pending
                    </button>
                `;
                        setTimeout(() => {
                            document.getElementById('resetBtnModal').addEventListener('click', function() {
                                viewModal.hide();
                                document.getElementById('reset_reg_id').value = this.getAttribute('data-reg-id');
                                document.getElementById('admin_notes_reset').value = registration.admin_notes || '';
                                document.getElementById('resetWarning').style.display = 'block';
                                setTimeout(() => {
                                    resetModal.show();
                                }, 500);
                            });
                        }, 300);
                    } else if (registration.status === 'rejected') {
                        actionButtons.innerHTML = `
                    <button type="button" class="btn btn-warning" data-reg-id="${registration.reg_id}" data-name="${registration.name}" data-status="${registration.status}" id="resetBtnModal">
                        <i class="fas fa-redo me-1"></i>Reset to Pending
                    </button>
                `;
                        setTimeout(() => {
                            document.getElementById('resetBtnModal').addEventListener('click', function() {
                                viewModal.hide();
                                document.getElementById('reset_reg_id').value = this.getAttribute('data-reg-id');
                                document.getElementById('admin_notes_reset').value = registration.admin_notes || '';
                                document.getElementById('resetWarning').style.display = 'none';
                                setTimeout(() => {
                                    resetModal.show();
                                }, 500);
                            });
                        }, 300);
                    }

                    // Show the modal
                    viewModal.show();
                });
            });

            // Approve registration handlers
            document.querySelectorAll('.approve-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const regId = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const notes = this.getAttribute('data-notes');

                    document.getElementById('approveChildName').textContent = name;
                    document.getElementById('approve_reg_id').value = regId;
                    document.getElementById('admin_notes_approve').value = notes || '';

                    approveModal.show();
                });
            });

            // Reject registration handlers
            document.querySelectorAll('.reject-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const regId = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const notes = this.getAttribute('data-notes');

                    document.getElementById('rejectChildName').textContent = name;
                    document.getElementById('reject_reg_id').value = regId;
                    document.getElementById('admin_notes_reject').value = notes || '';

                    rejectModal.show();
                });
            });

            // Reset registration handlers
            document.querySelectorAll('.reset-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const regId = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const status = this.getAttribute('data-status');
                    const notes = this.getAttribute('data-notes');

                    document.getElementById('reset_reg_id').value = regId;
                    document.getElementById('admin_notes_reset').value = notes || '';

                    // Show warning if currently approved
                    document.getElementById('resetWarning').style.display = (status === 'approved') ? 'block' : 'none';

                    resetModal.show();
                });
            });
        });
    </script>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>