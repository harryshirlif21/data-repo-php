<?php
// get_categories.php
// This script provides categories data as JSON.

session_start(); // Start session to potentially check user role, though not strictly required for public categories
header('Content-Type: application/json'); // Respond with JSON

// Include the database connection file
require_once 'db_connection.php';

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    $categories = [];
    $sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
    if ($result = $mysqli->query($sql_categories)) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $result->free();
        $response['success'] = true;
        $response['data'] = $categories;
    } else {
        throw new Exception("Error fetching categories: " . $mysqli->error);
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log("Get Categories API Error: " . $e->getMessage());
} finally {
    if ($mysqli) {
        $mysqli->close();
    }
    echo json_encode($response);
}
?>
