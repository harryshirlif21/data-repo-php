<?php
// payment.php
// This is a placeholder page for payment processing.

session_start();

// Basic check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$dataset_id = $_GET['dataset_id'] ?? 'N/A'; // Get dataset ID from URL parameter

// In a real application, you would integrate a payment gateway here.
// You would also fetch dataset details based on $dataset_id to show payment amount etc.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Data Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .payment-container {
            max-width: 600px;
            width: 100%;
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        h1 {
            font-size: 2.25rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        p {
            color: #4b5563;
            margin-bottom: 1rem;
        }
        .btn-primary {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #4f46e5;
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out;
            margin-top: 1.5rem;
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }
        .btn-back {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.6rem 1.2rem;
            background-color: #6b7280;
            color: white;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out;
        }
        .btn-back:hover {
            background-color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1>Complete Your Payment</h1>
        <p>You are attempting to download Dataset ID: <strong><?php echo htmlspecialchars($dataset_id); ?></strong>.</p>
        <p>Please complete the payment process to access this dataset.</p>
        <p class="text-lg font-semibold text-green-700 mt-4">Amount Due: $9.99</p>
        <p class="text-sm text-gray-500">
            (This is a placeholder. In a real application, you'd have payment form fields here.)
        </p>
        <a href="dashboard.php" class="btn-primary">Proceed to Payment Gateway (Simulated)</a>
        <br>
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
</body>
</html>
