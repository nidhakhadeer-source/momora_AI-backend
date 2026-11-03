<?php
// =============================================================================
// Momora AI - Notifications Endpoint (Backward Compatibility Wrapper)
// Routes POST requests to notification_mark_all_read.php and GET to notification_fetch.php
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/notification_mark_all_read.php';
} else {
    require __DIR__ . '/notification_fetch.php';
}
