<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "View Messages";
$current_page = "view_messages";

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

// Helper function to get primary key column name
function getPrimaryKeyColumn($conn, $table) {
    $query = "SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['Column_name'];
    }
    return null;
}

// Variables to store messages
$success_msg = "";
$error_msg = "";
$current_message = null;

// Create contact_messages table if it doesn't exist
if (!tableExists($conn, 'contact_messages')) {
    $create_table = "CREATE TABLE contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        subject VARCHAR(255),
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    if (!mysqli_query($conn, $create_table)) {
        $error_msg = "Error creating contact_messages table: " . mysqli_error($conn);
    }
}

// Get primary key column name (might not be 'id')
$id_column = getPrimaryKeyColumn($conn, 'contact_messages');
if (!$id_column) {
    $id_column = 'id'; // Default to 'id' if no primary key found
}

// Ensure is_read column exists
addColumnIfNotExists($conn, 'contact_messages', 'is_read', 'TINYINT(1) DEFAULT 0');

// Determine the datetime column name
$datetime_column = 'created_at'; // Default to created_at
$columns_to_check = ['created_at', 'timestamp', 'sent_at', 'date', 'message_date'];
foreach ($columns_to_check as $column) {
    if (columnExists($conn, 'contact_messages', $column)) {
        $datetime_column = $column;
        break;
    }
}

// If no datetime column found, add created_at
if (!columnExists($conn, 'contact_messages', $datetime_column)) {
    addColumnIfNotExists($conn, 'contact_messages', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
    $datetime_column = 'created_at';
}

// Create config file if it doesn't exist
$config_dir = "../includes";
$config_file = "$config_dir/config.php";
if (!file_exists($config_file)) {
    // Create directory if it doesn't exist
    if (!file_exists($config_dir)) {
        mkdir($config_dir, 0755, true);
    }

    $config_content = '<?php
// Database Configuration
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "orphanage");

// Website Configuration
define("SITE_NAME", "Orphanage Care System");
define("SITE_URL", "http://localhost/orphanage");
define("ADMIN_EMAIL", "admin@example.com");

// File Upload Configuration
define("UPLOAD_DIR", "uploads/");
define("MAX_FILE_SIZE", 5242880); // 5MB
define("ALLOWED_EXTENSIONS", ["jpg", "jpeg", "png", "gif", "pdf", "doc", "docx"]);

// Other Settings
define("DEBUG_MODE", true);
define("DEFAULT_TIMEZONE", "UTC");
date_default_timezone_set(DEFAULT_TIMEZONE);
?>';

    file_put_contents($config_file, $config_content);
}

// Insert a test message if table is empty
$count_query = "SELECT COUNT(*) as count FROM contact_messages";
$count_result = safeQuery($conn, $count_query);
if ($count_result && mysqli_fetch_assoc($count_result)['count'] == 0) {
    $insert_test = "INSERT INTO contact_messages (name, email, subject, message, $datetime_column, is_read) 
                   VALUES ('Test User', 'test@example.com', 'Test Subject', 'This is a test message to ensure the contact messages system is working correctly. If you are seeing this message, it means no real messages have been submitted through the contact form yet.', NOW(), 0)";
    mysqli_query($conn, $insert_test);
}

// Update existing rows that have NULL is_read to default value 0
$update_null_is_read = "UPDATE contact_messages SET is_read = 0 WHERE is_read IS NULL";
mysqli_query($conn, $update_null_is_read);

// Handle mark as read/unread action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];

    if ($_GET['action'] == 'read') {
        $update_query = "UPDATE contact_messages SET is_read = 1 WHERE $id_column = $message_id";
        if (mysqli_query($conn, $update_query)) {
            $success_msg = "Message marked as read.";
        } else {
            $error_msg = "Error updating message: " . mysqli_error($conn);
        }
    } elseif ($_GET['action'] == 'unread') {
        $update_query = "UPDATE contact_messages SET is_read = 0 WHERE $id_column = $message_id";
        if (mysqli_query($conn, $update_query)) {
            $success_msg = "Message marked as unread.";
        } else {
            $error_msg = "Error updating message: " . mysqli_error($conn);
        }
    } elseif ($_GET['action'] == 'delete') {
        $delete_query = "DELETE FROM contact_messages WHERE $id_column = $message_id";
        if (mysqli_query($conn, $delete_query)) {
            $success_msg = "Message deleted successfully.";
        } else {
            $error_msg = "Error deleting message: " . mysqli_error($conn);
        }
    }
}

