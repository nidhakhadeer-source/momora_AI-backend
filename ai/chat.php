<?php
// =============================================================================
// Momora AI - Conversational Guidance Endpoint (Backward Compatibility Wrapper)
// Routes POST requests to ai_assistant.php and GET requests to chat_history.php
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/ai_assistant.php';
} else {
    require __DIR__ . '/chat_history.php';
}
