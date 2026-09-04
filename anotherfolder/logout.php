<?php

// Start the current session
session_start();

// Remove all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Send the user back to the homepage
header("Location: index.php");
exit;

?>