// View message detail
if (isset($_GET['id']) && is_numeric($_GET['id']) && !isset($_GET['action'])) {
    $message_id = (int)$_GET['id'];
    $query = "SELECT * FROM contact_messages WHERE $id_column = $message_id";
    $result = safeQuery($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $current_message = mysqli_fetch_assoc($result);

        // Mark as read automatically when viewing
        if (isset($current_message['is_read']) && $current_message['is_read'] == 0) {
            $update_query = "UPDATE contact_messages SET is_read = 1 WHERE $id_column = $message_id";
            mysqli_query($conn, $update_query);
            $current_message['is_read'] = 1;
        }
    } else {
        $error_msg = "Message not found.";
    }
}

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] == 'mark_all_read') {
    $update_query = "UPDATE contact_messages SET is_read = 1 WHERE is_read = 0";
    if (mysqli_query($conn, $update_query)) {
        $success_msg = "All messages marked as read.";
    } else {
        $error_msg = "Error updating messages: " . mysqli_error($conn);
    }
}

// Pagination
$records_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = !empty($search) ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR subject LIKE '%$search%' OR message LIKE '%$search%'" : "";

// Filter by read status
$read_filter = isset($_GET['read_status']) ? mysqli_real_escape_string($conn, $_GET['read_status']) : '';
if (!empty($read_filter)) {
    $is_read = $read_filter == 'read' ? 1 : 0;
    $search_condition = !empty($search_condition) ? $search_condition . " AND is_read = $is_read" : "WHERE is_read = $is_read";
}

// Get total records for pagination
$total_records = 0;
if (tableExists($conn, 'contact_messages')) {
    $total_query = "SELECT COUNT(*) as total FROM contact_messages $search_condition";
    $total_result = safeQuery($conn, $total_query);
    if ($total_result && mysqli_num_rows($total_result) > 0) {
        $total_records = mysqli_fetch_assoc($total_result)['total'];
    }
}

$total_pages = ceil($total_records / $records_per_page);

// Count unread messages
$unread_count = 0;
if (tableExists($conn, 'contact_messages')) {
    $unread_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
    $unread_result = safeQuery($conn, $unread_query);
    if ($unread_result && mysqli_num_rows($unread_result) > 0) {
        $unread_count = mysqli_fetch_assoc($unread_result)['count'];
    }
}

// Get column names from table to use in query
$column_names = [];
$columns_query = "SHOW COLUMNS FROM contact_messages";
$columns_result = safeQuery($conn, $columns_query);
if ($columns_result) {
    while ($col = mysqli_fetch_assoc($columns_result)) {
        $column_names[] = $col['Field'];
    }
}

// Get messages with pagination
$messages = [];
if (tableExists($conn, 'contact_messages')) {
    // Use * to select all columns, which is safer than specifying them
    $query = "SELECT * FROM contact_messages $search_condition ORDER BY $datetime_column DESC LIMIT $offset, $records_per_page";

    $result = safeQuery($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Ensure is_read is set
            if (!isset($row['is_read'])) {
                $row['is_read'] = 0;
            }
            $messages[] = $row;
        }
    }
}

