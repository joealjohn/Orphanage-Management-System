<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "Add New Child";
$current_page = "manage_children";

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

// Create children table if it doesn't exist
if (!tableExists($conn, 'children')) {
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

// Variables to store form data and messages
$name = "";
$age = "";
$gender = "";
$education_level = "";
$health_status = "";
$status = "available";
$error_msg = "";
$success_msg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $age = (int)$_POST['age'];
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $education_level = mysqli_real_escape_string($conn, $_POST['education_level']);
    $health_status = mysqli_real_escape_string($conn, $_POST['health_status']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validate required fields
    $errors = [];

    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if ($age <= 0 || $age > 18) {
        $errors[] = "Please enter a valid age between 1 and 18";
    }

    if (empty($gender) || ($gender != 'male' && $gender != 'female')) {
        $errors[] = "Please select a valid gender";
    }

    if (empty($status) || !in_array($status, ['available', 'adopted', 'pending'])) {
        $errors[] = "Please select a valid status";
    }

    // Handle file upload
    $photo_path = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['photo']['name'];
        $file_size = $_FILES['photo']['size'];
        $file_tmp = $_FILES['photo']['tmp_name'];
        $tmp = explode('.', $file_name);
        $file_extension = strtolower(end($tmp));

        // Validate file extension
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed";
        }

        // Validate file size (max 5MB)
        if ($file_size > 5000000) {
            $errors[] = "File size must be less than 5MB";
        }

        // Upload file if no errors
        if (empty($errors)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = "../uploads/children";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename to prevent overwriting
            $new_file_name = uniqid('child_') . '.' . $file_extension;
            $upload_path = $upload_dir . '/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $photo_path = "uploads/children/" . $new_file_name;
            } else {
                $errors[] = "Error uploading file";
            }
        }
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO children (name, age, gender, education_level, health_status, status, photo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sisssss', $name, $age, $gender, $education_level, $health_status, $status, $photo_path);

        if (mysqli_stmt_execute($stmt)) {
            $child_id = mysqli_insert_id($conn);
            $success_msg = "Child information has been added successfully.";

            // Reset form
            $name = "";
            $age = "";
            $gender = "";
            $education_level = "";
            $health_status = "";
            $status = "available";
        } else {
            $error_msg = "Error adding child information: " . mysqli_error($conn);
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
                    <h2 class="page-title"><i class="fas fa-plus-circle me-2"></i>Add New Child</h2>
                    <a href="manage_children.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Children List
                    </a>
                </div>
                <p class="text-muted">Add a new child to the system with their details and photo.</p>
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
                <h5 class="card-title mb-0">Child Information</h5>
            </div>
            <div class="card-body">
                <form action="add_child.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    <div class="form-text">Enter the child's full name</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="age" class="form-label">Age <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="age" name="age" min="1" max="18" value="<?php echo $age; ?>" required>
                                    <div class="form-text">Enter age in years (1-18)</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="" <?php echo empty($gender) ? 'selected' : ''; ?>>Select gender</option>
                                        <option value="male" <?php echo $gender == 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo $gender == 'female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="education_level" class="form-label">Education Level</label>
                                    <select class="form-select" id="education_level" name="education_level">
                                        <option value="" <?php echo empty($education_level) ? 'selected' : ''; ?>>Select education level</option>
                                        <option value="None" <?php echo $education_level == 'None' ? 'selected' : ''; ?>>None</option>
                                        <option value="Preschool" <?php echo $education_level == 'Preschool' ? 'selected' : ''; ?>>Preschool</option>
                                        <option value="Primary" <?php echo $education_level == 'Primary' ? 'selected' : ''; ?>>Primary School</option>
                                        <option value="Middle School" <?php echo $education_level == 'Middle School' ? 'selected' : ''; ?>>Middle School</option>
                                        <option value="High School" <?php echo $education_level == 'High School' ? 'selected' : ''; ?>>High School</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="available" <?php echo $status == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="adopted" <?php echo $status == 'adopted' ? 'selected' : ''; ?>>Adopted</option>
                                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                    <div class="form-text">Current status of the child</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="health_status" class="form-label">Health Status</label>
                                <textarea class="form-control" id="health_status" name="health_status" rows="4"><?php echo htmlspecialchars($health_status); ?></textarea>
                                <div class="form-text">Provide any health information or special needs</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Photo Upload</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <div id="imagePreview" class="mb-3" style="display: none;">
                                            <img id="preview" class="img-fluid rounded" style="max-height: 200px;" alt="Image Preview">
                                        </div>
                                        <div id="defaultPreview" class="mb-3">
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <i class="fas fa-child fa-5x text-secondary"></i>
                                            </div>
                                        </div>
                                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*" onchange="previewImage(this)">
                                        <div class="form-text">Upload a photo of the child (optional)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Child Information
                        </button>
                        <a href="manage_children.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Function to preview image before upload
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            const defaultPreview = document.getElementById('defaultPreview');

            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    defaultPreview.style.display = 'none';
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                imagePreview.style.display = 'none';
                defaultPreview.style.display = 'block';
            }
        }
    </script>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>