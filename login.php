<?php
// login_process.php
// This file handles user login authentication and redirection based on user role.

session_start(); // Start the PHP session at the very beginning

// Include the database connection file
require_once 'db_connection.php';

// Check for database connection errors
if ($mysqli->connect_errno) {
    $message = 'Database connection failed: ' . $mysqli->connect_error;
    $message_type = 'error';
    // Redirect back to login.html with message
    header('Location: login.html?message=' . urlencode($message) . '&type=' . urlencode($message_type));
    exit;
}

// Check if the request method is POST (meaning the form was submitted)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic server-side validation
    if (empty($email) || empty($password)) {
        $message = 'Please enter both email and password.';
        $message_type = 'error';
        header('Location: login.html?message=' . urlencode($message) . '&type=' . urlencode($message_type));
        exit;
    }

    // Prepare a select statement to retrieve user data from the 'users' table
    $sql = "SELECT id, name, email, password_hash, role, is_approved FROM users WHERE email = ?";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $name, $email, $hashed_password, $role, $is_approved);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashed_password)) {
                // Check if the user account is approved (for roles that require approval)
                if ($is_approved == 0 && in_array($role, ['researcher', 'academic', 'contributor', 'curator'])) {
                    $message = 'Your account is awaiting admin approval. Please try again after approval.';
                    $message_type = 'info';
                    header('Location: login.html?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                    exit;
                }

                // Password is correct and account is approved, start a new session
                session_regenerate_id(true); // Regenerate session ID for security

                $_SESSION["loggedin"] = true;
                $_SESSION["user_id"] = $user_id;
                $_SESSION["user_name"] = $name;
                $_SESSION["user_email"] = $email;
                $_SESSION["user_role"] = $role; // Store the primary role from the users table

                // --- Check if the user is an admin from the 'admins' table ---
                $is_admin = false;
                $admin_staff_id = null;
                $check_admin_sql = "SELECT adminid FROM admins WHERE user_id = ?";
                if ($admin_stmt = $mysqli->prepare($check_admin_sql)) {
                    $admin_stmt->bind_param("i", $user_id);
                    $admin_stmt->execute();
                    $admin_stmt->store_result();
                    if ($admin_stmt->num_rows > 0) {
                        $is_admin = true;
                        $admin_stmt->bind_result($admin_staff_id);
                        $admin_stmt->fetch();
                        $_SESSION['is_admin'] = true; // Set a specific flag for admin status
                        $_SESSION['admin_id_staff'] = $admin_staff_id; // Store the unique admin staff ID
                    } else {
                        $_SESSION['is_admin'] = false;
                    }
                    $admin_stmt->close();
                } else {
                    // Log error if admin check statement fails (for debugging)
                    error_log("Failed to prepare admin check statement: " . $mysqli->error);
                    $_SESSION['is_admin'] = false;
                }
                // --- End Admin Check ---

                // Redirect based on admin status
                if ($is_admin) {
                    header("location: admin_dashboard.html"); // Redirect to admin dashboard (HTML file)
                } else {
                    header("location: dashboard.php"); // Redirect to general user dashboard (PHP file)
                }
                exit;

            } else {
                // Invalid password
                $message = 'Invalid email or password.';
                $message_type = 'error';
                header('Location: login.html?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                exit;
            }
        } else {
            // User not found
            $message = 'Invalid email or password.';
            $message_type = 'error';
            header('Location: login.html?message=' . urlencode($message) . '&type=' . urlencode($message_type));
            exit;
        }

        $stmt->close();
    } else {
        $message = 'Database error during login: ' . $mysqli->error;
        $message_type = 'error';
        header('Location: login.html?message=' . urlencode($message) . '&type=' . urlencode($message_type));
        exit;
    }
} else {
    // If not a POST request, redirect to login form
    header('Location: login.html');
    exit;
}

// Close database connection (only if it hasn't been closed by an exit statement)
$mysqli->close();
?>