// Create contact form file if it doesn't exist
$contact_form_file = "../contact.php";
if (!file_exists($contact_form_file)) {
    $form_content = '<?php
// Include necessary files
include "includes/header.php";
include "includes/db.php";

$success_msg = "";
$error_msg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $name = isset($_POST["name"]) ? mysqli_real_escape_string($conn, $_POST["name"]) : "";
    $email = isset($_POST["email"]) ? mysqli_real_escape_string($conn, $_POST["email"]) : "";
    $phone = isset($_POST["phone"]) ? mysqli_real_escape_string($conn, $_POST["phone"]) : "";
    $subject = isset($_POST["subject"]) ? mysqli_real_escape_string($conn, $_POST["subject"]) : "";
    $message = isset($_POST["message"]) ? mysqli_real_escape_string($conn, $_POST["message"]) : "";
    
    // Simple validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Check if table exists
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE \'contact_messages\'");
        if (mysqli_num_rows($check_table) == 0) {
            $create_table = "CREATE TABLE contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                subject VARCHAR(255),
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            mysqli_query($conn, $create_table);
        }
        
        // Insert the message
        $insert_query = "INSERT INTO contact_messages (name, email, phone, subject, message, is_read) 
                        VALUES (\'$name\', \'$email\', \'$phone\', \'$subject\', \'$message\', 0)";
        
        if (mysqli_query($conn, $insert_query)) {
            $success_msg = "Thank you for your message. We will get back to you soon.";
            
            // Reset form fields
            $name = "";
            $email = "";
            $phone = "";
            $subject = "";
            $message = "";
        } else {
            $error_msg = "Sorry, there was an error sending your message. Please try again later.";
        }
    } else {
        $error_msg = "Please fix the following errors:<br>";
        foreach ($errors as $error) {
            $error_msg .= "- " . $error . "<br>";
        }
    }
}
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4">Contact Us</h1>
            <p class="lead">Have questions or feedback? We\'d love to hear from you.</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>Get In Touch</h4>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded p-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5>Our Address</h5>
                            <p class="mb-0">123 Caring Street, City Name<br>State, Country 12345</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded p-3">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5>Phone Number</h5>
                            <p class="mb-0">(123) 456-7890<br>(987) 654-3210</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded p-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5>Email Address</h5>
                            <p class="mb-0">info@orphanagecare.org<br>support@orphanagecare.org</p>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Follow Us</h5>
                    <div class="d-flex">
                        <a href="#" class="btn btn-outline-primary me-2" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary me-2" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary me-2" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4"><i class="fas fa-paper-plane me-2"></i>Send Us a Message</h4>
                    
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="contact.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : \'\'; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : \'\'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : \'\'; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : \'\'; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($message) ? htmlspecialchars($message) : \'\'; ?></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>';

    file_put_contents($contact_form_file, $form_content);
}

