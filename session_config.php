<?php
// Centralized Session Configuration
// This file MUST be included BEFORE session_start() in all pages

// Set session lifetime to 24 hours (86400 seconds)
ini_set('session.gc_maxlifetime', 86400);

// Ensure session cookie lifetime is also 24 hours
ini_set('session.cookie_lifetime', 86400);

// Set secure session parameters BEFORE session_start()
session_set_cookie_params([
    'lifetime' => 86400,      // 24 hours
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => false,         // Set to true if using HTTPS
    'httponly' => true,        // Prevent JavaScript access
    'samesite' => 'Lax'        // CSRF protection
]);

// Use database for session storage for reliability
ini_set('session.save_handler', 'files');
ini_set('session.name', 'COMPLAINT_PORTAL_SESSION');

// Start the session
session_start();

// Verify session is still valid (check if user_id exists if they should be logged in)
if (isset($_SESSION['user_id'])) {
    // Regenerate session ID periodically for security (every 5 minutes)
    if (!isset($_SESSION['last_regen']) || time() - $_SESSION['last_regen'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = time();
    }
}
?>
