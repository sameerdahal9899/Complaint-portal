<?php

// Extend session lifetime
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 0);    // until browser closes

session_start();
require 'db.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    // ✅ Handle image upload
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

    if (!isset($error) && $title !== "" && $description !== "" && $category !== "") {
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, title, category, description, status, anonymous, image) VALUES (?, ?, ?, ?, 'Pending', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $category, $description, $anonymous, $imagePath]);

        header("Location: my_complaints.php?submitted=success");
        exit();
    } else {
        if (!isset($error)) {
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
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Arial, sans-serif;
      background: url('abc.png') no-repeat center center fixed;
      background-size: cover;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(6px);
      z-index: -1;
    }
    .logo { position: fixed; top: 20px; right: 20px; width: 80px; cursor: pointer; z-index: 1001; }
    .hamburger {
      position: fixed; top: 25px; left: 25px; font-size: 28px;
      color: white; cursor: pointer; z-index: 1001;
      transition: color 0.3s ease;
    }
    .hamburger.active { color: #0c4f97; }
    .sidebar {
      position: fixed; top: 0; left: -250px; width: 250px; height: 100%;
      background: rgba(255,255,255,0.95); padding-top: 80px;
      transition: left 0.4s ease; z-index: 1000;
    }
    .sidebar a { display: block; padding: 14px 20px; color: #0c4f97; text-decoration: none; font-weight: bold; }
    .sidebar a.active { background: #0c4f97; color: white; border-left: 4px solid #1a73e8; }
    .main { margin-left: 0; padding: 100px 40px; color: white; transition: margin-left 0.4s ease; display: flex; justify-content: center; }
    .form-card { background: rgba(255,255,255,0.95); color: #333; padding: 30px; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.3); max-width: 600px; width: 100%; }
    .form-card h2 { margin: 0 0 20px 0; color: #0c4f97; text-align: center; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
    .form-group textarea { resize: vertical; min-height: 100px; }
    .submit-btn { background: #0c4f97; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; }
    .submit-btn:hover { background: #1a73e8; }
    .sidebar.active { left: 0; }
    .main.shifted { margin-left: 250px; }
    .error { color: red; text-align: center; }
    .checkbox-row { display: flex; align-items: center; gap: 8px; margin: 15px 0; }
    .checkbox-row input[type="checkbox"] { margin: 0; }
    .tooltip { position: relative; display: inline-block; cursor: pointer; color: #0c4f97; }
    .tooltip .tooltiptext {
      visibility: hidden; width: 240px; background: #333; color: #fff;
      text-align: center; border-radius: 6px; padding: 8px;
      position: absolute; z-index: 1; bottom: 125%; left: 50%;
      margin-left: -120px; opacity: 0; transition: opacity 0.3s;
    }
    .tooltip:hover .tooltiptext { visibility: visible; opacity: 1; }
  </style>
</head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<body>
  <a href="index.php"><img src="logo.png" alt="Logo" class="logo"></a>
  <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>

  <div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="submit_complaint.php" class="active"><i class="fas fa-edit"></i> Submit Complaint</a>
    <a href="my_complaints.php"><i class="fas fa-user"></i> My Complaints</a>
    <a href="all_complaints.php"><i class="fas fa-users"></i> All Complaints</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main" id="main">
    <div class="form-card">
      <h2>Submit a Complaint</h2>
      <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
      <form action="submit_complaint.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="title">Complaint Title</label>
          <input type="text" id="title" name="title" placeholder="Enter complaint title" required>
        </div>
        <div class="form-group">
          <label for="description">Complaint Description</label>
          <textarea id="description" name="description" placeholder="Describe your issue" required></textarea>
        </div>
        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category" required>
            <option value="">Select category</option>
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

        <!-- ✅ Image upload field -->
        <div class="form-group">
          <label for="image">Attach an Image (optional)</label>
          <input type="file" id="image" name="image" accept="image/*">
          <small style="color:#555;">Max size: 2MB (JPG, PNG, GIF)</small>
        </div>

        <!-- ✅ Checkbox row aligned left -->
        <div class="checkbox-row">
          <input type="checkbox" id="anonymous" name="anonymous" value="1">
          <label for="anonymous">Submit Anonymously</label>
          <span class="tooltip">
            <i class="fas fa-info-circle"></i>
            <span class="tooltiptext">If checked, your name will not be visible to the admin.</span>
          </span>
        </div>
        <button type="submit" class="submit-btn">Submit Complaint</button>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");

      sidebar.classList.toggle("active");
      main.classList.toggle("shifted");
      hamburger.classList.toggle("active");
    }

    document.getElementById("main").addEventListener("click", function() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");

      if (sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
        main.classList.remove("shifted");
        hamburger.classList.remove("active");
      }
    });
  </script>
</body>
</html>
