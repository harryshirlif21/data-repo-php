<?php
// register.php

session_start(); // Start session to use session variables for messages

require_once 'db_connection.php'; // Include your DB connection

// Check the database connection
if ($mysqli->connect_errno) {
    $_SESSION['message'] = 'Database connection failed: ' . $mysqli->connect_error;
    $_SESSION['message_type'] = 'error';
    header('Location: registration.php');
    exit;
}

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get name fields and concatenate to form full name
    $surname = trim($_POST['surname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $name = trim($surname . ' ' . $firstname . ' ' . $middlename); // Full name

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_category = trim($_POST['user_category'] ?? 'normal_user');

    // Basic validation
    if (empty($surname) || empty($firstname) || empty($email) || empty($password) || empty($confirm_password) || empty($user_category)) {
        $_SESSION['message'] = 'Please fill in all required fields.';
        $_SESSION['message_type'] = 'error';
        header('Location: registration.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Please enter a valid email address.';
        $_SESSION['message_type'] = 'error';
        header('Location: registration.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['message'] = 'Passwords do not match.';
        $_SESSION['message_type'] = 'error';
        header('Location: registration.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['message'] = 'Password must be at least 6 characters.';
        $_SESSION['message_type'] = 'error';
        header('Location: registration.php');
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Approval logic
    $is_approved = in_array($user_category, ['normal_user', 'student']) ? 1 : 0;

    $mysqli->begin_transaction();

    try {
        // Insert into users table
        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password_hash, role, is_approved) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $mysqli->error);
        }

        $stmt->bind_param("ssssi", $name, $email, $password_hash, $user_category, $is_approved);
        if (!$stmt->execute()) {
            if ($mysqli->errno == 1062) {
                throw new Exception('Email already exists.');
            }
            throw new Exception('Error inserting user: ' . $stmt->error);
        }
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Additional fields based on user_category
        $update_sql = "";
        $params = [];
        $types = "";
        switch ($user_category) {
            case 'student':
                $update_sql = "UPDATE users SET institution=?, student_id=?, course_of_study=?, year_of_study=? WHERE id=?";
                $params = [
                    trim($_POST['institution'] ?? null),
                    trim($_POST['student_id'] ?? null),
                    trim($_POST['course_of_study'] ?? null),
                    intval($_POST['year_of_study'] ?? 0),
                    $user_id
                ];
                $types = "sssii";
                break;
            case 'researcher':
                $update_sql = "UPDATE users SET institution=?, research_area=?, research_id=?, experience=? WHERE id=?";
                $params = [
                    trim($_POST['institution'] ?? null),
                    trim($_POST['research_area'] ?? null),
                    trim($_POST['research_id'] ?? null),
                    intval($_POST['experience'] ?? 0),
                    $user_id
                ];
                $types = "sssii";
                break;
            case 'academic':
                $update_sql = "UPDATE users SET academic_institution=?, department=?, designation=?, academic_email=? WHERE id=?";
                $params = [
                    trim($_POST['academic_institution'] ?? null),
                    trim($_POST['department'] ?? null),
                    trim($_POST['designation'] ?? null),
                    trim($_POST['academic_email'] ?? null),
                    $user_id
                ];
                $types = "ssssi";
                break;
            case 'contributor':
                $update_sql = "UPDATE users SET organization_name=?, type_of_data=?, contact_info=? WHERE id=?";
                $params = [
                    trim($_POST['organization_name'] ?? null),
                    trim($_POST['type_of_data'] ?? null),
                    trim($_POST['contact_info'] ?? null),
                    $user_id
                ];
                $types = "sssi";
                break;
            case 'curator':
                $update_sql = "UPDATE users SET area_of_expertise=?, curator_institution=?, curator_experience=? WHERE id=?";
                $params = [
                    trim($_POST['area_of_expertise'] ?? null),
                    trim($_POST['curator_institution'] ?? null),
                    intval($_POST['curator_experience'] ?? 0),
                    $user_id
                ];
                $types = "ssii";
                break;
        }

        if (!empty($update_sql)) {
            $update_stmt = $mysqli->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception('Prepare failed for role fields: ' . $mysqli->error);
            }
            $update_stmt->bind_param($types, ...$params);
            if (!$update_stmt->execute()) {
                throw new Exception('Error updating role-specific fields: ' . $update_stmt->error);
            }
            $update_stmt->close();
        }

        $mysqli->commit();

        $_SESSION['message'] = 'Registration successful!';
        $_SESSION['message_type'] = 'success';
        if (!$is_approved) {
            $_SESSION['message'] .= ' Please wait for admin approval.';
            $_SESSION['message_type'] = 'info';
        }

        header('Location: login.html');
        exit;

    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['message'] = 'Registration failed: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header('Location: registration.php');
        exit;
    }

} else {
    // If request is not POST, redirect to registration form
    header('Location: registration.php');
    exit;
}

$mysqli->close();
?>
