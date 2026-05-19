<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Pages | Dashboard</title>
  <link rel="stylesheet" href="./css/style.css">
  <script src="./js/main.js"></script>

  <style>
    .content-wrapper { padding: 30px; }
    .top-header { display: flex; justify-content: space-between; align-items: center; }
    .top-header h2 { margin: 0; }
    .edit-btn {
      background-color: #b19777;
      color: black;
      padding: 10px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td {
      padding: 12px;
      border: 1px solid #ccc;
      vertical-align: top;
      text-align: left;
    }
    th { background-color: #b19777; color: black; }
  </style>
</head>

<body>
  <div class="container">
    <?php include('includes/sidebar.php'); ?>
    <div class="main">
      <?php include('includes/topbar.php'); ?>

      <div class="content-wrapper">
        <div class="top-header">
          <h2>Manage Pages</h2>
        </div>

        <table>
          <thead>
            <tr>
              <th>Sl. No</th>
              <th>Page Name</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>Home Page</td>
              <td><a href="edit_page.php?page=home" class="edit-btn">Edit</a></td>
            </tr>
            <tr>
              <td>2</td>
              <td>About Page</td>
              <td><a href="edit_page.php?page=about" class="edit-btn">Edit</a></td>
            </tr>
            <tr>
              <td>3</td>
              <td>Accommodation Page</td>
              <td><a href="edit_page.php?page=accommodation" class="edit-btn">Edit</a></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="./js/main.js"></script>
</body>
</html>
