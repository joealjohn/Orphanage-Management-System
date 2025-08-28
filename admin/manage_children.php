<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Manage Children";
$current_page = "manage_children";

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

// Check if children table exists
if (!tableExists($conn, 'children')) {
    // Create children table
    $create_table = "CREATE TABLE children (
        child_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        age INT NOT NULL,
        gender ENUM('male', 'female') NOT NULL,
        education_level VARCHAR(255),
        health_status TEXT,
        status ENUM('available', 'adopted', 'pending') DEFAULT 'available',
        photo VARCHAR(255),
        admission_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    if (!mysqli_query($conn, $create_table)) {
        $error_msg = "Error creating children table: " . mysqli_error($conn);
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $child_id = (int)$_GET['id'];

    // Check if child has pending or approved adoption requests
    $has_adoptions = false;
    if (tableExists($conn, 'adoption_requests')) {
        $check_adoptions = "SELECT COUNT(*) as count FROM adoption_requests WHERE child_id = $child_id AND status IN ('pending', 'approved')";
        $adoptions_result = safeQuery($conn, $check_adoptions);
        if ($adoptions_result && mysqli_num_rows($adoptions_result) > 0) {
            $has_adoptions = mysqli_fetch_assoc($adoptions_result)['count'] > 0;
        }
    }

    if ($has_adoptions) {
        $error_msg = "Cannot delete this child. There are pending or approved adoption requests associated with this child.";
    } else {
        // Get child photo to delete file
        $photo_query = "SELECT photo FROM children WHERE child_id = $child_id";
        $photo_result = safeQuery($conn, $photo_query);
        if ($photo_result && mysqli_num_rows($photo_result) > 0) {
            $photo = mysqli_fetch_assoc($photo_result)['photo'];
            // Delete photo file if it exists
            if (!empty($photo) && file_exists("../$photo")) {
                unlink("../$photo");
            }
        }

        // Delete from database
        $delete_query = "DELETE FROM children WHERE child_id = $child_id";
        if (mysqli_query($conn, $delete_query)) {
            $success_msg = "Child record deleted successfully.";
        } else {
            $error_msg = "Error deleting child record: " . mysqli_error($conn);
        }
    }
}

// Handle status change action
if (isset($_GET['action']) && $_GET['action'] == 'status' && isset($_GET['id']) && isset($_GET['status'])) {
    $child_id = (int)$_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);

    if ($status == 'available' || $status == 'adopted' || $status == 'pending') {
        $update_query = "UPDATE children SET status = '$status' WHERE child_id = $child_id";
        if (mysqli_query($conn, $update_query)) {
            $success_msg = "Child status updated to " . ucfirst($status) . " successfully.";

            // If marking as adopted, update any pending adoption requests
            if ($status == 'adopted' && tableExists($conn, 'adoption_requests')) {
                $update_requests = "UPDATE adoption_requests SET status = 'rejected', admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nAutomatically rejected because child was marked as adopted.') 
                                  WHERE child_id = $child_id AND status = 'pending'";
                mysqli_query($conn, $update_requests);
            }
        } else {
            $error_msg = "Error updating child status: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Invalid status provided.";
    }
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = !empty($search) ? "WHERE name LIKE '%$search%' OR education_level LIKE '%$search%' OR health_status LIKE '%$search%'" : "";

// Filter by status or gender
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$gender_filter = isset($_GET['gender']) ? mysqli_real_escape_string($conn, $_GET['gender']) : '';

if (!empty($status_filter)) {
    $search_condition = !empty($search_condition) ? $search_condition . " AND status = '$status_filter'" : "WHERE status = '$status_filter'";
}

if (!empty($gender_filter)) {
    $search_condition = !empty($search_condition) ? $search_condition . " AND gender = '$gender_filter'" : "WHERE gender = '$gender_filter'";
}

// Sort options
$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'newest';
$sort_condition = "ORDER BY child_id DESC"; // default to newest

if ($sort == 'oldest') {
    $sort_condition = "ORDER BY child_id ASC";
} elseif ($sort == 'name_asc') {
    $sort_condition = "ORDER BY name ASC";
} elseif ($sort == 'name_desc') {
    $sort_condition = "ORDER BY name DESC";
} elseif ($sort == 'age_asc') {
    $sort_condition = "ORDER BY age ASC";
} elseif ($sort == 'age_desc') {
    $sort_condition = "ORDER BY age DESC";
}

// Get total records for pagination
$total_records = 0;
if (tableExists($conn, 'children')) {
    $total_query = "SELECT COUNT(*) as total FROM children $search_condition";
    $total_result = safeQuery($conn, $total_query);
    if ($total_result && mysqli_num_rows($total_result) > 0) {
        $total_records = mysqli_fetch_assoc($total_result)['total'];
    }
}

// Get status counts for filter badges
$available_count = 0;
$adopted_count = 0;
$pending_count = 0;
if (tableExists($conn, 'children')) {
    $status_counts_query = "SELECT status, COUNT(*) as count FROM children GROUP BY status";
    $status_counts_result = safeQuery($conn, $status_counts_query);
    if ($status_counts_result) {
        while ($row = mysqli_fetch_assoc($status_counts_result)) {
            if ($row['status'] == 'available') {
                $available_count = $row['count'];
            } elseif ($row['status'] == 'adopted') {
                $adopted_count = $row['count'];
            } elseif ($row['status'] == 'pending') {
                $pending_count = $row['count'];
            }
        }
    }
}

$total_pages = ceil($total_records / $records_per_page);

// Get children records with pagination
$children = [];
if (tableExists($conn, 'children')) {
    $query = "SELECT * FROM children $search_condition $sort_condition LIMIT $offset, $records_per_page";
    $result = safeQuery($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $children[] = $row;
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
                    <i class="fas fa-child text-primary me-2"></i> Manage Children
                </h1>
                <p class="mb-0 text-muted">View, edit, and manage all children in the care center.</p>
            </div>
            <a href="add_child.php" class="btn btn-success shadow-sm">
                <i class="fas fa-plus-circle fa-sm me-2"></i>Add New Child
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

        <!-- Status Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Children</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_records; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-child fa-2x text-gray-300"></i>
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
                                    Available</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $available_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Adopted</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $adopted_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-home fa-2x text-gray-300"></i>
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
                                    Pending</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Search & Filter</h6>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Search Form -->
                    <div class="col-md-5 mb-3 mb-md-0">
                        <form action="manage_children.php" method="GET" class="d-flex">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by name, education level..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <?php if (!empty($gender_filter)): ?>
                                <input type="hidden" name="gender" value="<?php echo htmlspecialchars($gender_filter); ?>">
                            <?php endif; ?>
                            <?php if ($sort != 'newest'): ?>
                                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap gap-2">
                            <!-- Status Filter -->
                            <div class="btn-group me-2 mb-2 mb-md-0" role="group">
                                <a href="manage_children.php<?php echo !empty($search) || !empty($gender_filter) || $sort != 'newest' ?
                                        '?' .
                                        (!empty($search) ? 'search=' . urlencode($search) . '&' : '') .
                                        (!empty($gender_filter) ? 'gender=' . $gender_filter . '&' : '') .
                                        ($sort != 'newest' ? 'sort=' . $sort : '') : ''; ?>"
                                   class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">
                                    All
                                </a>
                                <a href="manage_children.php?status=available<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?><?php echo $sort != 'newest' ? '&sort=' . $sort : ''; ?>"
                                   class="btn <?php echo $status_filter == 'available' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                                    Available <span class="badge bg-white text-success"><?php echo $available_count; ?></span>
                                </a>
                                <a href="manage_children.php?status=adopted<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?><?php echo $sort != 'newest' ? '&sort=' . $sort : ''; ?>"
                                   class="btn <?php echo $status_filter == 'adopted' ? 'btn-info' : 'btn-outline-info'; ?> btn-sm">
                                    Adopted <span class="badge bg-white text-info"><?php echo $adopted_count; ?></span>
                                </a>
                                <a href="manage_children.php?status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?><?php echo $sort != 'newest' ? '&sort=' . $sort : ''; ?>"
                                   class="btn <?php echo $status_filter == 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?> btn-sm">
                                    Pending <span class="badge bg-white text-warning"><?php echo $pending_count; ?></span>
                                </a>
                            </div>

                            <!-- Gender Filter -->
                            <div class="btn-group me-2 mb-2 mb-md-0">
                                <a href="manage_children.php?gender=male<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo $sort != 'newest' ? '&sort=' . $sort : ''; ?>"
                                   class="btn <?php echo $gender_filter == 'male' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">
                                    <i class="fas fa-mars"></i> Boys
                                </a>
                                <a href="manage_children.php?gender=female<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo $sort != 'newest' ? '&sort=' . $sort : ''; ?>"
                                   class="btn <?php echo $gender_filter == 'female' ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm">
                                    <i class="fas fa-venus"></i> Girls
                                </a>
                            </div>

                            <!-- Sort Dropdown -->
                            <div class="dropdown mb-2 mb-md-0">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-sort me-1"></i>
                                    <?php
                                    switch ($sort) {
                                        case 'oldest':
                                            echo 'Oldest First';
                                            break;
                                        case 'name_asc':
                                            echo 'Name (A-Z)';
                                            break;
                                        case 'name_desc':
                                            echo 'Name (Z-A)';
                                            break;
                                        case 'age_asc':
                                            echo 'Age (Youngest)';
                                            break;
                                        case 'age_desc':
                                            echo 'Age (Oldest)';
                                            break;
                                        default:
                                            echo 'Newest First';
                                    }
                                    ?>
                                </button>
                                <ul class="dropdown-menu shadow" aria-labelledby="sortDropdown">
                                    <li><a class="dropdown-item <?php echo ($sort == 'newest') ? 'active' : ''; ?>"
                                           href="manage_children.php?sort=newest<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?>">
                                            <i class="fas fa-sort-numeric-down me-1"></i> Newest First
                                        </a></li>
                                    <li><a class="dropdown-item <?php echo ($sort == 'oldest') ? 'active' : ''; ?>"
                                           href="manage_children.php?sort=oldest<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?>">
                                            <i class="fas fa-sort-numeric-up me-1"></i> Oldest First
                                        </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?php echo ($sort == 'name_asc') ? 'active' : ''; ?>"
                                           href="manage_children.php?sort=name_asc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?>">
                                            <i class="fas fa-sort-alpha-down me-1"></i> Name (A-Z)
                                        </a></li>
                                    <li><a class="dropdown-item <?php echo ($sort == 'name_desc') ? 'active' : ''; ?>"
                                           href="manage_children.php?sort=name_desc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?>">
                                            <i class="fas fa-sort-alpha-up me-1"></i> Name (Z-A)
                                        </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?php echo ($sort == 'age_asc') ? 'active' : ''; ?>"
                                           href="manage_children.php?sort=age_asc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?>">
                                            <i class="fas fa-baby me-1"></i> Age (Youngest First)
                                        </a></li>
                                    <li><a class="dropdown-item <?php echo ($sort == 'age_desc') ? 'active' : ''; ?>"
                                           href="manage_children.php?sort=age_desc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($gender_filter) ? '&gender=' . $gender_filter : ''; ?>">
                                            <i class="fas fa-child me-1"></i> Age (Oldest First)
                                        </a></li>
                                </ul>
                            </div>

                            <!-- Clear Filters Button -->
                            <?php if (!empty($search) || !empty($status_filter) || !empty($gender_filter) || $sort != 'newest'): ?>
                                <a href="manage_children.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times-circle me-1"></i>Clear All
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Children Records Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list me-2"></i>Children Records
                </h6>
                <span class="badge bg-primary"><?php echo $total_records; ?> Records</span>
            </div>
            <div class="card-body p-0">
                <?php if (count($children) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">#</th>
                                <th scope="col">Child</th>
                                <th scope="col">Age</th>
                                <th scope="col">Gender</th>
                                <th scope="col">Status</th>
                                <th scope="col">Admission Date</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($children as $child): ?>
                                <tr class="align-middle">
                                    <td class="ps-3"><?php echo $child['child_id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if (!empty($child['photo']) && file_exists("../" . $child['photo'])): ?>
                                                    <img src="<?php echo '../' . htmlspecialchars($child['photo']); ?>"
                                                         alt="<?php echo htmlspecialchars($child['name']); ?>"
                                                         class="rounded-circle" width="50" height="50"
                                                         style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-<?php echo $child['gender'] == 'male' ? 'primary' : 'danger'; ?> bg-opacity-10 text-<?php echo $child['gender'] == 'male' ? 'primary' : 'danger'; ?> d-flex align-items-center justify-content-center"
                                                         style="width: 50px; height: 50px;">
                                                        <i class="fas <?php echo $child['gender'] == 'male' ? 'fa-boy' : 'fa-girl'; ?> fa-lg"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($child['name']); ?></h6>
                                                <?php if (!empty($child['education_level'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($child['education_level']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $child['age']; ?> years</td>
                                    <td>
                                        <span class="badge bg-<?php echo $child['gender'] == 'male' ? 'primary' : 'danger'; ?> bg-opacity-10 text-<?php echo $child['gender'] == 'male' ? 'primary' : 'danger'; ?> py-2 px-3">
                                            <i class="fas <?php echo $child['gender'] == 'male' ? 'fa-mars' : 'fa-venus'; ?> me-1"></i>
                                            <?php echo ucfirst($child['gender']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($child['status'] == 'available'): ?>
                                            <span class="badge bg-success py-2 px-3">
                                                <i class="fas fa-check-circle me-1"></i>Available
                                            </span>
                                        <?php elseif ($child['status'] == 'adopted'): ?>
                                            <span class="badge bg-info py-2 px-3">
                                                <i class="fas fa-home me-1"></i>Adopted
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark py-2 px-3">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span data-bs-toggle="tooltip" title="<?php echo date('F j, Y - g:i A', strtotime($child['admission_date'])); ?>">
                                            <?php echo date('M d, Y', strtotime($child['admission_date'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            <a href="view_child.php?id=<?php echo $child['child_id']; ?>"
                                               class="btn btn-primary btn-sm me-1"
                                               data-bs-toggle="tooltip"
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_child.php?id=<?php echo $child['child_id']; ?>"
                                               class="btn btn-secondary btn-sm me-1"
                                               data-bs-toggle="tooltip"
                                               title="Edit Details">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-info btn-sm me-1"
                                                    data-bs-toggle="tooltip"
                                                    title="Change Status"
                                                    onclick="changeStatus(<?php echo $child['child_id']; ?>, '<?php echo htmlspecialchars($child['name']); ?>', '<?php echo $child['status']; ?>');">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    data-bs-toggle="tooltip"
                                                    title="Delete Record"
                                                    onclick="confirmDelete(<?php echo $child['child_id']; ?>, '<?php echo htmlspecialchars($child['name']); ?>', '<?php echo $child['status']; ?>');">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
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
                            <img src="../assets/img/empty_children.svg" alt="No Children" style="max-width: 200px; opacity: 0.6;">
                        </div>
                        <h5 class="text-gray-700 mb-3">No Children Records Found</h5>
                        <?php if (!empty($search) || !empty($status_filter) || !empty($gender_filter)): ?>
                            <p class="text-muted mb-4">No results match your search criteria. Try different keywords or filters.</p>
                            <a href="manage_children.php" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-1"></i>Clear Search
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-4">There are no children in the system yet. Get started by adding a child record.</p>
                            <a href="add_child.php" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Add First Child
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
                                        (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                        (!empty($gender_filter) ? '&gender=' . $gender_filter : '') .
                                        ($sort != 'newest' ? '&sort=' . $sort : ''); ?>"
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
                                        (!empty($gender_filter) ? '&gender=' . $gender_filter : '') .
                                        ($sort != 'newest' ? '&sort=' . $sort : '') .
                                        '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i .
                                            (!empty($search) ? '&search=' . urlencode($search) : '') .
                                            (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                            (!empty($gender_filter) ? '&gender=' . $gender_filter : '') .
                                            ($sort != 'newest' ? '&sort=' . $sort : ''); ?>">
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
                                        (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                        (!empty($gender_filter) ? '&gender=' . $gender_filter : '') .
                                        ($sort != 'newest' ? '&sort=' . $sort : '') .
                                        '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) .
                                        (!empty($search) ? '&search=' . urlencode($search) : '') .
                                        (!empty($status_filter) ? '&status=' . $status_filter : '') .
                                        (!empty($gender_filter) ? '&gender=' . $gender_filter : '') .
                                        ($sort != 'newest' ? '&sort=' . $sort : ''); ?>"
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

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">
                        <i class="fas fa-exchange-alt me-2 text-primary"></i>Change Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="statusModalBody">
                    <p>Select new status for <strong id="childName" class="text-primary"></strong>:</p>

                    <div class="d-grid gap-2 mt-3" id="statusOptions">
                        <!-- Status options will be inserted here via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Child Record
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the record for <strong id="deleteChildName" class="text-danger"></strong>?</p>
                    <p class="text-danger">This action cannot be undone. All data associated with this child will be permanently removed.</p>

                    <div id="adoptedWarning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle me-2"></i>This child is marked as adopted. Are you sure you want to delete their record?
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-danger" id="deleteConfirmButton">
                        <i class="fas fa-trash-alt me-1"></i>Delete Record
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

        // Apply filters when select boxes change
        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const genderFilter = document.getElementById('genderFilter').value;
            const sortFilter = document.getElementById('sortFilter').value;
            const searchParam = '<?php echo !empty($search) ? "&search=" . urlencode($search) : ""; ?>';

            let url = 'manage_children.php?';
            let params = [];

            if (statusFilter) params.push('status=' + statusFilter);
            if (genderFilter) params.push('gender=' + genderFilter);
            if (sortFilter && sortFilter !== 'newest') params.push('sort=' + sortFilter);
            if (searchParam) params.push(searchParam.substring(1)); // Remove the leading &

            url += params.join('&');

            window.location.href = url;
        }

        // Change status modal function
        function changeStatus(childId, name, currentStatus) {
            const modal = document.getElementById('statusModal');
            const childNameElement = document.getElementById('childName');
            const statusOptionsElement = document.getElementById('statusOptions');

            childNameElement.textContent = name;

            // Clear previous options
            statusOptionsElement.innerHTML = '';

            // Add status options based on current status
            if (currentStatus !== 'available') {
                const availableLink = document.createElement('a');
                availableLink.href = `manage_children.php?action=status&id=${childId}&status=available`;
                availableLink.className = 'btn btn-success mb-2';
                availableLink.innerHTML = '<i class="fas fa-check-circle me-2"></i>Mark as Available';
                statusOptionsElement.appendChild(availableLink);
            }

            if (currentStatus !== 'adopted') {
                const adoptedLink = document.createElement('a');
                adoptedLink.href = `manage_children.php?action=status&id=${childId}&status=adopted`;
                adoptedLink.className = 'btn btn-info mb-2';
                adoptedLink.innerHTML = '<i class="fas fa-home me-2"></i>Mark as Adopted';
                statusOptionsElement.appendChild(adoptedLink);
            }

            if (currentStatus !== 'pending') {
                const pendingLink = document.createElement('a');
                pendingLink.href = `manage_children.php?action=status&id=${childId}&status=pending`;
                pendingLink.className = 'btn btn-warning mb-2';
                pendingLink.innerHTML = '<i class="fas fa-clock me-2"></i>Mark as Pending';
                statusOptionsElement.appendChild(pendingLink);
            }

            // Show status message
            const statusMessage = document.createElement('div');
            statusMessage.className = `alert ${
                currentStatus === 'available' ? 'alert-success' :
                    (currentStatus === 'adopted' ? 'alert-info' : 'alert-warning')
            } mt-3 mb-0`;

            statusMessage.innerHTML = `<i class="fas fa-${
                currentStatus === 'available' ? 'check-circle' :
                    (currentStatus === 'adopted' ? 'home' : 'clock')
            } me-2"></i>Current Status: <strong>${currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)}</strong>`;

            statusOptionsElement.appendChild(statusMessage);

            // Show the modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

        // Delete confirmation modal
        function confirmDelete(childId, name, status) {
            const modal = document.getElementById('deleteModal');
            const childNameElement = document.getElementById('deleteChildName');
            const adoptedWarning = document.getElementById('adoptedWarning');
            const deleteButton = document.getElementById('deleteConfirmButton');

            childNameElement.textContent = name;

            // Show adopted warning if applicable
            if (status === 'adopted') {
                adoptedWarning.classList.remove('d-none');
            } else {
                adoptedWarning.classList.add('d-none');
            }

            // Set delete button link
            deleteButton.href = `manage_children.php?action=delete&id=${childId}`;

            // Show the modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    </script>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>