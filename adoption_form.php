<?php
// Include necessary files
include "includes/header.php";
include "includes/db.php";

$success_msg = "";
$error_msg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION["user_id"]) && isset($_POST["child_id"]) && isset($_POST["reason"])) {
        $user_id = $_SESSION["user_id"];
        $child_id = (int)$_POST["child_id"];
        $reason = mysqli_real_escape_string($conn, $_POST["reason"]);
        
        // Check if child exists and is available
        $check_query = "SELECT * FROM children WHERE child_id = $child_id AND status = 'available'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Check if user already has a pending request for this child
            $existing_query = "SELECT * FROM adoption_requests WHERE user_id = $user_id AND child_id = $child_id AND status = 'pending'";
            $existing_result = mysqli_query($conn, $existing_query);
            
            if (mysqli_num_rows($existing_result) == 0) {
                // Insert the adoption request
                $insert_query = "INSERT INTO adoption_requests (child_id, user_id, reason, request_date) VALUES ($child_id, $user_id, '$reason', NOW())";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success_msg = "Your adoption request has been submitted successfully.";
                } else {
                    $error_msg = "Error submitting request: " . mysqli_error($conn);
                }
            } else {
                $error_msg = "You already have a pending request for this child.";
            }
        } else {
            $error_msg = "Child not found or not available for adoption.";
        }
    } else {
        $error_msg = "Missing required information. Please fill out all fields.";
    }
}

// Get child details if specified in URL
$child_name = "";
$child_age = "";
$child_gender = "";
$child_id = isset($_GET["child_id"]) ? (int)$_GET["child_id"] : 0;

if ($child_id > 0) {
    $child_query = "SELECT name, age, gender FROM children WHERE child_id = $child_id AND status = 'available'";
    $child_result = mysqli_query($conn, $child_query);
    
    if ($child_result && mysqli_num_rows($child_result) > 0) {
        $child_data = mysqli_fetch_assoc($child_result);
        $child_name = $child_data["name"];
        $child_age = $child_data["age"];
        $child_gender = $child_data["gender"];
    } else {
        $error_msg = "Child not found or not available for adoption.";
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-heart me-2"></i>Adoption Request Form</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!isset($_SESSION["user_id"])): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You need to <a href="login.php" class="alert-link">login</a> to submit an adoption request.
                        </div>
                    <?php elseif (empty($success_msg)): ?>
                        <form action="adoption_form.php" method="POST">
                            <?php if ($child_id > 0 && !empty($child_name)): ?>
                                <div class="mb-4">
                                    <h5>Child Information:</h5>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo $child_name; ?></p>
                                    <p class="mb-1"><strong>Age:</strong> <?php echo $child_age; ?> years</p>
                                    <p><strong>Gender:</strong> <?php echo ucfirst($child_gender); ?></p>
                                    <input type="hidden" name="child_id" value="<?php echo $child_id; ?>">
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="child" class="form-label">Select Child</label>
                                    <select class="form-select" id="child" name="child_id" required>
                                        <option value="">-- Select a child --</option>
                                        <?php
                                        $children_query = "SELECT child_id, name, age, gender FROM children WHERE status = 'available' ORDER BY name";
                                        $children_result = mysqli_query($conn, $children_query);
                                        
                                        if ($children_result && mysqli_num_rows($children_result) > 0) {
                                            while ($child = mysqli_fetch_assoc($children_result)) {
                                                echo "<option value=\"" . $child["child_id"] . "\">" . $child["name"] . " (" . $child["age"] . " years, " . ucfirst($child["gender"]) . ")</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Adoption</label>
                                <textarea class="form-control" id="reason" name="reason" rows="5" placeholder="Please explain why you would like to adopt this child..." required></textarea>
                                <div class="form-text">Please provide detailed information about your reasons for adoption and your ability to provide a loving home.</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="agree" required>
                                <label class="form-check-label" for="agree">
                                    I understand that submitting this form is the first step in the adoption process, and that additional screening and approval will be required.
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Submit Adoption Request</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="text-center">
                        <a href="available_children.php" class="btn btn-outline-primary">View Available Children</a>
                        <?php if (isset($_SESSION["user_id"])): ?>
                            <a href="my_requests.php" class="btn btn-outline-secondary">My Adoption Requests</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>