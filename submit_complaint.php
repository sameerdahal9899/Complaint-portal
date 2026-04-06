<?php
require 'session_config.php'; // Centralized session configuration
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;

        // Limit file size (2 MB)
        if ($_FILES["image"]["size"] > 2 * 1024 * 1024) {
            $error = "Image size must be less than 2MB.";
        } else {
            $allowedTypes = ['image/jpeg','image/png','image/gif'];
            if (in_array($_FILES["image"]["type"], $allowedTypes)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                    $imagePath = $targetFile;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Only JPG, PNG, and GIF files are allowed.";
            }
        }
    }

    if ($error === null && $title !== "" && $description !== "" && $category !== "") {
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, title, category, description, status, anonymous, image) VALUES (?, ?, ?, ?, 'Pending', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $category, $description, $anonymous, $imagePath]);

        header("Location: my_complaints.php?submitted=success");
        exit();
    } else {
        if ($error === null) {
            $error = "Please fill in all required fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint - Complaint Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: url('abc.png') no-repeat center center fixed;
            background-size: cover;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
            z-index: -1;
        }

        /* Logo - Fixed Position */
        .logo {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 80px;
            cursor: pointer;
            z-index: 1001;
        }

        /* Hamburger Menu - Fixed Position */
        .hamburger {
            position: fixed;
            top: 25px;
            left: 25px;
            font-size: 28px;
            color: white;
            cursor: pointer;
            z-index: 1001;
            transition: color 0.3s ease;
        }

        .hamburger:hover {
            color: #1a73e8;
        }

        .hamburger.active {
            color: #0c4f97;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding-top: 80px;
            transition: left 0.4s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.12);
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar a {
            display: block;
            padding: 14px 20px;
            color: #0c4f97;
            text-decoration: none;
            font-weight: bold;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #0c4f97;
            color: white;
            border-left-color: #1a73e8;
        }

        /* Main Content - Centered */
        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            padding: 20px;
            transition: margin-left 0.4s ease;
        }
        .form-card {
            background: rgba(255, 255, 255, 0.98);
            color: #333;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 600px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card h2 {
            margin: 0 0 30px 0;
            color: #0c4f97;
            text-align: center;
            font-size: 28px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0c4f97;
            box-shadow: 0 0 8px rgba(12, 79, 151, 0.3);
        }

        /* Checkbox Row */
        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .checkbox-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            margin: 0;
        }

        .checkbox-row label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
            color: #0c4f97;
        }

        .tooltip i {
            font-size: 14px;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 12px;
            white-space: normal;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Submit Button */
        .submit-btn {
            background: #0c4f97;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #1a73e8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(12, 79, 151, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Error Message */
        .error {
            color: #d32f2f;
            background: #ffebee;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            border-left: 4px solid #d32f2f;
        }

        /* Helper Text */
        small {
            display: block;
            margin-top: 6px;
            color: #666;
            font-size: 13px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-card {
                padding: 25px;
                max-width: 95%;
            }

            .form-card h2 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .main {
                padding: 20px 15px;
            }

            .tooltip .tooltiptext {
                width: 150px;
                margin-left: -75px;
            }
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 20px;
            }

            .form-card h2 {
                font-size: 20px;
            }

            .submit-btn {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Logo -->
    <a href="dashboard.php"><img src="logo.png" alt="Logo" class="logo"></a>

    <!-- Hamburger Menu -->
    <div class="hamburger" id="hamburger">&#9776;</div>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="submit_complaint.php" class="active"><i class="fas fa-edit"></i> Submit Complaint</a>
        <a href="my_complaints.php"><i class="fas fa-user"></i> My Complaints</a>
        <a href="all_complaints.php"><i class="fas fa-users"></i> All Complaints</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content - Centered Form -->
    <div class="main" id="main">
        <div class="form-card">
            <h2>Submit a Complaint</h2>

            <!-- Error Message -->
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <!-- Form -->
            <form action="submit_complaint.php" method="POST" enctype="multipart/form-data">
                <!-- Title Field -->
                <div class="form-group">
                    <label for="title">Complaint Title *</label>
                    <input type="text" id="title" name="title" placeholder="Enter complaint title" required>
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Complaint Description *</label>
                    <textarea id="description" name="description" placeholder="Describe your issue in detail..." required></textarea>
                </div>

                <!-- Category Field -->
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">-- Select a Category --</option>
                        <option value="Academic">Academic</option>
                        <option value="Facility">Facility</option>
                        <option value="Administration">Administration</option>
                        <option value="Canteen">Canteen</option>
                        <option value="Sports">Sports</option>
                        <option value="Library">Library</option>
                        <option value="Hostel">Hostel</option>
                        <option value="Transport">Transport</option>
                        <option value="Examination">Examination</option>
                        <option value="Others">Others</option>
                    </select>
                </div>

                <!-- Image Upload Field -->
                <div class="form-group">
                    <label for="image">Attach an Image (Optional)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small>Max size: 2MB | Supported formats: JPG, PNG, GIF</small>
                </div>

                <!-- Anonymous Checkbox -->
                <div class="checkbox-row">
                    <input type="checkbox" id="anonymous" name="anonymous" value="1">
                    <label for="anonymous">Submit Anonymously</label>
                    <span class="tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span class="tooltiptext">Your name will not be visible to the admin if checked</span>
                    </span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function toggleSidebar(e) {
            if (e) e.stopPropagation();
            const sidebar = document.getElementById("sidebar");
            const main = document.getElementById("main");
            const hamburger = document.querySelector(".hamburger");
            sidebar.classList.toggle("active");
            hamburger.classList.toggle("active");
            main.classList.toggle("shifted");
            // Save sidebar state to localStorage
            localStorage.setItem("sidebarOpen", sidebar.classList.contains("active") ? "true" : "false");
        }

        function closeSidebar() {
            const sidebar = document.getElementById("sidebar");
            const main = document.getElementById("main");
            const hamburger = document.querySelector(".hamburger");
            sidebar.classList.remove("active");
            hamburger.classList.remove("active");
            main.classList.remove("shifted");
            localStorage.setItem("sidebarOpen", "false");
        }

        // Initialize everything after DOM is ready
        window.addEventListener("DOMContentLoaded", function() {
            // Clear localStorage if user just logged in
            const params = new URLSearchParams(window.location.search);
            if (params.has('just_logged_in')) {
                localStorage.removeItem("sidebarOpen");
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            const hamburger = document.querySelector(".hamburger");
            const sidebar = document.getElementById("sidebar");
            const main = document.getElementById("main");
            
            // Attach hamburger click listener
            hamburger.addEventListener("click", toggleSidebar);
            
            // Close sidebar when clicking on sidebar links
            const sidebarLinks = sidebar.querySelectorAll("a");
            sidebarLinks.forEach(link => {
                link.addEventListener("click", closeSidebar);
            });
            
            // Restore sidebar state from localStorage
            if (localStorage.getItem("sidebarOpen") === "true") {
                sidebar.classList.add("active");
                hamburger.classList.add("active");
                main.classList.add("shifted");
            }
            
            // Close sidebar only when clicking on main content (not sidebar links)
            main.addEventListener("click", function(e) {
                // Don't close if click is on a sidebar link or inside sidebar
                if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                    if (sidebar.classList.contains("active")) {
                        closeSidebar();
                    }
                }
            });
        });
    </script>
</body>
</html>
