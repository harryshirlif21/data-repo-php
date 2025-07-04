<?php
// logout.php
// This script handles logging out a user by destroying their session.

// Start the PHP session
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
// This effectively logs the user out.
session_destroy();

// Redirect the user to the login page after logging out
header("location: index.html");
exit; // Ensure that no further code is executed after the redirect
?>
