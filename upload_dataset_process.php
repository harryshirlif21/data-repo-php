<?php
// upload_dataset_process.php
// This script handles the backend logic for uploading new datasets via AJAX.

session_start(); // Start the PHP session
header('Content-Type: application/json'); // Respond with JSON

// Include the database connection file
require_once 'db_connection.php';

$response = ['success' => false, 'message' => ''];

// Check if the user is logged in and has the necessary role for uploading datasets.
// Only 'researcher' and 'contributor' roles are allowed to upload.
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) ||
    !in_array($_SESSION["user_role"], ['researcher', 'contributor'])) {
    http_response_code(403); // Forbidden
    $response['message'] = 'Access denied. You do not have permission to upload datasets.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name']; // Not strictly needed here, but good for context
$user_role = $_SESSION['user_role'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data from FormData
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $metadata_summary_input = trim($_POST['metadata_summary'] ?? '');

    $metadata_summary = NULL; // Default to NULL for database insertion
    $form_valid = true; // Flag to track overall form validity

    // Validate metadata_summary if provided
    if (!empty($metadata_summary_input)) {
        // Attempt to decode as JSON to validate it
        json_decode($metadata_summary_input);
        if (json_last_error() === JSON_ERROR_NONE) {
            // It's valid JSON, so store it as is
            $metadata_summary = $metadata_summary_input;
        } else {
            // It's not empty, but it's invalid JSON
            $response['message'] = 'Metadata Summary must be valid JSON or left empty. Example: {"key": "value"}';
            $form_valid = false; // Mark form as invalid
        }
    }

    // File upload handling
    $file_name = $_FILES['dataset_file']['name'] ?? '';
    $file_tmp_name = $_FILES['dataset_file']['tmp_name'] ?? '';
    $file_size = $_FILES['dataset_file']['size'] ?? 0;
    $file_error = $_FILES['dataset_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $file_type = $_FILES['dataset_file']['type'] ?? '';

    // Basic validation for other fields, only if previous validation passed
    if ($form_valid) {
        if (empty($title) || empty($description) || $category_id === 0 || $file_error === UPLOAD_ERR_NO_FILE) {
            $response['message'] = 'Please fill in all required fields and select a file.';
            $form_valid = false;
        } elseif ($file_error !== UPLOAD_ERR_OK) {
            $response['message'] = 'File upload error: ' . $file_error;
            $form_valid = false;
        }
    }

    // Proceed with file upload and database insertion only if the form is valid
    if ($form_valid) {
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_format = $file_extension;

        // Define upload directory (ensure this directory exists and is writable)
        $upload_dir = 'uploads/datasets/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        // Generate a unique file name to prevent overwrites
        $new_file_name = uniqid('dataset_', true) . '.' . $file_extension;
        $destination_path = $upload_dir . $new_file_name;

        // Move the uploaded file
        if (move_uploaded_file($file_tmp_name, $destination_path)) {
            // File successfully moved, now insert data into database
            $file_size_formatted = formatBytes($file_size); // Helper function to format file size

            $mysqli->begin_transaction(); // Start transaction

            try {
                // The 's' type for metadata_summary will correctly handle NULL
                $insert_sql = "INSERT INTO datasets (title, description, file_path, file_size, file_format, uploaded_by_user_id, category_id, metadata_summary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $mysqli->prepare($insert_sql);
                // Bind metadata_summary as a string, which correctly handles NULL in MySQL for JSON columns
                $stmt->bind_param("sssssiis", $title, $description, $destination_path, $file_size_formatted, $file_format, $user_id, $category_id, $metadata_summary);

                if ($stmt->execute()) {
                    $mysqli->commit(); // Commit transaction
                    $response['success'] = true;
                    $response['message'] = 'Dataset uploaded successfully and is awaiting approval!';
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $mysqli->rollback(); // Rollback on error
                // Delete the uploaded file if database insertion fails
                if (file_exists($destination_path)) {
                    unlink($destination_path);
                }
                $response['message'] = 'Error uploading dataset: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Failed to move uploaded file.';
        }
    }
} else {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Invalid request method.';
}

// Helper function to format file size (optional, but good for display)
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Close database connection
if ($mysqli) {
    $mysqli->close();
}

echo json_encode($response);
?>
