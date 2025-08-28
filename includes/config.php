<?php
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
?>