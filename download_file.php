<?php
// download_file.php
// This script handles secure file downloads, checking user authentication and permissions.

session_start();

// Include the database connection file
require_once 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    // Redirect to login if not logged in
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$dataset_id = $_GET['dataset_id'] ?? null;

if (empty($dataset_id)) {
    $_SESSION['message'] = 'No dataset specified for download.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit;
}

// Fetch dataset details and file path from the database
$file_path = null;
$file_name_for_download = null;
$is_approved = false;
$uploader_role = null;
$uploader_id = null;

$sql = "SELECT d.file_path, d.title, d.status, u.role AS uploader_role, u.id AS uploader_id, u.institution
        FROM datasets d
        JOIN users u ON d.uploaded_by_user_id = u.id
        WHERE d.id = ? AND d.status = 'approved'"; // Only allow download of approved datasets

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();
    $stmt->bind_result($file_path, $title, $status, $uploader_role, $uploader_id, $uploader_institution);
    $stmt->fetch();
    $stmt->close();

    if ($file_path && $status === 'approved') {
        $file_name_for_download = basename($file_path); // Use original file name or title
        $is_approved = true;
    }
} else {
    error_log("Error preparing dataset fetch for download: " . $mysqli->error);
}
$mysqli->close(); // Close DB connection after fetching data

if (!$is_approved) {
    $_SESSION['message'] = 'Dataset not found or not approved for download.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit;
}

// --- Permission Check for Download ---
$has_permission = false;

// Admins, Curators, Researchers, Contributors can generally download their own or approved datasets
if (in_array($user_role, ['researcher', 'academic', 'contributor', 'curator'])) {
    // For these roles, we'll assume they can download any approved dataset for now.
    // More granular permissions (e.g., only their own uploads, or specific data access requests)
    // would be implemented here.
    $has_permission = true;
} elseif ($user_role === 'normal_user') {
    // Normal users require payment (redirected to payment.php from dashboard.php)
    // If they somehow reach here, it means they bypassed payment, so deny.
    $_SESSION['message'] = 'Normal users must complete payment to download this dataset.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php'); // Redirect back to dashboard
    exit;
} elseif ($user_role === 'student') {
    // Students need to be from an allowed institution
    $allowed_institutions = [
        'University of Nairobi',
        'Strathmore University',
        'Kenyatta University',
        'Moi University'
    ];
    $student_institution = $additional_user_details['institution'] ?? ''; // Fetch from session or re-fetch from DB if needed

    // Re-fetch student's institution if not in session, or rely on $additional_user_details if passed
    // For simplicity here, we'll assume $additional_user_details['institution'] is available
    // (It's fetched in dashboard.php, but not necessarily in session for this script directly)
    // A more robust solution would be to store it in session or fetch it again here.
    // For now, let's assume it's available or default to empty.
    
    // To make this robust, let's re-fetch student's institution if user_role is student
    if ($user_role === 'student') {
        $mysqli_reconnect = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($mysqli_reconnect->connect_errno) {
            error_log("Failed to reconnect DB for student institution check: " . $mysqli_reconnect->connect_error);
            $_SESSION['message'] = 'Database error during permission check.';
            $_SESSION['message_type'] = 'error';
            header('Location: dashboard.php');
            exit;
        }
        $sql_student_inst = "SELECT institution FROM users WHERE id = ?";
        if ($stmt_inst = $mysqli_reconnect->prepare($sql_student_inst)) {
            $stmt_inst->bind_param("i", $user_id);
            $stmt_inst->execute();
            $stmt_inst->bind_result($student_institution_from_db);
            $stmt_inst->fetch();
            $stmt_inst->close();
        }
        $mysqli_reconnect->close();
        $student_institution = $student_institution_from_db; // Use the fetched institution
    }

    if (!empty($student_institution) && in_array($student_institution, $allowed_institutions)) {
        $has_permission = true;
    } else {
        $_SESSION['message'] = 'Your institution is not authorized to download this dataset.';
        $_SESSION['message_type'] = 'error';
        header('Location: dashboard.php');
        exit;
    }
}

// If user has permission, proceed with file download
if ($has_permission && file_exists($file_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name_for_download . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    $_SESSION['message'] = 'File not found or access denied.';
    $_SESSION['message_type'] = 'error';
    header('Location: dashboard.php');
    exit;
}
?>
