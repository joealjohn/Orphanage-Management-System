<!-- Admin Footer -->
<footer class="admin-footer">
    <div class="container-fluid">
        <div class="text-muted">
            &copy; 2025 Orphanage Management System | All Rights Reserved
            <br>
        </div>
    </div>
</footer>
</div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Admin Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Sidebar
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminWrapper = document.getElementById('adminWrapper');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                adminWrapper.classList.toggle('sidebar-collapsed');

                // Store user preference
                if (adminWrapper.classList.contains('sidebar-collapsed')) {
                    localStorage.setItem('sidebar-collapsed', 'true');
                } else {
                    localStorage.setItem('sidebar-collapsed', 'false');
                }
            });
        }

        // Check for stored sidebar state
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            adminWrapper.classList.add('sidebar-collapsed');
        }

        // Enable Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
</body>
</html>