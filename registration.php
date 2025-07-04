<?php
// registration.php
// This page displays the registration form and handles messages from register.php

session_start(); // Start the session to access messages

$message = '';
$message_type = ''; // 'success', 'error', 'info'

// Check for messages in the session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info'; // Default to 'info'
    unset($_SESSION['message']); // Clear the message after displaying
    unset($_SESSION['message_type']); // Clear the message type
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Repository Registration</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5); /* Blue focus ring */
            border-color: #4299e1;
        }
        .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5);
            border-color: #4299e1;
        }
        /* Styles for message box based on type */
        .message-box.success {
            background-color: #d1fae5; /* green-100 */
            border-color: #34d399; /* green-400 */
            color: #065f46; /* green-700 */
        }
        .message-box.error {
            background-color: #fee2e2; /* red-100 */
            border-color: #f87171; /* red-400 */
            color: #b91c1c; /* red-700 */
        }
        .message-box.info {
            background-color: #dbeafe; /* blue-100 */
            border-color: #60a5fa; /* blue-400 */
            color: #1e40af; /* blue-700 */
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-xl w-full bg-white p-8 rounded-xl shadow-lg border border-gray-200">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Create Your Account</h2>
        <p class="text-center text-gray-600 mb-8">Join the Data Repository</p>

        <!-- Form will now submit directly to register.php -->
        <form id="registrationForm" action="register.php" method="POST" class="space-y-6">
            <div>
    <label for="surname" class="block text-sm font-medium text-gray-700 mb-1">Surname</label>
    <input
        type="text"
        id="surname"
        name="surname"
        placeholder="Doe"
        required
        class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
    >
</div>
<div>
    <label for="firstname" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
    <input
        type="text"
        id="firstname"
        name="firstname"
        placeholder="John"
        required
        class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
    >
</div>
<div>
    <label for="middlename" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
    <input
        type="text"
        id="middlename"
        name="middlename"
        placeholder="Michael"
        class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
    >
</div>


            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    required
                    class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                >
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                >
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="••••••••"
                    required
                    class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                >
            </div>

            <div>
                <label for="user_category" class="block text-sm font-medium text-gray-700 mb-1">User Category</label>
                <select
                    id="user_category"
                    name="user_category"
                    required
                    class="form-select mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out bg-white"
                >
                    <option value="">Select a category</option>
                    <option value="normal_user">Normal User</option>
                    <option value="student">Student</option>
                    <option value="researcher">Researcher</option>
                    <option value="academic">Academic</option>
                    <option value="contributor">Contributor</option>
                    <option value="curator">Curator</option>
                </select>
            </div>

            <!-- Dynamic fields based on user category -->
            <div id="dynamicFields" class="space-y-6">
                <!-- Fields will be inserted here by JavaScript -->
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 ease-in-out transform hover:scale-105"
                >
                    Register
                </button>
            </div>
        </form>

        <p class="mt-8 text-center text-sm text-gray-600">
            Already have an account?
            <a href="login.html" class="font-medium text-blue-600 hover:text-blue-500 transition duration-150 ease-in-out">Login here</a>
        </p>

        <!-- Message Box for displaying messages from PHP -->
        <?php if (!empty($message)): ?>
            <div id="messageBox" class="mt-6 p-3 rounded-md message-box <?php echo htmlspecialchars($message_type); ?>" role="alert">
                <span id="messageText"><?php echo htmlspecialchars($message); ?></span>
                <button class="float-right text-current hover:opacity-75 font-bold" onclick="document.getElementById('messageBox').classList.add('hidden');">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const userCategorySelect = document.getElementById('user_category');
        const dynamicFieldsDiv = document.getElementById('dynamicFields');
        // No more direct manipulation of messageBox from JS submit event
        // const messageBox = document.getElementById('messageBox');
        // const messageText = document.getElementById('messageText');

        const userCategoryFields = {
            'student': `
                <div>
                    <label for="institution" class="block text-sm font-medium text-gray-700 mb-1">Institution</label>
                    <input type="text" id="institution" name="institution" placeholder="University Name" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                    <input type="text" id="student_id" name="student_id" placeholder="12345678" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="course_of_study" class="block text-sm font-medium text-gray-700 mb-1">Course of Study</label>
                    <input type="text" id="course_of_study" name="course_of_study" placeholder="Computer Science" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="year_of_study" class="block text-sm font-medium text-gray-700 mb-1">Year of Study</label>
                    <input type="number" id="year_of_study" name="year_of_study" placeholder="3" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
            `,
            'researcher': `
                <div>
                    <label for="institution" class="block text-sm font-medium text-gray-700 mb-1">Institution</label>
                    <input type="text" id="institution" name="institution" placeholder="Research Institute" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="research_area" class="block text-sm font-medium text-gray-700 mb-1">Research Area</label>
                    <input type="text" id="research_area" name="research_area" placeholder="Artificial Intelligence" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="research_id" class="block text-sm font-medium text-gray-700 mb-1">Research ID (e.g., ORCID)</label>
                    <input type="text" id="research_id" name="research_id" placeholder="0000-0000-0000-0000" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="experience" class="block text-sm font-medium text-gray-700 mb-1">Experience (Years)</label>
                    <input type="number" id="experience" name="experience" placeholder="5" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="cv_publications" class="block text-sm font-medium text-gray-700 mb-1">CV or Publications (Optional)</label>
                    <input type="file" id="cv_publications" name="cv_publications" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
            `,
            'academic': `
                <div>
                    <label for="academic_institution" class="block text-sm font-medium text-gray-700 mb-1">Academic Institution</label>
                    <input type="text" id="academic_institution" name="academic_institution" placeholder="University of Example" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <input type="text" id="department" name="department" placeholder="Computer Science" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="designation" class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                    <input type="text" id="designation" name="designation" placeholder="Professor" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="academic_email" class="block text-sm font-medium text-gray-700 mb-1">Academic Email</label>
                    <input type="email" id="academic_email" name="academic_email" placeholder="academic@example.com" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
            `,
            'contributor': `
                <div>
                    <label for="organization_name" class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
                    <input type="text" id="organization_name" name="organization_name" placeholder="Tech Solutions Inc." class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="type_of_data" class="block text-sm font-medium text-gray-700 mb-1">Type of Data Provided</label>
                    <input type="text" id="type_of_data" name="type_of_data" placeholder="Financial, Scientific, etc." class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="contact_info" class="block text-sm font-medium text-gray-700 mb-1">Contact Information</label>
                    <input type="text" id="contact_info" name="contact_info" placeholder="Phone or additional email" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
            `,
            'curator': `
                <div>
                    <label for="area_of_expertise" class="block text-sm font-medium text-gray-700 mb-1">Area of Expertise</label>
                    <input type="text" id="area_of_expertise" name="area_of_expertise" placeholder="Data Curation, Biology, etc." class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="curator_institution" class="block text-sm font-medium text-gray-700 mb-1">Institution</label>
                    <input type="text" id="curator_institution" name="curator_institution" placeholder="Data Science Institute" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="curator_experience" class="block text-sm font-medium text-gray-700 mb-1">Experience (Years)</label>
                    <input type="number" id="curator_experience" name="curator_experience" placeholder="7" class="form-input mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
            `
            // 'normal_user' has no additional fields
        };

        userCategorySelect.addEventListener('change', function() {
            const selectedCategory = this.value;
            dynamicFieldsDiv.innerHTML = userCategoryFields[selectedCategory] || '';
            // Reapply Tailwind focus styles to newly added inputs
            dynamicFieldsDiv.querySelectorAll('input, select').forEach(input => {
                input.classList.add('focus:ring-blue-500', 'focus:border-blue-500', 'transition', 'duration-150', 'ease-in-out');
            });
        });

        // The JavaScript for form submission is now removed as per your request.
        // The form will submit directly to register.php via its 'action' and 'method' attributes.
    </script>
</body>
</html>