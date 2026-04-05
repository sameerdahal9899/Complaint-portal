<?php
require 'session_config.php'; // Centralized session configuration
require 'db.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['complaint_id'])) {
    $newStatus = trim($_POST['status'] ?? '');
    $complaintId = (int)($_POST['complaint_id'] ?? 0);  // Cast to integer for safety
    $filterCategory = isset($_POST['category']) ? trim($_POST['category']) : 'All';  // Get category from form

    // Validate inputs
    if ($complaintId <= 0 || empty($newStatus)) {
        header("Location: admin_all_complaints.php?category=" . urlencode($filterCategory) . "&error=invalid_input");
        exit();
    }

    // Get current status before update
    $checkStmt = $pdo->prepare("SELECT status FROM complaints WHERE id = ?");
    $checkStmt->execute([$complaintId]);
    $currentRow = $checkStmt->fetch();
    $currentStatus = $currentRow ? $currentRow['status'] : null;

    error_log("Update attempt - ID: $complaintId, Current: $currentStatus, New: $newStatus, Category: $filterCategory");

    // When admin sets status to "Resolved", automatically change it to "Resolved from Admin"
    // so the student gets a confirmation dialog
    if ($newStatus === 'Resolved') {
        $newStatus = 'Resolved from Admin';
        error_log("Changed to: $newStatus");
    }

    // Update the complaint status
    $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $result = $stmt->execute([$newStatus, $complaintId]);
    
    error_log("Update result - Success: " . ($result ? 'true' : 'false') . ", Rows: " . $stmt->rowCount());

    // Verify the update was successful
    if ($result && $stmt->rowCount() > 0) {
        error_log("Update successful for complaint ID: $complaintId");
        header("Location: admin_all_complaints.php?category=" . urlencode($filterCategory) . "&updated=success");
        exit();
    } else {
        error_log("Update failed for complaint ID: $complaintId");
        header("Location: admin_all_complaints.php?category=" . urlencode($filterCategory) . "&error=failed");
        exit();
    }
}

