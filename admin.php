<?php
// admin.php
// This script serves as a consolidated backend for the admin dashboard.
// It handles both API data fetching (GET requests) and administrative actions (POST requests).

session_start(); // Start the PHP session

// Include the database connection file
require_once 'db_connection.php';

// Check for database connection errors
if ($mysqli->connect_errno) {
    // For GET requests, return JSON error
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error]);
        exit;
    } else {
        // For POST requests, set session message and redirect
        $_SESSION['message'] = 'Database connection failed: ' . $mysqli->connect_error;
        $_SESSION['message_type'] = 'error';
        header('Location: admin_dashboard.html');
        exit;
    }
}

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    // For GET requests, return JSON unauthorized
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
        exit;
    } else {
        // For POST requests, set session message and redirect
        $_SESSION['message'] = 'Unauthorized access. Please log in.';
        $_SESSION['message_type'] = 'error';
        header('Location: login.html');
        exit;
    }
}

// Check if the logged-in user is actually an admin by checking the 'admins' table
$is_admin = false;
$current_user_id = $_SESSION["user_id"];
$current_admin_staff_id = null; // To store the staff ID of the logged-in admin

$check_admin_sql = "SELECT adminid FROM admins WHERE user_id = ?";
if ($stmt = $mysqli->prepare($check_admin_sql)) {
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $is_admin = true;
        $stmt->bind_result($current_admin_staff_id);
        $stmt->fetch();
    }
    $stmt->close();
}

// If not an admin, deny access
if (!$is_admin) {
    // For GET requests, return JSON forbidden
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Access denied. You do not have administrative privileges.']);
        exit;
    } else {
        // For POST requests, set session message and redirect
        $_SESSION['message'] = 'Access denied. You do not have administrative privileges.';
        $_SESSION['message_type'] = 'error';
        header('Location: dashboard.php'); // Redirect to a non-admin dashboard or login
        exit;
    }
}

// Initialize response for JSON output (for GET requests)
$json_response = ['success' => false, 'message' => ''];

