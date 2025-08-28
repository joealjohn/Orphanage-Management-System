<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Add New User";
$current_page = "manage_users";

// Include necessary files
include '../includes/session.php';
include '../includes/db.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Helper function to check if table exists
function tableExists($conn, $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($check && mysqli_num_rows($check) > 0);
}

// Check if users table exists
if (!tableExists($conn, 'users')) {
    // Create users table
    $create_table = "CREATE TABLE users (
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

    if (!mysqli_query($conn, $create_table)) {
        $error_msg = "Error creating users table: " . mysqli_error($conn);
    }
}

// Variables to store form data and messages
$name = "";
$email = "";
$phone = "";
$address = "";
$role = "user";
$error_msg = "";
$success_msg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : "";
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : "";
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Validate required fields
    $errors = [];

    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if ($role != 'admin' && $role != 'user') {
        $errors[] = "Invalid role selected";
    }

    // Check if email already exists
    $check_email = "SELECT user_id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check_email);

    if ($result && mysqli_num_rows($result) > 0) {
        $errors[] = "Email already in use. Please choose another email address.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (name, email, password, phone, address, role) 
                 VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $hashed_password, $phone, $address, $role);

        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            $success_msg = "User has been added successfully.";

            // Reset form
            $name = "";
            $email = "";
            $phone = "";
            $address = "";
            $role = "user";
        } else {
            $error_msg = "Error adding user: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Please fix the following errors: <ul>";
        foreach ($errors as $error) {
            $error_msg .= "<li>" . $error . "</li>";
        }
        $error_msg .= "</ul>";
    }
}

// Include admin header
include 'admin_header.php';
?>

    <!-- Content Container -->
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="page-title"><i class="fas fa-user-plus me-2"></i>Add New User</h2>
                    <a href="manage_users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Users List
                    </a>
                </div>
                <p class="text-muted">Add a new user to the system. You can create either regular users or administrators.</p>
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
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">User Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="add_user.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    <div class="form-text">Enter the user's full name</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    <div class="form-text">This will be used for login</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Show Password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 8 characters</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" title="Show Password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Re-enter password to confirm</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                    <div class="form-text">Optional contact number</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">User Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="user" <?php echo ($role == 'user') ? 'selected' : ''; ?>>Regular User</option>
                                        <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                    <div class="form-text">Select the user's role and permissions</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                                <div class="form-text">Optional user address</div>
                            </div>

                            <div class="border-top pt-3 mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Add User
                                </button>
                                <a href="manage_users.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>User Roles</h6>
                            <hr>
                            <p class="mb-2"><strong>Regular User:</strong> Can browse available children, submit adoption requests, and manage their own profile.</p>
                            <p class="mb-0"><strong>Administrator:</strong> Has full access to the admin panel and can manage all system data.</p>
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Important</h6>
                            <hr>
                            <p class="mb-0">Be careful when assigning administrator privileges. Administrators have complete access to manage users, children, adoption requests, and system settings.</p>
                        </div>

                        <div class="alert alert-success mb-0">
                            <h6 class="alert-heading"><i class="fas fa-user-shield me-2"></i>Password Security</h6>
                            <hr>
                            <p class="mb-0">Ensure passwords are secure and contain a mix of letters, numbers, and special characters. Passwords are stored securely using one-way encryption.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');

            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmPasswordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (password === confirmPassword) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity('Passwords do not match');
            }
        });
    </script>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>