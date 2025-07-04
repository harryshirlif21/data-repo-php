<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dataset</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .upload-container {
            max-width: 700px;
            width: 100%;
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        h1 {
            font-size: 2.25rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        input[type="text"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            color: #1f2937;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: #f9fafb;
            cursor: pointer;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus,
        select:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #3b82f6; /* Blue-500 */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); /* Blue-200 with transparency */
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .submit-btn {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background-color: #22c55e; /* Green-500 */
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .submit-btn:hover {
            background-color: #16a34a; /* Green-600 */
            transform: translateY(-1px);
        }
        .back-btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.6rem 1.2rem;
            background-color: #6b7280; /* Gray-500 */
            color: white;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out;
        }
        .back-btn:hover {
            background-color: #4b5563; /* Gray-600 */
        }
        /* Message Box styles */
        .message-box {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .message-box.success {
            background-color: #d1fae5; /* green-100 */
            border: 1px solid #34d399; /* green-400 */
            color: #065f46; /* green-700 */
        }
        .message-box.error {
            background-color: #fee2e2; /* red-100 */
            border: 1px solid #f87171; /* red-400 */
            color: #b91c1c; /* red-700 */
        }
        .message-box.info {
            background-color: #dbeafe; /* blue-100 */
            border: 1px solid #60a5fa; /* blue-400 */
            color: #1e40af; /* blue-700 */
        }
        .message-box button {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1><B>Upload New Dataset</B></h1>
        <p class="text-center text-gray-600 mb-6">You can upload datasets for review.</p>

        <!-- Message Box for displaying messages -->
        <div id="messageBox" class="message-box hidden" role="alert">
            <span id="messageText"></span>
            <button class="close-btn" onclick="document.getElementById('messageBox').classList.add('hidden');">&times;</button>
        </div>

        <form id="uploadDatasetForm" enctype="multipart/form-data" class="space-y-4">
            <div class="form-group">
                <label for="title">Dataset Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Loading Categories...</option>
                </select>
            </div>
           <div class="form-group">
    <label for="metadata_summary">Metadata Summary (optional, must be JSON):</label>
    <textarea id="metadata_summary" name="metadata_summary" rows="4" placeholder='{"source":"KBS","year":2022}'></textarea>
</div>

            <div class="form-group">
                <label for="dataset_file">Select Dataset File:</label>
                <input type="file" id="dataset_file" name="dataset_file" required>
            </div>
            <button type="submit" class="submit-btn">Upload Dataset</button>
        </form>

        <div class="text-center">
            <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Function to display messages
        function showMessage(message, type) {
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');

            messageText.textContent = message;
            messageBox.className = 'message-box'; // Reset classes
            messageBox.classList.add(type); // Add type class (success, error, info)
            messageBox.classList.remove('hidden');

            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 5000); // Hide after 5 seconds
        }

        // Function to load categories dynamically
        async function loadCategories() {
            const categorySelect = document.getElementById('category_id');
            try {
                const response = await fetch('get_categories.php'); // New endpoint for categories
                if (!response.ok) {
                    throw new Error('Failed to fetch categories.');
                }
                const result = await response.json();

                if (result.success) {
                    categorySelect.innerHTML = '<option value="">Select a Category</option>'; // Clear loading message
                    result.data.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.name;
                        categorySelect.appendChild(option);
                    });
                } else {
                    showMessage(result.message || 'Failed to load categories.', 'error');
                    categorySelect.innerHTML = '<option value="">Error loading categories</option>';
                }
            } catch (error) {
                console.error('Error loading categories:', error);
                showMessage('An error occurred while loading categories.', 'error');
                categorySelect.innerHTML = '<option value="">Error loading categories</option>';
            }
        }

        // Event listener for form submission
        document.getElementById('uploadDatasetForm').addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const form = event.target;
            const formData = new FormData(form); // Create FormData object from the form

            // Client-side validation for metadata_summary (if not empty, must be valid JSON)
            const metadataSummaryInput = document.getElementById('metadata_summary').value.trim();
            if (metadataSummaryInput !== '') {
                try {
                    JSON.parse(metadataSummaryInput);
                } catch (e) {
                    showMessage('Metadata Summary must be valid JSON or left empty.', 'error');
                    return; // Stop submission
                }
            }

            try {
                const response = await fetch('upload_dataset_process.php', {
                    method: 'POST',
                    body: formData // FormData handles multipart/form-data automatically
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    form.reset(); // Clear the form on successful upload
                    // Optionally redirect after a short delay
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An unexpected error occurred during upload. Please try again.', 'error');
            }
        });

        // Load categories when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', loadCategories);
    </script>
</body>
</html>
