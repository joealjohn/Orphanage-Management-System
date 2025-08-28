<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Include necessary files
include '../includes/session.php';
include '../includes/db.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Check if email is already in use by another user
    $check_email_query = "SELECT * FROM users WHERE email = '$email' AND user_id != $user_id";
    $check_email_result = mysqli_query($conn, $check_email_query);

    if (mysqli_num_rows($check_email_result) > 0) {
        $error = "Email address is already in use by another user.";
    } else {
        // Update user information
        $update_query = "UPDATE users SET name = '$name', email = '$email' WHERE user_id = $user_id";

        if (mysqli_query($conn, $update_query)) {
            // Update session variables
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;

            $success = "Profile information updated successfully.";

            // Check if password change is requested
            if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
                // Verify current password
                $password_query = "SELECT password FROM users WHERE user_id = $user_id";
                $password_result = mysqli_query($conn, $password_query);
                $user_data = mysqli_fetch_assoc($password_result);

                if ($user_data['password'] == $current_password) {
                    // Check if new passwords match
                    if ($new_password == $confirm_password) {
                        // Update password
                        $password_update_query = "UPDATE users SET password = '$new_password' WHERE user_id = $user_id";

                        if (mysqli_query($conn, $password_update_query)) {
                            $success .= " Password has been changed.";
                        } else {
                            $error = "Error updating password: " . mysqli_error($conn);
                        }
                    } else {
                        $error = "New passwords do not match.";
                    }
                } else {
                    $error = "Current password is incorrect.";
                }
            }
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

// Redirect back to dashboard with status
if (!empty($success)) {
    header("Location: dashboard.php?tab=profile&success=" . urlencode($success));
    exit();
} else if (!empty($error)) {
    header("Location: dashboard.php?tab=profile&error=" . urlencode($error));
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>