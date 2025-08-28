/**
 * Custom JavaScript for Orphanage Management System
 * Last updated: 2025-08-24 05:58:26 by joealjohn
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Form validation for all forms with "needs-validation" class
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });

    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    if (password && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        });

        // Also check when changing the main password field
        password.addEventListener('input', function() {
            if (password.value !== confirmPassword.value && confirmPassword.value !== '') {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    }

    // File input preview for image uploads
    const fileInputs = document.querySelectorAll('.custom-file-input');

    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const fileName = e.target.files[0].name;
            const nextSibling = e.target.nextElementSibling;
            if (nextSibling) {
                nextSibling.innerText = fileName;
            }

            // Preview image if there's a preview element
            const preview = document.getElementById(e.target.getAttribute('data-preview'));
            if (preview && e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    });

    // Filter functionality for children listing
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Build query string from form fields
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();

            for (const pair of formData.entries()) {
                if (pair[1] !== '') {
                    params.append(pair[0], pair[1]);
                }
            }

            window.location.search = params.toString();
        });
    }

    // Clear filter button functionality
    const clearFilterBtn = document.getElementById('clear-filter');
    if (clearFilterBtn) {
        clearFilterBtn.addEventListener('click', function() {
            window.location.search = '';
        });
    }

    // Counter animation for statistics
    const counters = document.querySelectorAll('.counter');
    const speed = 200; // Animation speed in milliseconds

    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target') || +counter.innerText;
            const count = +counter.innerText;

            // Lower animation speed for lower numbers
            const inc = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + inc);
                setTimeout(updateCount, 1);
            } else {
                counter.innerText = target;
            }
        };

        // Start animation when counter is in viewport
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCount();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        observer.observe(counter);
    });

    // Back to top button
    const backToTopButton = document.getElementById('back-to-top');

    if (backToTopButton) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });

        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Admin sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
            document.querySelector('.sidebar').classList.toggle('toggled');
        });
    }

    // Confirm action function (delete, etc.)
    window.confirmAction = function(message, callback) {
        if (confirm(message || 'Are you sure you want to perform this action?')) {
            if (typeof callback === 'function') {
                callback();
            }
            return true;
        }
        return false;
    };

    // Preloader
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        window.addEventListener('load', function() {
            preloader.style.opacity = '0';
            setTimeout(function() {
                preloader.style.display = 'none';
            }, 500);
        });
    }

    // Add back to top button if it doesn't exist
    if (!backToTopButton && document.body.scrollHeight > window.innerHeight * 1.5) {
        const btn = document.createElement('a');
        btn.id = 'back-to-top';
        btn.className = 'back-to-top';
        btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        btn.href = '#';
        btn.title = 'Back to top';

        document.body.appendChild(btn);

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        });
    }

    // Initialize date pickers
    const datePickers = document.querySelectorAll('.datepicker');
    if (datePickers.length > 0) {
        datePickers.forEach(picker => {
            new Datepicker(picker, {
                format: 'yyyy-mm-dd',
                autohide: true
            });
        });
    }

    // Handle message modal events if on admin message page
    const messageViewButtons = document.querySelectorAll('.view-message');
    if (messageViewButtons.length > 0) {
        messageViewButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const email = this.getAttribute('data-email');
                const subject = this.getAttribute('data-subject');
                const message = this.getAttribute('data-message');
                const date = this.getAttribute('data-date');
                const status = this.getAttribute('data-status');

                document.getElementById('message-name').textContent = name;

                const emailElement = document.getElementById('message-email');
                emailElement.textContent = email;
                emailElement.href = 'mailto:' + email;

                document.getElementById('message-subject').textContent = subject;
                document.getElementById('message-date').textContent = date;
                document.getElementById('message-content').textContent = message;

                const statusElement = document.getElementById('message-status');
                if (status === 'unread') {
                    statusElement.innerHTML = '<span class="badge bg-danger">Unread</span>';
                    document.getElementById('mark-read-btn').style.display = 'block';
                    document.getElementById('mark-read-btn').href = 'view_messages.php?read_id=' + id;
                } else {
                    statusElement.innerHTML = '<span class="badge bg-secondary">Read</span>';
                    document.getElementById('mark-read-btn').style.display = 'none';
                }

                document.getElementById('delete-btn').href = 'view_messages.php?delete_id=' + id;
            });
        });
    }

    // Enable multilevel dropdown menus if present
    document.querySelectorAll('.dropdown-menu a.dropdown-toggle').forEach(function(element) {
        element.addEventListener('click', function(e) {
            let parentDropdown = this.closest('.dropdown-menu');
            if (this.nextElementSibling) {
                e.preventDefault();
                e.stopPropagation();

                let submenu = this.nextElementSibling;
                if (submenu.classList.contains('show')) {
                    submenu.classList.remove('show');
                } else {
                    // Close all other submenus
                    let allSubmenus = parentDropdown.querySelectorAll('.dropdown-menu');
                    allSubmenus.forEach(el => el.classList.remove('show'));

                    // Open this submenu
                    submenu.classList.add('show');
                }
            }
        });
    });

    // Add notification system
    const notifyUser = function(message, type = 'info', duration = 5000) {
        const notificationArea = document.getElementById('notification-area');

        if (!notificationArea) {
            // Create notification area if it doesn't exist
            const area = document.createElement('div');
            area.id = 'notification-area';
            area.className = 'notification-area';
            document.body.appendChild(area);
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        notification.innerHTML = `
            <div class="notification-content">
                ${type === 'success' ? '<i class="fas fa-check-circle"></i>' :
            type === 'error' ? '<i class="fas fa-exclamation-circle"></i>' :
                type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' :
                    '<i class="fas fa-info-circle"></i>'}
                <span>${message}</span>
            </div>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;

        document.getElementById('notification-area').appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto-remove notification after duration
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, duration);

        // Close button functionality
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    };

    // Make notification function globally available
    window.notifyUser = notifyUser;

    // Check for URL parameters that might indicate actions happened
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        notifyUser(decodeURIComponent(urlParams.get('success')), 'success');
    }
    if (urlParams.has('error')) {
        notifyUser(decodeURIComponent(urlParams.get('error')), 'error');
    }
});