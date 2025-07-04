<?php
// dashboard.php
// This is the main user dashboard page. Its content and available actions
// vary based on the user's role and fetched database information.

session_start(); // Start the PHP session at the very beginning

// Include the database connection file
require_once 'db_connection.php';

// Check if the user is logged in. If not, redirect them to the login page.
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"])) {
    header("location: login.html"); // Redirect to your login HTML page
    exit; // Stop script execution
}

// Retrieve basic user information from session variables
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

// Initialize an array to hold additional user details
$additional_user_details = [];

// Fetch more detailed user information from the database based on their role
$sql_user_details = "SELECT * FROM users WHERE id = ?";
if ($stmt = $mysqli->prepare($sql_user_details)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $additional_user_details = $result->fetch_assoc();
    }
    $stmt->close();
} else {
    error_log("Error preparing user details query: " . $mysqli->error);
}

// --- Fetch Approved Datasets for display (for all users) ---
$approved_datasets = [];
$sql_approved_datasets = "SELECT d.id, d.title, d.description, d.file_size, d.file_format,
                                 u.name AS uploaded_by_name, u.role AS uploader_role,
                                 c.name AS category_name, d.uploaded_at
                          FROM datasets d
                          JOIN users u ON d.uploaded_by_user_id = u.id
                          LEFT JOIN categories c ON d.category_id = c.id
                          WHERE d.status = 'approved'
                          ORDER BY d.uploaded_at DESC";

if ($result = $mysqli->query($sql_approved_datasets)) {
    while ($row = $result->fetch_assoc()) {
        $approved_datasets[] = $row;
    }
    $result->free();
} else {
    error_log("Error fetching approved datasets: " . $mysqli->error);
}