// Fetch all complaints
$stmt = $pdo->query("SELECT c.*, u.name 
                     FROM complaints c 
                     JOIN users u ON c.user_id = u.id 
                     ORDER BY c.id DESC");
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define all categories
$allCategories = [
    "Academic","Facility","Administration","Canteen","Sports",
    "Library","Hostel","Transport","Examination","Others"
];

// Calculate counts for each category
$categories = [];
foreach ($allCategories as $cat) {
    $categories[$cat] = 0;
}
foreach ($complaints as $c) {
    if (isset($categories[$c['category']])) {
        $categories[$c['category']]++;
    }
}

// Handle filter (works for both category and status)
$filter = $_GET['category'] ?? 'All';
$filteredComplaints = $complaints;
if ($filter !== '' && $filter !== 'All') {
    if ($filter === 'Resolved') {
        // Show both "Resolved" and "Resolved from Admin" for Resolved filter
        $filteredComplaints = array_filter($complaints, fn($c) => $c['status'] === 'Resolved' || $c['status'] === 'Resolved from Admin');
    } else {
        $filteredComplaints = array_filter($complaints, fn($c) => $c['category'] === $filter || $c['status'] === $filter);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - All Complaints</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="hamburger admin" onclick="toggleSidebar()">&#9776;</div>
  <div class="sidebar admin" id="sidebar">
    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="admin_all_complaints.php" class="active"><i class="fas fa-users"></i> All Complaints</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main admin-main" id="main">
    <h2>All Complaints (Admin)</h2>

    <!-- Success/Error Messages -->
    <?php if(isset($_GET['updated']) && $_GET['updated'] === 'success'): ?>
      <p class="success">✓ Complaint status updated successfully!</p>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
      <p class="error">✗ Error: <?= htmlspecialchars($_GET['error']) ?>. Please try again.</p>
    <?php endif; ?>

    <!-- Category Capsules -->
    <div class="capsules">
      <a href="admin_all_complaints.php?category=All" class="capsule">
        All (<?= count($complaints) ?>)
      </a>
      <?php foreach($categories as $cat => $count): ?>
        <a href="admin_all_complaints.php?category=<?= urlencode($cat) ?>" class="capsule">
          <?= htmlspecialchars($cat) ?> (<?= $count ?>)
        </a>
      <?php endforeach; ?>

      <!-- ✅ Capsules for statuses -->
      <a href="admin_all_complaints.php?category=Pending" class="capsule">
        Pending (<?= count(array_filter($complaints, fn($c) => $c['status'] === 'Pending')) ?>)
      </a>
      <a href="admin_all_complaints.php?category=In Progress" class="capsule">
        In Progress (<?= count(array_filter($complaints, fn($c) => $c['status'] === 'In Progress')) ?>)
      </a>
      <a href="admin_all_complaints.php?category=Resolved" class="capsule">
        Resolved (<?= count(array_filter($complaints, fn($c) => $c['status'] === 'Resolved' || $c['status'] === 'Resolved from Admin')) ?>)
      </a>
    </div>

    <!-- Complaints Table -->
    <table>
      <tr>
        <th>ID</th><th>Student</th><th>Title</th><th>Category</th><th>Description</th><th>Date</th><th>Status</th><th>Image</th><th>Action</th>
      </tr>
      <?php if(empty($filteredComplaints)): ?>
        <tr>
          <td colspan="9" style="text-align:center;">No complaints found for <?= htmlspecialchars($filter) ?></td>
        </tr>
      <?php else: ?>
        <?php foreach($filteredComplaints as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['id']) ?></td>
          <td><?= !empty($c['anonymous']) && $c['anonymous']==1 ? "Anonymous" : htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars($c['title']) ?></td>
          <td><?= htmlspecialchars($c['category']) ?></td>
          <td><?= htmlspecialchars($c['description']) ?></td>
          <td><?= htmlspecialchars($c['created_at']) ?></td>
          <td>
            <?php if($c['status'] === 'Resolved'): ?>
              <span class="badge resolved">Resolved</span>
            <?php elseif($c['status'] === 'Resolved from Admin'): ?>
              <span class="badge admin-resolved">Resolved from Admin</span>
            <?php elseif($c['status'] === 'In Progress'): ?>
              <span class="badge progress">In Progress</span>
            <?php else: ?>
              <span class="badge pending">Pending</span>
            <?php endif; ?>
          </td>
                    <td>
            <?php if(!empty($c['image'])): ?>
              <a href="#" onclick="openModal('<?= htmlspecialchars($c['image']) ?>')">1</a>
            <?php else: ?>
              0
            <?php endif; ?>
          </td>
          <td>
            <form method="post" action="admin_all_complaints.php">
              <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
              <input type="hidden" name="category" value="<?= htmlspecialchars($filter) ?>">
              <select name="status" required>
                <option value="">-- Select Status --</option>
                <option value="Pending" <?= $c['status']=="Pending"?"selected":"" ?>>Pending</option>
                <option value="In Progress" <?= $c['status']=="In Progress"?"selected":"" ?>>In Progress</option>
                <option value="Resolved" <?= ($c['status']=="Resolved from Admin" || $c['status']=="Resolved")?"selected":"" ?>>Resolved</option>
              </select>
              <button type="submit" name="update_status" value="1">Update</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>

  <!-- Modal for fullscreen image -->
  <div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");
      
      sidebar.classList.toggle("active");
      hamburger.classList.toggle("active");
      main.classList.toggle("shifted");
      // Save sidebar state to localStorage
      localStorage.setItem("sidebarOpen", sidebar.classList.contains("active") ? "true" : "false");
    }

    // Restore sidebar state on page load
    window.addEventListener("DOMContentLoaded", function() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");
      if (localStorage.getItem("sidebarOpen") === "true") {
        sidebar.classList.add("active");
        hamburger.classList.add("active");
        main.classList.add("shifted");
      }
    });

    // Close sidebar when clicking outside of it
    document.addEventListener("click", function(e) {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");
      
      // If sidebar is open and click is outside sidebar and not on hamburger
      if (sidebar.classList.contains("active") && 
          !sidebar.contains(e.target) && 
          !hamburger.contains(e.target)) {
        sidebar.classList.remove("active");
        hamburger.classList.remove("active");
        main.classList.remove("shifted");
        localStorage.setItem("sidebarOpen", "false");
      }
    });

    // ✅ Open modal with image
    function openModal(imageSrc) {
      const modal = document.getElementById("imageModal");
      const modalImg = document.getElementById("modalImage");
      modal.style.display = "block";
      modalImg.src = imageSrc;
    }

    // ✅ Close modal
    function closeModal() {
      document.getElementById("imageModal").style.display = "none";
    }

    // ✅ Close modal when clicking outside image
    window.onclick = function(event) {
      const modal = document.getElementById("imageModal");
      if (event.target === modal) {
        modal.style.display = "none";
      }
    }
  </script>
</body>
</html>