// Include admin header
include 'admin_header.php';
?>

    <!-- Content Container -->
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="page-title"><i class="fas fa-envelope me-2 text-primary"></i>Contact Messages</h2>
                    <?php if ($unread_count > 0): ?>
                        <a href="view_messages.php?action=mark_all_read" class="btn btn-outline-success">
                            <i class="fas fa-check-double me-2"></i>Mark All as Read (<?php echo $unread_count; ?>)
                        </a>
                    <?php endif; ?>
                </div>
                <p class="text-muted">View and manage contact messages from website visitors.</p>
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

        <div class="row">
            <!-- Message List -->
            <div class="col-md-4 col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-white">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <form action="view_messages.php" method="GET" class="d-flex">
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm" placeholder="Search messages..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-primary btn-sm" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <a href="view_messages.php" class="btn <?php echo empty($read_filter) ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                                    <a href="view_messages.php?read_status=unread<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                                       class="btn <?php echo ($read_filter == 'unread') ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                        Unread <?php if ($unread_count > 0): ?><span class="badge bg-danger"><?php echo $unread_count; ?></span><?php endif; ?>
                                    </a>
                                    <a href="view_messages.php?read_status=read<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                                       class="btn <?php echo ($read_filter == 'read') ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                        Read
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (count($messages) > 0): ?>
                            <?php foreach ($messages as $message): ?>
                                <a href="view_messages.php?id=<?php echo isset($message[$id_column]) ? $message[$id_column] : ''; ?>"
                                   class="list-group-item list-group-item-action <?php echo (isset($message['is_read']) && $message['is_read'] == 0) ? 'bg-light' : ''; ?>
                               <?php echo (isset($current_message) && isset($current_message[$id_column]) && isset($message[$id_column]) && $current_message[$id_column] == $message[$id_column]) ? 'active' : ''; ?>"
                                   style="border-left: 4px solid <?php echo (isset($message['is_read']) && $message['is_read'] == 0) ? '#0d6efd' : 'transparent'; ?>;">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 <?php echo (isset($message['is_read']) && $message['is_read'] == 0) ? 'fw-bold' : ''; ?>">
                                            <?php if (isset($message['is_read']) && $message['is_read'] == 0): ?>
                                                <i class="fas fa-circle text-primary me-1 fa-xs"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($message['name']); ?>
                                        </h6>
                                        <small><?php echo date('M d', strtotime($message[$datetime_column])); ?></small>
                                    </div>
                                    <div class="mb-1 text-truncate <?php echo (isset($message['is_read']) && $message['is_read'] == 0) ? 'fw-bold' : ''; ?>">
                                        <?php echo isset($message['subject']) ? htmlspecialchars($message['subject'] ?: 'No subject') : 'No subject'; ?>
                                    </div>
                                    <small class="<?php echo (isset($current_message) && isset($current_message[$id_column]) && isset($message[$id_column]) && $current_message[$id_column] == $message[$id_column]) ? 'text-white' : 'text-muted'; ?>">
                                        <?php echo htmlspecialchars(substr($message['message'], 0, 50)) . (strlen($message['message']) > 50 ? '...' : ''); ?>
                                        <?php if (isset($message['is_read']) && $message['is_read'] == 0): ?>
                                            <span class="badge bg-primary ms-1">New</span>
                                        <?php endif; ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center py-4">
                                <div class="mb-2">
                                    <i class="fas fa-envelope-open fa-3x text-muted"></i>
                                </div>
                                <h6 class="mb-1">No messages found</h6>
                                <?php if (!empty($search) || !empty($read_filter)): ?>
                                    <p class="text-muted small">No results match your search criteria.</p>
                                    <a href="view_messages.php" class="btn btn-sm btn-outline-secondary mt-2">Clear Filters</a>
                                <?php else: ?>
                                    <p class="text-muted small">You have not received any contact messages yet.</p>
                                    <button class="btn btn-sm btn-primary mt-2" id="addTestMessage">
                                        Add Test Message
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) .
                                                (!empty($search) ? '&search=' . urlencode($search) : '') .
                                                (!empty($read_filter) ? '&read_status=' . $read_filter : ''); ?>"
                                           aria-disabled="<?php echo ($page <= 1) ? 'true' : 'false'; ?>">Previous</a>
                                    </li>

                                    <?php
                                    // Show limited page numbers
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1<?php echo
                                                    (!empty($search) ? '&search=' . urlencode($search) : '') .
                                                    (!empty($read_filter) ? '&read_status=' . $read_filter : ''); ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i .
                                                    (!empty($search) ? '&search=' . urlencode($search) : '') .
                                                    (!empty($read_filter) ? '&read_status=' . $read_filter : ''); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $total_pages .
                                                    (!empty($search) ? '&search=' . urlencode($search) : '') .
                                                    (!empty($read_filter) ? '&read_status=' . $read_filter : ''); ?>"><?php echo $total_pages; ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) .
                                                (!empty($search) ? '&search=' . urlencode($search) : '') .
                                                (!empty($read_filter) ? '&read_status=' . $read_filter : ''); ?>"
                                           aria-disabled="<?php echo ($page >= $total_pages) ? 'true' : 'false'; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Message Detail -->
            <div class="col-md-8 col-lg-8">
                <?php if ($current_message): ?>
                    <div class="card shadow">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0">
                                <?php echo isset($current_message['subject']) ? htmlspecialchars($current_message['subject'] ?: 'No Subject') : 'No Subject'; ?>
                            </h5>
                            <div>
                                <?php if (isset($current_message['is_read']) && $current_message['is_read'] == 1): ?>
                                    <a href="view_messages.php?action=unread&id=<?php echo $current_message[$id_column]; ?>" class="btn btn-sm btn-outline-primary me-2" title="Mark as Unread">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="view_messages.php?action=read&id=<?php echo $current_message[$id_column]; ?>" class="btn btn-sm btn-outline-success me-2" title="Mark as Read">
                                        <i class="fas fa-envelope-open"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="#" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $current_message[$id_column]; ?>)" title="Delete Message">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($current_message['name']); ?></h6>
                                            <div class="text-muted small"><?php echo htmlspecialchars($current_message['email']); ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($current_message['phone'])): ?>
                                        <div class="mb-2 ms-5 ps-2">
                                            <i class="fas fa-phone text-muted me-1"></i>
                                            <?php echo htmlspecialchars($current_message['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="text-muted mb-2">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?php echo date('F j, Y - g:i A', strtotime($current_message[$datetime_column])); ?>
                                    </div>
                                    <div>
                                        <?php if (isset($current_message['is_read']) && $current_message['is_read'] == 1): ?>
                                            <span class="badge bg-success">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Unread</span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary">ID: <?php echo $current_message[$id_column]; ?></span>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="message-content">
                                <div class="mb-4">
                                    <h6 class="text-muted">Message:</h6>
                                    <div class="p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($current_message['message'])); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <?php if (!empty($current_message['email'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($current_message['email']); ?>" class="btn btn-primary">
                                        <i class="fas fa-reply me-1"></i>Reply via Email
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($current_message['phone'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($current_message['phone']); ?>" class="btn btn-outline-primary ms-2">
                                        <i class="fas fa-phone me-1"></i>Call
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-body text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-envelope-open fa-5x text-muted"></i>
                            </div>
                            <h5>No Message Selected</h5>
                            <p class="text-muted">Select a message from the list to view its details.</p>
                            <?php if (count($messages) == 0): ?>
                                <?php if (!empty($search) || !empty($read_filter)): ?>
                                    <a href="view_messages.php" class="btn btn-outline-secondary mt-2">Clear Search</a>
                                <?php else: ?>
                                    <div class="mt-3">
                                        <p>To verify the contact form is working correctly:</p>
                                        <ol class="text-start mx-auto" style="max-width: 400px;">
                                            <li>Make sure the contact form correctly inserts data into the database.</li>
                                            <li>Check that the contact form is properly configured.</li>
                                            <li>Try submitting a test message through the contact form.</li>
                                        </ol>
                                        <button class="btn btn-primary mt-2" id="createTestMessage">
                                            <i class="fas fa-plus me-1"></i> Create Test Message
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this message?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteLink" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Message Modal -->
    <div class="modal fade" id="testMessageModal" tabindex="-1" aria-labelledby="testMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testMessageModalLabel">Create Test Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="testMessageForm">
                        <div class="mb-3">
                            <label for="testName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="testName" value="Test User" required>
                        </div>
                        <div class="mb-3">
                            <label for="testEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="testEmail" value="test@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="testPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="testPhone" value="555-123-4567">
                        </div>
                        <div class="mb-3">
                            <label for="testSubject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="testSubject" value="Test Contact Message">
                        </div>
                        <div class="mb-3">
                            <label for="testMessage" class="form-label">Message</label>
                            <textarea class="form-control" id="testMessage" rows="4" required>This is a test message to verify that the contact messaging system is working properly. This was manually created by an administrator.</textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTestMessage">Create Test Message</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Apply filters when select boxes change
        function applyFilters() {
            const readFilter = document.getElementById('readFilter').value;
            const searchParam = '<?php echo !empty($search) ? "&search=" . urlencode($search) : ""; ?>';

            let url = 'view_messages.php?';
            if (readFilter) {
                url += 'read_status=' + readFilter;
            }
            url += searchParam;

            window.location.href = url;
        }

        // Handle delete confirmation
        function confirmDelete(messageId) {
            const confirmDeleteLink = document.getElementById('confirmDeleteLink');
            confirmDeleteLink.href = 'view_messages.php?action=delete&id=' + messageId;

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Force reload after actions to ensure proper view updates
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.alert-success')) {
                if (!sessionStorage.getItem('justUpdated')) {
                    sessionStorage.setItem('justUpdated', 'true');
                    setTimeout(() => {
                        window.location.reload(true);
                    }, 1500);
                } else {
                    sessionStorage.removeItem('justUpdated');
                }
            }

            // Handle test message creation
            const createTestBtn = document.getElementById('createTestMessage');
            if (createTestBtn) {
                createTestBtn.addEventListener('click', function() {
                    const testMessageModal = new bootstrap.Modal(document.getElementById('testMessageModal'));
                    testMessageModal.show();
                });
            }

            const addTestBtn = document.getElementById('addTestMessage');
            if (addTestBtn) {
                addTestBtn.addEventListener('click', function() {
                    const testMessageModal = new bootstrap.Modal(document.getElementById('testMessageModal'));
                    testMessageModal.show();
                });
            }

            const saveTestBtn = document.getElementById('saveTestMessage');
            if (saveTestBtn) {
                saveTestBtn.addEventListener('click', function() {
                    // Get form values
                    const name = document.getElementById('testName').value;
                    const email = document.getElementById('testEmail').value;
                    const phone = document.getElementById('testPhone').value;
                    const subject = document.getElementById('testSubject').value;
                    const message = document.getElementById('testMessage').value;

                    // Use direct redirect as a simpler approach
                    window.location.href = 'view_messages.php?add_test=true&name=' +
                        encodeURIComponent(name) + '&email=' + encodeURIComponent(email) +
                        '&phone=' + encodeURIComponent(phone) + '&subject=' +
                        encodeURIComponent(subject) + '&message=' + encodeURIComponent(message);
                });
            }
        });
    </script>

<?php
// Handle direct request to add a test message
if (isset($_GET['add_test']) && $_GET['add_test'] == 'true') {
    $name = isset($_GET['name']) ? mysqli_real_escape_string($conn, $_GET['name']) : 'Test User';
    $email = isset($_GET['email']) ? mysqli_real_escape_string($conn, $_GET['email']) : 'test@example.com';
    $phone = isset($_GET['phone']) ? mysqli_real_escape_string($conn, $_GET['phone']) : '555-123-4567';
    $subject = isset($_GET['subject']) ? mysqli_real_escape_string($conn, $_GET['subject']) : 'Test Contact Message';
    $message = isset($_GET['message']) ? mysqli_real_escape_string($conn, $_GET['message']) : 'This is a test message to verify that the contact messaging system is working properly.';

    // Use the actual field names from the table
    $fields = [];
    $values = [];

    // Always add these fields
    $fields[] = 'name';
    $values[] = "'$name'";
    $fields[] = 'email';
    $values[] = "'$email'";
    $fields[] = 'message';
    $values[] = "'$message'";

    // Add these if the columns exist
    if (in_array('subject', $column_names)) {
        $fields[] = 'subject';
        $values[] = "'$subject'";
    }
    if (in_array('phone', $column_names)) {
        $fields[] = 'phone';
        $values[] = "'$phone'";
    }
    if (in_array('is_read', $column_names)) {
        $fields[] = 'is_read';
        $values[] = "0";
    }
    if (in_array($datetime_column, $column_names)) {
        $fields[] = $datetime_column;
        $values[] = "NOW()";
    }

    $insert_test = "INSERT INTO contact_messages (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";

    if (mysqli_query($conn, $insert_test)) {
        // Redirect to avoid form resubmission
        header('Location: view_messages.php?success=1');
        exit;
    } else {
        $error_msg = "Error creating test message: " . mysqli_error($conn);
    }
}
?>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>