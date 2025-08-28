<?php
// Set base path for includes
global $conn;
$base_path = "../";

// Set page title and current page for active menu
$page_title = "View Child";
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

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_children.php");
    exit();
}

$child_id = (int)$_GET['id'];

// Fetch child data
$query = "SELECT * FROM children WHERE child_id = $child_id";
$result = safeQuery($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: manage_children.php");
    exit();
}

$child = mysqli_fetch_assoc($result);

// Fetch adoption requests for this child if table exists
$adoption_requests = [];
if (tableExists($conn, 'adoption_requests') && tableExists($conn, 'users')) {
    $adoption_query = "SELECT ar.*, u.name as user_name, u.email
                      FROM adoption_requests ar
                      JOIN users u ON ar.user_id = u.user_id
                      WHERE ar.child_id = $child_id
                      ORDER BY ar.request_date DESC";
    $adoption_result = safeQuery($conn, $adoption_query);

    if ($adoption_result) {
        while ($row = mysqli_fetch_assoc($adoption_result)) {
            $adoption_requests[] = $row;
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
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="page-title"><i class="fas fa-child me-2"></i>Child Information</h2>
                    <div>
                        <a href="edit_child.php?id=<?php echo $child_id; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i>Edit Child
                        </a>
                        <a href="manage_children.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to List
                        </a>
                    </div>
                </div>
                <p class="text-muted">View detailed information about the child.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($child['name']); ?></h5>
                            <div>
                                <?php if ($child['status'] == 'available'): ?>
                                    <span class="badge bg-success">Available</span>
                                <?php elseif ($child['status'] == 'adopted'): ?>
                                    <span class="badge bg-info">Adopted</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">ID:</dt>
                                    <dd class="col-sm-8"><?php echo $child['child_id']; ?></dd>

                                    <dt class="col-sm-4">Age:</dt>
                                    <dd class="col-sm-8"><?php echo $child['age']; ?> years</dd>

                                    <dt class="col-sm-4">Gender:</dt>
                                    <dd class="col-sm-8"><?php echo ucfirst($child['gender']); ?></dd>

                                    <dt class="col-sm-4">Education:</dt>
                                    <dd class="col-sm-8"><?php echo !empty($child['education_level']) ? htmlspecialchars($child['education_level']) : 'Not specified'; ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Status:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($child['status'] == 'available'): ?>
                                            <span class="text-success">Available for adoption</span>
                                        <?php elseif ($child['status'] == 'adopted'): ?>
                                            <span class="text-info">Adopted</span>
                                        <?php else: ?>
                                            <span class="text-warning">Pending adoption</span>
                                        <?php endif; ?>
                                    </dd>

                                    <dt class="col-sm-4">Admitted:</dt>
                                    <dd class="col-sm-8"><?php echo date('F j, Y', strtotime($child['admission_date'])); ?></dd>

                                    <dt class="col-sm-4">Time at Center:</dt>
                                    <dd class="col-sm-8">
                                        <?php
                                        $admission_date = new DateTime($child['admission_date']);
                                        $current_date = new DateTime();
                                        $interval = $admission_date->diff($current_date);

                                        if ($interval->y > 0) {
                                            echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ', ';
                                        }
                                        echo $interval->m . ' month' . ($interval->m != 1 ? 's' : '');
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>

                        <?php if (!empty($child['health_status'])): ?>
                            <div class="mt-3">
                                <h6>Health Status</h6>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($child['health_status'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex gap-2">
                            <a href="edit_child.php?id=<?php echo $child_id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>Edit Information
                            </a>
                            <?php if ($child['status'] != 'adopted'): ?>
                                <a href="manage_children.php?action=status&id=<?php echo $child_id; ?>&status=adopted" class="btn btn-sm btn-outline-info" onclick="return confirm('Are you sure you want to mark this child as adopted? This will reject all pending adoption requests.');">
                                    <i class="fas fa-home me-1"></i>Mark as Adopted
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Adoption Requests Section -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Adoption Requests</h5>
                        <span class="badge bg-secondary"><?php echo count($adoption_requests); ?> requests</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($adoption_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Requester</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($adoption_requests as $request): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($request['user_name']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($request['email']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <?php if ($request['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($request['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="adoption_requests.php#request<?php echo $request['request_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="mb-2">
                                    <i class="fas fa-heart fa-3x text-muted"></i>
                                </div>
                                <p class="text-muted">No adoption requests have been received for this child yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Child Photo -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Photo</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($child['photo']) && file_exists("../" . $child['photo'])): ?>
                            <img src="<?php echo '../' . htmlspecialchars($child['photo']); ?>" alt="<?php echo htmlspecialchars($child['name']); ?>" class="img-fluid rounded">
                        <?php else: ?>
                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="height: 300px;">
                                <div class="text-center">
                                    <i class="fas fa-child fa-5x text-secondary mb-3"></i>
                                    <p class="text-muted">No photo available</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="edit_child.php?id=<?php echo $child_id; ?>#photo" class="btn btn-sm btn-outline-secondary w-100">
                            <?php if (!empty($child['photo'])): ?>
                                <i class="fas fa-camera me-1"></i>Update Photo
                            <?php else: ?>
                                <i class="fas fa-camera me-1"></i>Add Photo
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="edit_child.php?id=<?php echo $child_id; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Edit Information
                            </a>

                            <?php if ($child['status'] == 'available'): ?>
                                <a href="manage_children.php?action=status&id=<?php echo $child_id; ?>&status=adopted" class="btn btn-info" onclick="return confirm('Are you sure you want to mark this child as adopted?');">
                                    <i class="fas fa-home me-2"></i>Mark as Adopted
                                </a>
                            <?php elseif ($child['status'] == 'adopted'): ?>
                                <a href="manage_children.php?action=status&id=<?php echo $child_id; ?>&status=available" class="btn btn-success" onclick="return confirm('Are you sure you want to mark this child as available?');">
                                    <i class="fas fa-check-circle me-2"></i>Mark as Available
                                </a>
                            <?php elseif ($child['status'] == 'pending'): ?>
                                <div class="btn-group w-100">
                                    <a href="manage_children.php?action=status&id=<?php echo $child_id; ?>&status=available" class="btn btn-success" onclick="return confirm('Are you sure you want to mark this child as available?');">
                                        <i class="fas fa-check-circle me-1"></i>Mark Available
                                    </a>
                                    <a href="manage_children.php?action=status&id=<?php echo $child_id; ?>&status=adopted" class="btn btn-info" onclick="return confirm('Are you sure you want to mark this child as adopted?');">
                                        <i class="fas fa-home me-1"></i>Mark Adopted
                                    </a>
                                </div>
                            <?php endif; ?>

                            <a href="#" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $child_id; ?>, '<?php echo htmlspecialchars($child['name']); ?>');">
                                <i class="fas fa-trash-alt me-2"></i>Delete Record
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Child Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the record for <strong id="childName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone. All data associated with this child will be permanently removed.</p>

                    <?php if (count($adoption_requests) > 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>This child has associated adoption requests. Deleting this record will also remove access to these requests.
                        </div>
                    <?php endif; ?>

                    <?php if ($child['status'] == 'adopted'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>This child is marked as adopted. Are you sure you want to delete their record?
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Delete Record</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to handle delete confirmation
        function confirmDelete(childId, childName) {
            const modal = document.getElementById('deleteModal');
            document.getElementById('childName').textContent = childName;
            document.getElementById('confirmDelete').href = 'manage_children.php?action=delete&id=' + childId;

            const deleteModal = new bootstrap.Modal(modal);
            deleteModal.show();

            return false; // Prevent default link action
        }
    </script>

<?php
// Update the footer date and username
include 'admin_footer.php';
?>