// --- NEW: Fetch My Submissions (for researcher and contributor) ---
$my_submissions = [];
if (in_array($user_role, ['researcher', 'contributor'])) {
    $sql_my_submissions = "SELECT d.id, d.title, d.description, d.file_size, d.file_format, d.status, d.uploaded_at,
                                   c.name AS category_name
                           FROM datasets d
                           LEFT JOIN categories c ON d.category_id = c.id
                           WHERE d.uploaded_by_user_id = ?
                           ORDER BY d.uploaded_at DESC";
    if ($stmt = $mysqli->prepare($sql_my_submissions)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $my_submissions[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Error preparing my submissions query: " . $mysqli->error);
    }
}

// --- Define allowed institutions for students (for demonstration) ---
// In a real application, this would come from a database table (e.g., `approved_institutions`)
$allowed_institutions = [
    'University of Nairobi',
    'Strathmore University',
    'Kenyatta University',
    'Moi University'
];

// Close database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Data Repository</title>
    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background */
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            display: flex; /* Use flexbox for two-column layout */
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            gap: 2rem; /* Space between columns */
        }
        .left-panel {
            flex: 1; /* Take remaining space */
            min-width: 280px; /* Minimum width for responsiveness */
            background-color: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        .right-panel {
            flex: 2; /* Take twice the space of the left panel */
            min-width: 320px; /* Minimum width for responsiveness */
            padding: 0; /* Adjust padding as needed, already done by dashboard-container */
        }
        h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #374151;
            margin-bottom: 1rem;
            text-align: center;
        }
        h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }
        .user-detail-item {
            margin-bottom: 0.75rem;
            font-size: 1rem;
            color: #4b5563;
        }
        .user-detail-item strong {
            color: #1f2937;
        }
        .user-role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px; /* Pill shape */
            font-weight: 600;
            font-size: 0.875rem;
            margin-left: 0.5rem;
        }
        /* Role-specific badge colors */
        .role-normal_user { background-color: #e0e7ff; color: #3730a3; } /* Indigo-light */
        .role-student { background-color: #dbeafe; color: #1e40af; } /* Blue */
        .role-researcher { background-color: #d1fae5; color: #065f46; } /* Green */
        .role-academic { background-color: #fffbeb; color: #b45309; } /* Amber */
        .role-contributor { background-color: #fce7f3; color: #9d174d; } /* Pink */
        .role-curator { background-color: #eef2ff; color: #4f46e5; } /* Violet */
        .role-admin { background-color: #fee2e2; color: #dc2626; } /* Red (for clarity, though admins go to admin_dashboard.html) */

        .action-list {
            margin-top: 1rem;
            display: grid; /* Use grid for action buttons */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Responsive grid */
            gap: 1rem;
        }
        .action-list a {
            display: flex; /* Use flex for centering content in button */
            justify-content: center;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: #4f46e5; /* Indigo */
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .action-list a:hover {
            background-color: #4338ca; /* Darker indigo */
            transform: translateY(-2px);
        }
        .logout-btn {
            margin-top: 2rem;
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #ef4444; /* Red */
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logout-btn:hover {
            background-color: #dc2626; /* Darker red */
        }

        /* Styles for dataset table */
        .dataset-table-container {
            overflow-x: auto;
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            background-color: #f9fafb;
        }
        .dataset-table {
            width: 100%;
            border-collapse: collapse;
        }
        .dataset-table th, .dataset-table td {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            text-align: left;
            font-size: 0.9rem;
        }
        .dataset-table th {
            background-color: #eef2ff;
            font-weight: 600;
            color: #4f46e5;
        }
        .dataset-table tr:nth-child(even) {
            background-color: #fcfdfe;
        }
        .download-btn {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            background-color: #10b981; /* Green */
            color: white;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out;
            white-space: nowrap;
        }
        .download-btn:hover {
            background-color: #059669;
        }
        .access-denied-text {
            color: #ef4444; /* Red */
            font-size: 0.85rem;
            white-space: nowrap;
        }


        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column; /* Stack columns vertically on small screens */
            }
            .left-panel, .right-panel {
                min-width: unset; /* Remove min-width constraint */
                width: 100%; /* Take full width */
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Left Panel: User Details -->
        <div class="left-panel">
            <h2 class="text-left">Your Profile</h2>
            <div class="user-detail-item">
                <strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?>
            </div>
            <div class="user-detail-item">
                <strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?>
            </div>
            <div class="user-detail-item">
                <strong>Role:</strong>
                <span class="user-role-badge role-<?php echo htmlspecialchars($user_role); ?>">
                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user_role))); ?>
                </span>
            </div>

            <?php if ($user_role === 'student' && isset($additional_user_details['institution'])): ?>
                <div class="user-detail-item">
                    <strong>Institution:</strong> <?php echo htmlspecialchars($additional_user_details['institution']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Student ID:</strong> <?php echo htmlspecialchars($additional_user_details['student_id']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Course:</strong> <?php echo htmlspecialchars($additional_user_details['course_of_study']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Year:</strong> <?php echo htmlspecialchars($additional_user_details['year_of_study']); ?>
                </div>
            <?php elseif ($user_role === 'researcher' && isset($additional_user_details['research_area'])): ?>
                <div class="user-detail-item">
                    <strong>Institution:</strong> <?php echo htmlspecialchars($additional_user_details['institution']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Research Area:</strong> <?php echo htmlspecialchars($additional_user_details['research_area']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Research ID:</strong> <?php echo htmlspecialchars($additional_user_details['research_id']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Experience (Years):</strong> <?php echo htmlspecialchars($additional_user_details['experience']); ?>
                </div>
            <?php elseif ($user_role === 'academic' && isset($additional_user_details['academic_institution'])): ?>
                <div class="user-detail-item">
                    <strong>Institution:</strong> <?php echo htmlspecialchars($additional_user_details['academic_institution']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Department:</strong> <?php echo htmlspecialchars($additional_user_details['department']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Designation:</strong> <?php echo htmlspecialchars($additional_user_details['designation']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Academic Email:</strong> <?php echo htmlspecialchars($additional_user_details['academic_email']); ?>
                </div>
            <?php elseif ($user_role === 'contributor' && isset($additional_user_details['organization_name'])): ?>
                <div class="user-detail-item">
                    <strong>Organization:</strong> <?php echo htmlspecialchars($additional_user_details['organization_name']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Data Type:</strong> <?php echo htmlspecialchars($additional_user_details['type_of_data']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Contact Info:</strong> <?php echo htmlspecialchars($additional_user_details['contact_info']); ?>
                </div>
            <?php elseif ($user_role === 'curator' && isset($additional_user_details['area_of_expertise'])): ?>
                <div class="user-detail-item">
                    <strong>Area of Expertise:</strong> <?php echo htmlspecialchars($additional_user_details['area_of_expertise']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Institution:</strong> <?php echo htmlspecialchars($additional_user_details['curator_institution']); ?>
                </div>
                <div class="user-detail-item">
                    <strong>Experience (Years):</strong> <?php echo htmlspecialchars($additional_user_details['curator_experience']); ?>
                </div>
            <?php endif; ?>

            <p class="mt-6 text-gray-500 text-sm">
                Your account is currently <?php echo $additional_user_details['is_approved'] ? 'approved' : 'awaiting approval'; ?>.
            </p>
        </div>

        <!-- Right Panel: Role-Specific Actions & Datasets -->
        <div class="right-panel">
            <h2 class="text-left">Available Actions</h2>
            <div class="action-list">
                <?php
                // Note: Admin users are redirected to admin_dashboard.html by login_process.php.
                // This section is for other roles.
                if ($user_role === 'researcher' || $user_role === 'contributor'): ?>
                    <a href="upload_dataset.php">Upload New Dataset</a>
                    <a href="#my-submissions">View My Submissions</a> <!-- Link to the new section -->
                    <a href="#">Request Data Access</a>
                <?php elseif ($user_role === 'curator'): ?>
                    <a href="#">Review Pending Datasets</a>
                    <a href="#">Manage Approved Datasets</a>
                <?php elseif ($user_role === 'student'): ?>
                    <a href="#">Submit Coursework</a>
                <?php elseif ($user_role === 'academic'): ?>
                    <a href="#">Upload Teaching Materials</a>
                    <a href="#">Supervise Students</a>
                <?php endif; ?>
                <a href="#">View All Available Datasets</a>
                <a href="#">Search Datasets</a>
            </div>

            <h2 class="text-left mt-8">Approved Datasets</h2>
            <div class="dataset-table-container">
                <?php if (empty($approved_datasets)): ?>
                    <p class="text-center text-gray-500">No approved datasets available yet.</p>
                <?php else: ?>
                    <table class="dataset-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Uploaded By</th>
                                <th>Size</th>
                                <th>Format</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approved_datasets as $dataset): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dataset['title']); ?></td>
                                    <td><?php echo htmlspecialchars($dataset['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($dataset['uploaded_by_name']); ?> (<?php echo htmlspecialchars($dataset['uploader_role']); ?>)</td>
                                    <td><?php echo htmlspecialchars($dataset['file_size']); ?></td>
                                    <td><?php echo htmlspecialchars($dataset['file_format']); ?></td>
                                    <td>
                                        <?php if ($user_role === 'normal_user'): ?>
                                            <a href="payment.php?dataset_id=<?php echo htmlspecialchars($dataset['id']); ?>" class="download-btn bg-blue-500 hover:bg-blue-600">Download (Payment)</a>
                                        <?php elseif ($user_role === 'student'):
                                            // Check if student's institution is allowed
                                            $student_institution = $additional_user_details['institution'] ?? '';
                                            if (!empty($student_institution) && in_array($student_institution, $allowed_institutions)): ?>
                                                <a href="download_file.php?dataset_id=<?php echo htmlspecialchars($dataset['id']); ?>" class="download-btn">Download</a>
                                            <?php else: ?>
                                                <span class="access-denied-text">Access Denied (Institution)</span>
                                            <?php endif; ?>
                                        <?php else: // For researcher, academic, contributor, curator, etc. ?>
                                            <a href="download_file.php?dataset_id=<?php echo htmlspecialchars($dataset['id']); ?>" class="download-btn">Download</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <?php if (in_array($user_role, ['researcher', 'contributor'])): ?>
                <h2 id="my-submissions" class="text-left mt-8">My Submissions</h2>
                <div class="dataset-table-container">
                    <?php if (empty($my_submissions)): ?>
                        <p class="text-center text-gray-500">You have not uploaded any datasets yet.</p>
                    <?php else: ?>
                        <table class="dataset-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Size</th>
                                    <th>Format</th>
                                    <th>Status</th>
                                    <th>Uploaded At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_submissions as $submission): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($submission['title']); ?></td>
                                        <td><?php echo htmlspecialchars($submission['category_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($submission['file_size']); ?></td>
                                        <td><?php echo htmlspecialchars($submission['file_format']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($submission['status']) {
                                                case 'pending':
                                                    $status_class = 'text-yellow-600';
                                                    break;
                                                case 'approved':
                                                    $status_class = 'text-green-600';
                                                    break;
                                                case 'disapproved':
                                                    $status_class = 'text-red-600';
                                                    break;
                                            }
                                            ?>
                                            <span class="font-semibold <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars(ucwords($submission['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($submission['uploaded_at']))); ?></td>
                                        <td>
                                            <?php if ($submission['status'] === 'approved'): ?>
                                                <a href="download_file.php?dataset_id=<?php echo htmlspecialchars($submission['id']); ?>" class="download-btn">Download</a>
                                            <?php else: ?>
                                                <span class="text-gray-500 text-sm">N/A</span>
                                            <?php endif; ?>
                                            <!-- Add other actions like 'Edit' or 'Delete' if needed -->
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <div class="text-center mt-8">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>