// --- Handle GET Requests (Data Fetching) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json'); // Ensure JSON header for GET requests
    $action = $_GET['action'] ?? 'get_all_data';

    try {
        switch ($action) {
            case 'get_all_data':
                $json_response['data']['admin'] = [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'adminid' => $current_admin_staff_id
                ];

                // Fetch actual admin users
                $sql_admins = "SELECT u.id, u.name, u.email, a.adminid FROM users u JOIN admins a ON u.id = a.user_id ORDER BY u.name ASC";
                if ($result = $mysqli->query($sql_admins)) {
                    while ($row = $result->fetch_assoc()) {
                        $json_response['data']['admin_users'][] = $row;
                    }
                    $result->free();
                } else {
                    throw new Exception("Error fetching admin users: " . $mysqli->error);
                }

                // Fetch all non-admin users (excluding those in the admins table)
                $non_admin_users_data = [];
                $admin_user_ids = [];
                foreach ($json_response['data']['admin_users'] as $admin_user) {
                    $admin_user_ids[] = $admin_user['id'];
                }

                $sql_non_admin_users = "SELECT id, name, email, role, is_approved, institution, student_id, course_of_study, year_of_study,
                                               research_area, research_id, experience, academic_institution, department, designation, academic_email,
                                               organization_name, type_of_data, contact_info, area_of_expertise, curator_institution, curator_experience, created_at
                                        FROM users";
                if (!empty($admin_user_ids)) {
                    $placeholders = implode(',', array_fill(0, count($admin_user_ids), '?'));
                    $sql_non_admin_users .= " WHERE id NOT IN ($placeholders)";
                }
                $sql_non_admin_users .= " ORDER BY created_at DESC";

                if ($stmt = $mysqli->prepare($sql_non_admin_users)) {
                    if (!empty($admin_user_ids)) {
                        $types = str_repeat('i', count($admin_user_ids));
                        $stmt->bind_param($types, ...$admin_user_ids);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $non_admin_users_data[] = $row;
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Error preparing non-admin user fetch statement: " . $mysqli->error);
                }
                $json_response['data']['non_admin_users'] = $non_admin_users_data;

                // Fetch all datasets
                $datasets_data = [];
                $sql_datasets = "SELECT d.id, d.title, d.description, d.file_path, d.file_size, d.file_format, d.status, d.uploaded_at, d.metadata_summary, d.approval_notes, d.approved_at,
                                         u.name AS uploaded_by_name, u.role AS uploader_role, c.name AS category_name
                                  FROM datasets d
                                  JOIN users u ON d.uploaded_by_user_id = u.id
                                  LEFT JOIN categories c ON d.category_id = c.id
                                  ORDER BY d.uploaded_at DESC";
                if ($result = $mysqli->query($sql_datasets)) {
                    while ($row = $result->fetch_assoc()) {
                        $datasets_data[] = $row;
                    }
                    $result->free();
                } else {
                    throw new Exception("Error fetching datasets: " . $mysqli->error);
                }

                $json_response['data']['pending_datasets'] = array_values(array_filter($datasets_data, function($dataset) {
                    return $dataset['status'] === 'pending';
                }));
                $json_response['data']['approved_datasets'] = array_values(array_filter($datasets_data, function($dataset) {
                    return $dataset['status'] === 'approved';
                }));

                // Fetch allowed institutions
                $allowed_institutions = [];
                $sql_institutions = "SELECT id, institution_name FROM allowed_institutions ORDER BY institution_name ASC";
                if ($result = $mysqli->query($sql_institutions)) {
                    while ($row = $result->fetch_assoc()) {
                        $allowed_institutions[] = $row;
                    }
                    $result->free();
                }
                $json_response['data']['allowed_institutions'] = $allowed_institutions;

                $json_response['success'] = true;
                $json_response['message'] = 'Data fetched successfully.';
                break;

            case 'get_user_details':
                $user_id = (int)($_GET['user_id'] ?? 0);
                if ($user_id === 0) {
                    $json_response['message'] = 'Invalid user ID.';
                    break;
                }
                $sql = "SELECT id, name, email, role, is_approved, institution, student_id, course_of_study, year_of_study,
                               research_area, research_id, experience, academic_institution, department, designation, academic_email,
                               organization_name, type_of_data, contact_info, area_of_expertise, curator_institution, curator_experience, created_at
                        FROM users WHERE id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($user_data = $result->fetch_assoc()) {
                        $json_response['success'] = true;
                        $json_response['data'] = $user_data;
                    } else {
                        $json_response['message'] = 'User not found.';
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing user details query: " . $mysqli->error);
                }
                break;

            case 'get_dataset_details':
                $dataset_id = (int)($_GET['dataset_id'] ?? 0);
                if ($dataset_id === 0) {
                    $json_response['message'] = 'Invalid dataset ID.';
                    break;
                }
                $sql = "SELECT d.id, d.title, d.description, d.file_path, d.file_size, d.file_format, d.uploaded_at, d.metadata_summary, d.status, d.approval_notes, d.approved_at,
                               u.name AS uploaded_by_name, u.role AS uploader_role,
                               c.name AS category_name
                        FROM datasets d
                        JOIN users u ON d.uploaded_by_user_id = u.id
                        LEFT JOIN categories c ON d.category_id = c.id
                        WHERE d.id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("i", $dataset_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($dataset_data = $result->fetch_assoc()) {
                        $json_response['success'] = true;
                        $json_response['data'] = $dataset_data;
                    } else {
                        $json_response['message'] = 'Dataset not found.';
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing dataset details query: " . $mysqli->error);
                }
                break;

            default:
                $json_response['message'] = 'Invalid action specified for API.';
                break;
        }
    } catch (Exception $e) {
        $json_response['message'] = 'Server error: ' . $e->getMessage();
        error_log("Admin API Error: " . $e->getMessage());
    } finally {
        if ($mysqli) {
            $mysqli->close();
        }
        echo json_encode($json_response);
        exit;
    }
}

// --- Handle POST Requests (Actions) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start output buffering for POST requests, as they will redirect
    ob_start();

    $response_message = '';
    $response_type = ''; // 'success', 'error', 'info'

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add_admin':
                $email = trim($_POST['email'] ?? '');
                if (empty($email)) {
                    $response_message = 'Email is required to promote a user to admin.';
                    $response_type = 'error';
                    break;
                }

                $sql_find_user = "SELECT id, name FROM users WHERE email = ?";
                if ($stmt_find = $mysqli->prepare($sql_find_user)) {
                    $stmt_find->bind_param("s", $email);
                    $stmt_find->execute();
                    $result_find = $stmt_find->get_result();
                    if ($user = $result_find->fetch_assoc()) {
                        $check_existing_admin_sql = "SELECT user_id FROM admins WHERE user_id = ?";
                        if ($check_stmt = $mysqli->prepare($check_existing_admin_sql)) {
                            $check_stmt->bind_param("i", $user['id']);
                            $check_stmt->execute();
                            $check_stmt->store_result();
                            if ($check_stmt->num_rows > 0) {
                                $response_message = 'User is already an administrator.';
                                $response_type = 'info';
                            } else {
                                $temp_adminid = 'ADM_' . uniqid();
                                $insert_admin_sql = "INSERT INTO admins (user_id, adminid) VALUES (?, ?)";
                                if ($insert_stmt = $mysqli->prepare($insert_admin_sql)) {
                                    $insert_stmt->bind_param("is", $user['id'], $temp_adminid);
                                    if ($insert_stmt->execute()) {
                                        $update_user_role_sql = "UPDATE users SET role = 'admin', is_approved = 1 WHERE id = ?";
                                        if ($update_stmt_user = $mysqli->prepare($update_user_role_sql)) {
                                            $update_stmt_user->bind_param("i", $user['id']);
                                            $update_stmt_user->execute();
                                            $update_stmt_user->close();
                                        }
                                        $mysqli->commit();
                                        $response_message = 'User ' . htmlspecialchars($user['name']) . ' (' . htmlspecialchars($email) . ') has been promoted to administrator.';
                                        $response_type = 'success';
                                    } else {
                                        throw new Exception("Error inserting into admins table: " . $insert_stmt->error);
                                    }
                                    $insert_stmt->close();
                                } else {
                                    throw new Exception("Database error preparing insert admin statement: " . $mysqli->error);
                                }
                            }
                            $check_stmt->close();
                        } else {
                            throw new Exception("Database error preparing check existing admin statement: " . $mysqli->error);
                        }
                    } else {
                        $response_message = 'User with this email not found.';
                        $response_type = 'error';
                    }
                    $stmt_find->close();
                } else {
                    throw new Exception("Database error preparing find user statement: " . $mysqli->error);
                }
                break;

            case 'remove_admin_role':
                $user_id = (int)($_POST['user_id'] ?? 0);
                if ($user_id === 0) {
                    $response_message = 'Invalid user ID.';
                    $response_type = 'error';
                    break;
                }
                if ($user_id === $_SESSION['user_id']) {
                    $response_message = 'You cannot remove your own admin role.';
                    $response_type = 'error';
                    break;
                }

                $mysqli->begin_transaction();
                try {
                    $sql_delete_admin = "DELETE FROM admins WHERE user_id = ?";
                    if ($stmt_delete_admin = $mysqli->prepare($sql_delete_admin)) {
                        $stmt_delete_admin->bind_param("i", $user_id);
                        $stmt_delete_admin->execute();
                        $stmt_delete_admin->close();
                    } else {
                        throw new Exception("Database error preparing delete admin statement: " . $mysqli->error);
                    }

                    $sql_update_user_role = "UPDATE users SET role = 'normal_user', is_approved = 1 WHERE id = ?";
                    if ($stmt_update_user_role = $mysqli->prepare($sql_update_user_role)) {
                        $stmt_update_user_role->bind_param("i", $user_id);
                        if ($stmt_update_user_role->execute()) {
                            $mysqli->commit();
                            $response_message = 'Admin role removed successfully. User is now a normal user.';
                            $response_type = 'success';
                        } else {
                            throw new Exception("Error updating user role: " . $stmt_update_user_role->error);
                        }
                        $stmt_update_user_role->close();
                    } else {
                        throw new Exception("Database error preparing update user role statement: " . $mysqli->error);
                    }
                } catch (Exception $e) {
                    $mysqli->rollback();
                    $response_message = 'Error removing admin role: ' . $e->getMessage();
                    $response_type = 'error';
                }
                break;

            case 'approve_user':
                $user_id = (int)($_POST['user_id'] ?? 0);
                if ($user_id === 0) {
                    $response_message = 'Invalid user ID.';
                    $response_type = 'error';
                    break;
                }

                $sql = "UPDATE users SET is_approved = 1 WHERE id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        $response_message = 'User ID: ' . $user_id . ' has been approved.';
                        $response_type = 'success';
                    } else {
                        throw new Exception("Error approving user: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing statement: " . $mysqli->error);
                }
                break;

            case 'delete_user':
                $user_id = (int)($_POST['user_id'] ?? 0);
                if ($user_id === 0) {
                    $response_message = 'Invalid user ID.';
                    $response_type = 'error';
                    break;
                }
                if ($user_id === $_SESSION['user_id']) {
                    $response_message = 'You cannot delete your own account from here.';
                    $response_type = 'error';
                    break;
                }

                $mysqli->begin_transaction();
                try {
                    $sql_delete_datasets = "DELETE FROM datasets WHERE uploaded_by_user_id = ?";
                    if ($stmt_datasets = $mysqli->prepare($sql_delete_datasets)) {
                        $stmt_datasets->bind_param("i", $user_id);
                        $stmt_datasets->execute();
                        $stmt_datasets->close();
                    }

                    $sql_delete_from_admins = "DELETE FROM admins WHERE user_id = ?";
                    if ($stmt_delete_admin = $mysqli->prepare($sql_delete_from_admins)) {
                        $stmt_delete_admin->bind_param("i", $user_id);
                        $stmt_delete_admin->execute();
                        $stmt_delete_admin->close();
                    }

                    $sql_delete_user = "DELETE FROM users WHERE id = ?";
                    if ($stmt_user = $mysqli->prepare($sql_delete_user)) {
                        $stmt_user->bind_param("i", $user_id);
                        if ($stmt_user->execute()) {
                            $mysqli->commit();
                            $response_message = 'User ID: ' . $user_id . ' and associated data deleted successfully.';
                            $response_type = 'success';
                        } else {
                            throw new Exception("Error deleting user: " . $stmt_user->error);
                        }
                        $stmt_user->close();
                    } else {
                        throw new Exception("Database error preparing delete user statement: " . $mysqli->error);
                    }
                } catch (Exception $e) {
                    $mysqli->rollback();
                    $response_message = 'Error deleting user and associated data: ' . $e->getMessage();
                    $response_type = 'error';
                }
                break;

            case 'approve_dataset':
                $dataset_id = (int)($_POST['dataset_id'] ?? 0);
                if ($dataset_id === 0) {
                    $response_message = 'Invalid dataset ID.';
                    $response_type = 'error';
                    break;
                }

                $sql = "UPDATE datasets SET status = 'approved', approved_at = NOW() WHERE id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("i", $dataset_id);
                    if ($stmt->execute()) {
                        $response_message = 'Dataset ID: ' . $dataset_id . ' approved successfully.';
                        $response_type = 'success';
                    } else {
                        throw new Exception("Error approving dataset: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing statement: " . $mysqli->error);
                }
                break;

            case 'disapprove_dataset':
                $dataset_id = (int)($_POST['dataset_id'] ?? 0);
                if ($dataset_id === 0) {
                    $response_message = 'Invalid dataset ID.';
                    $response_type = 'error';
                    break;
                }

                $sql = "UPDATE datasets SET status = 'disapproved', approved_at = NULL, approval_notes = ? WHERE id = ?";
                $notes = "Disapproved by admin.";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("si", $notes, $dataset_id);
                    if ($stmt->execute()) {
                        $response_message = 'Dataset ID: ' . $dataset_id . ' disapproved.';
                        $response_type = 'success';
                    } else {
                        throw new Exception("Error disapproving dataset: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing statement: " . $mysqli->error);
                }
                break;

            case 'remove_dataset':
                $dataset_id = (int)($_POST['dataset_id'] ?? 0);
                if ($dataset_id === 0) {
                    $response_message = 'Invalid dataset ID.';
                    $response_type = 'error';
                    break;
                }

                $file_to_delete = null;
                $sql_get_file = "SELECT file_path FROM datasets WHERE id = ?";
                if ($stmt_get_file = $mysqli->prepare($sql_get_file)) {
                    $stmt_get_file->bind_param("i", $dataset_id);
                    $stmt_get_file->execute();
                    $stmt_get_file->bind_result($file_path_from_db);
                    $stmt_get_file->fetch();
                    $stmt_get_file->close();
                    $file_to_delete = $file_path_from_db;
                }

                $sql = "DELETE FROM datasets WHERE id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("i", $dataset_id);
                    if ($stmt->execute()) {
                        if ($file_to_delete && file_exists($file_to_delete)) {
                            unlink($file_to_delete);
                        }
                        $response_message = 'Dataset ID: ' . $dataset_id . ' and its file removed successfully.';
                        $response_type = 'success';
                    } else {
                        throw new Exception("Error removing dataset: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing statement: " . $mysqli->error);
                }
                break;

            case 'add_allowed_institution':
                $institution_name = trim($_POST['institution_name'] ?? '');
                if (empty($institution_name)) {
                    $response_message = 'Institution name cannot be empty.';
                    $response_type = 'error';
                    break;
                }

                $sql = "INSERT INTO allowed_institutions (institution_name) VALUES (?)";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("s", $institution_name);
                    if ($stmt->execute()) {
                        $response_message = 'Institution "' . htmlspecialchars($institution_name) . '" added to allowed list.';
                        $response_type = 'success';
                    } else {
                        if ($mysqli->errno == 1062) {
                            $response_message = 'Institution "' . htmlspecialchars($institution_name) . '" already exists.';
                            $response_type = 'info';
                        } else {
                            throw new Exception("Error adding institution: " . $stmt->error);
                        }
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing statement: " . $mysqli->error);
                }
                break;

            case 'remove_allowed_institution':
                $institution_id = (int)($_POST['institution_id'] ?? 0);
                if ($institution_id === 0) {
                    $response_message = 'Invalid institution ID.';
                    $response_type = 'error';
                    break;
                }

                $sql = "DELETE FROM allowed_institutions WHERE id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("i", $institution_id);
                    if ($stmt->execute()) {
                        $response_message = 'Institution removed successfully.';
                        $response_type = 'success';
                    } else {
                        throw new Exception("Error removing institution: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error preparing statement: " . $mysqli->error);
                }
                break;

            default:
                $response_message = 'Invalid action.';
                $response_type = 'error';
                break;
        }
    } catch (Exception $e) {
        $response_message = 'Server error: ' . $e->getMessage();
        $response_type = 'error';
        error_log("Admin Actions Error: " . $e->getMessage());
    } finally {
        if ($mysqli) {
            $mysqli->close();
        }
        // Store message in session for redirection
        $_SESSION['message'] = $response_message;
        $_SESSION['message_type'] = $response_type;

        // Ensure session data is written before redirecting
        session_write_close();
        ob_end_clean(); // Clear the buffer before sending the header
        header('Location: admin_dashboard.html?message=' . urlencode($response_message) . '&type=' . urlencode($response_type));
        exit;
    }
}

// If neither GET nor POST, or if script somehow falls through (shouldn't happen with proper exits)
// Close connection and exit.
if ($mysqli) {
    $mysqli->close();
}
exit;
?>
