<?php
// Set page title and current page for active menu
global $conn;
$page_title = "Login";
$current_page = "login";

include 'includes/session.php';
include 'includes/db.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    // If there's a redirect URL stored in session, use that
    if (isset($_SESSION['redirect_url'])) {
        $redirect = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header("Location: $redirect");
        exit();
    } else {
        // Otherwise redirect to dashboard
        redirectToDashboard();
    }
}

$error = "";
$message = "";

// Check if there's a message to display
if (isset($_GET['msg']) && $_GET['msg'] == 'login_required') {
    $message = "Please log in to access the contact form.";
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query database for user
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password' AND status = 'active'";
    $result = mysqli_query($conn, $query);

    // Check if user exists and is active
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // If there's a redirect URL stored in session, use that
        if (isset($_SESSION['redirect_url'])) {
            $redirect = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']);
            header("Location: $redirect");
            exit();
        } else {
            // Otherwise redirect to dashboard
            redirectToDashboard();
        }
    } else {
        // Query to check if account is blocked
        $blocked_query = "SELECT * FROM users WHERE email = '$email' AND status = 'blocked'";
        $blocked_result = mysqli_query($conn, $blocked_query);

        if (mysqli_num_rows($blocked_result) == 1) {
            $error = "Your account has been blocked. Please contact the administrator.";
        } else {
            $error = "Invalid email or password. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

    <style>
        /* Background overlay styling */
        .auth-background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center;
            background-size: cover;
            filter: blur(0px);
            z-index: -1;
            transition: filter 1s ease;
        }

        .auth-background-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
        }

        /* Container styling */
        .auth-container {
            padding: 5rem 0;
            position: relative;
            z-index: 1;
        }

        /* Auth card styling */
        .auth-card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            margin: 0 auto;
        }

        .auth-header {
            background-color: #28a745;
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .auth-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .auth-header i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .auth-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group i.icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: #6c757d;
        }

        .auth-input {
            width: 100%;
            padding: 10px 15px 10px 45px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 1rem;
        }

        .auth-input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }

        .auth-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .auth-btn:hover {
            background-color: #218838;
        }

        .auth-footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #eee;
            background-color: #f8f9fa;
        }

        .auth-footer p {
            margin: 0;
            color: #6c757d;
        }

        .auth-footer a {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>

    <!-- Background overlay div -->
    <div class="auth-background-overlay" id="authBackground"></div>

    <!-- Auth Container -->
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="auth-card">
                        <div class="auth-header">
                            <i class="fas fa-user-circle"></i>
                            <h2>Login to Account</h2>
                        </div>

                        <div class="auth-body">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <div><?php echo $error; ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($message)): ?>
                                <div class="alert alert-info d-flex align-items-center" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div><?php echo $message; ?></div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="login.php">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <i class="fas fa-envelope icon"></i>
                                    <input type="email" class="form-control auth-input" id="email" name="email" placeholder="Enter your email" required>
                                </div>

                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <i class="fas fa-lock icon"></i>
                                    <input type="password" class="form-control auth-input" id="password" name="password" placeholder="Enter your password" required>
                                </div>

                                <button type="submit" class="auth-btn">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login
                                </button>
                            </form>
                        </div>

                        <div class="auth-footer">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add blur effect to background after page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const background = document.getElementById('authBackground');
                background.style.filter = 'blur(5px)';
            }, 300);
        });
    </script>

<?php include 'includes/footer.php'; ?>