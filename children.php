<?php
// Set page title and current page for active menu
global $conn;
$page_title = "Our Children";
$current_page = "children";

include 'includes/session.php';
include 'includes/db.php';

// Get filter parameters
$age = isset($_GET['age']) ? $_GET['age'] : '';
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'available';
$education = isset($_GET['education']) ? $_GET['education'] : '';

// Build query based on filters
$query = "SELECT * FROM children WHERE 1=1";

if (!empty($age)) {
    $query .= " AND age = " . (int)$age;
}

if (!empty($gender)) {
    $query .= " AND gender = '" . mysqli_real_escape_string($conn, $gender) . "'";
}

if (!empty($status)) {
    $query .= " AND status = '" . mysqli_real_escape_string($conn, $status) . "'";
}

if (!empty($education)) {
    $query .= " AND education_level = '" . mysqli_real_escape_string($conn, $education) . "'";
}

$result = mysqli_query($conn, $query);

// Get distinct education levels for filter
$edu_query = "SELECT DISTINCT education_level FROM children ORDER BY education_level";
$edu_result = mysqli_query($conn, $edu_query);

include 'includes/header.php';
?>

    <!-- Children Hero Section -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-child me-2"></i>Our Children</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Our Children</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Children Content -->
    <section class="children-section py-5">
        <div class="container">
            <div class="row">
                <!-- Filters -->
                <div class="col-lg-3 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Children</h5>
                        </div>
                        <div class="card-body">
                            <form id="filter-form" method="GET" action="children.php">
                                <div class="mb-3">
                                    <label class="form-label">Age</label>
                                    <select class="form-select" name="age">
                                        <option value="">All Ages</option>
                                        <?php for($i=1; $i<=18; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($age == $i) ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> years
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">All Genders</option>
                                        <option value="male" <?php echo ($gender == 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($gender == 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($gender == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">All Status</option>
                                        <option value="available" <?php echo ($status == 'available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="adopted" <?php echo ($status == 'adopted') ? 'selected' : ''; ?>>Adopted</option>
                                        <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending Adoption</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Education Level</label>
                                    <select class="form-select" name="education">
                                        <option value="">All Education Levels</option>
                                        <?php while($edu_row = mysqli_fetch_assoc($edu_result)): ?>
                                            <option value="<?php echo $edu_row['education_level']; ?>"
                                                <?php echo ($education == $edu_row['education_level']) ? 'selected' : ''; ?>>
                                                <?php echo $edu_row['education_level']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                    <button type="button" id="clear-filter" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-2"></i>Clear Filters
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Information Box -->
                    <div class="info-box mt-4">
                        <h5><i class="fas fa-info-circle me-2"></i>How to Help</h5>
                        <p>Interested in adoption or supporting our children? Register an account or contact us to learn more.</p>
                        <div class="d-grid">
                            <?php if (!isLoggedIn()): ?>
                                <a href="register.php" class="btn btn-outline-success">
                                    <i class="fas fa-user-plus me-2"></i>Register Now
                                </a>
                            <?php else: ?>
                                <a href="contact.php" class="btn btn-outline-success">
                                    <i class="fas fa-envelope me-2"></i>Contact Us
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <div class="info-box mt-4">
                            <h5><i class="fas fa-hand-holding-heart me-2 text-success"></i>Ready to Adopt?</h5>
                            <p>If you are ready to provide a loving home for one of our children, we encourage you to start the adoption process.</p>
                            <div class="d-grid">
                                <a href="users/request_adoption.php" class="btn btn-success">
                                    <i class="fas fa-heart me-2"></i>Begin Adoption Process
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Children List -->
                <div class="col-lg-9">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>
                            <?php
                            $count = mysqli_num_rows($result);
                            echo "<i class='fas fa-child me-2 text-success'></i>$count Children Found";
                            ?>
                        </h3>
                        <?php if (isLoggedIn()): ?>
                            <a href="users/view_children.php" class="btn btn-success">
                                <i class="fas fa-heart me-1"></i> Request Adoption
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($count == 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No children found matching your filter criteria.
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="child-card">
                                    <div class="child-img">
                                        <?php if (!empty($row['photo']) && file_exists($row['photo'])): ?>
                                            <img src="<?php echo $row['photo']; ?>" alt="<?php echo $row['name']; ?>">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/300x200?text=Child+Photo" alt="<?php echo $row['name']; ?>">
                                        <?php endif; ?>

                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                    </div>
                                    <div class="child-info">
                                        <h4><?php echo $row['name']; ?></h4>
                                        <div class="child-details">
                                            <p>
                                                <i class="fas fa-birthday-cake me-2"></i>
                                                Age: <?php echo $row['age']; ?> years
                                            </p>
                                            <p>
                                                <i class="fas fa-venus-mars me-2"></i>
                                                Gender: <?php echo ucfirst($row['gender']); ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-book me-2"></i>
                                                Education: <?php echo $row['education_level']; ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                Admitted: <?php echo date('M d, Y', strtotime($row['admission_date'])); ?>
                                            </p>
                                        </div>

                                        <?php if (isLoggedIn() && $row['status'] == 'available'): ?>
                                            <div class="d-grid mt-3">
                                                <a href="users/request_adoption.php?child_id=<?php echo $row['child_id']; ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-heart me-1"></i> Request Adoption
                                                </a>
                                            </div>
                                        <?php elseif (!isLoggedIn() && $row['status'] == 'available'): ?>
                                            <div class="d-grid mt-3">
                                                <a href="login.php" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-sign-in-alt me-1"></i> Login to Request
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Child Stories Section -->
    <section class="child-stories-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title"><i class="fas fa-book-open text-success me-2"></i>Success Stories</h2>
            <div class="section-divider mx-auto mb-5"></div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="story-card">
                        <div class="story-header">
                            <img src="https://randomuser.me/api/portraits/women/56.jpg" alt="Child Story">
                            <div class="story-title">
                                <h4>Emma's Journey</h4>
                                <p>From orphanage to medical school</p>
                            </div>
                        </div>
                        <div class="story-content">
                            <p>Emma came to us at age 7 after losing her parents in a tragic accident. Despite her trauma, her resilience was remarkable. With proper care, education, and emotional support, she thrived in her studies. Today, Emma is 23 and attending medical school, determined to help others as she was once helped.</p>
                            <a href="#" class="btn btn-sm btn-outline-success">Read More</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="story-card">
                        <div class="story-header">
                            <img src="https://randomuser.me/api/portraits/men/22.jpg" alt="Child Story">
                            <div class="story-title">
                                <h4>Michael's Family</h4>
                                <p>Finding a forever home at last</p>
                            </div>
                        </div>
                        <div class="story-content">
                            <p>Michael spent his first 5 years moving between foster homes until he arrived at our orphanage. His behavioral challenges made adoption difficult, but our dedicated team worked with him for two years. The Johnsons saw beyond his difficulties and welcomed him into their family. Now 12, Michael is thriving in his forever home.</p>
                            <a href="#" class="btn btn-sm btn-outline-success">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section py-5">
        <div class="container text-center">
            <h2 class="mb-4 text-white"><i class="fas fa-hand-holding-heart me-2"></i> Ready to Make a Difference?</h2>
            <p class="lead text-white mb-4">Become a part of our mission to provide loving homes for these children</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <?php if (!isLoggedIn()): ?>
                        <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                            <a href="register.php" class="btn btn-light btn-lg px-4 me-md-2 rounded-pill">
                                <i class="fas fa-user-plus me-2"></i>Register Now
                            </a>
                            <a href="login.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                            <a href="users/view_children.php" class="btn btn-light btn-lg px-4 me-md-2 rounded-pill">
                                <i class="fas fa-heart me-2"></i>Request Adoption
                            </a>
                            <a href="users/register_child.php" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                                <i class="fas fa-user-plus me-2"></i>Register a Child
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('clear-filter').addEventListener('click', function() {
            window.location.href = 'children.php';
        });
    </script>

<?php include 'includes/footer.php'; ?>