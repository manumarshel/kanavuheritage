<?php
session_start();
include '../includes/connect.php'; // Your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to check user credentials, update table and column names as per your structure
    $query = "SELECT id, username, password FROM admin WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    // If user exists and password matches
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Redirect to the admin dashboard (index.php)
        header("Location: index.php");
        exit();
    } else {
        // If credentials are incorrect, show an error message
        $_SESSION['login_error'] = "Invalid username or password!";
        header("Location: login.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Icon Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body, html {
      height: 100%;
      margin: 0;
      font-family: Arial, sans-serif;
    }

    .bg-container {
      background-image: url('./imgs/banner.jpg');
      background-size: cover;
      background-position: center;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-box {
      background-color: rgba(239, 228, 228, 0.7);
      padding: 40px;
      border-radius: 15px;
      text-align: center;
      max-width: 400px;
      width: 100%;
    }

    .login-box img {
      max-width: 200px;
      margin-bottom: 20px;
    }

    .login-form {
      display: none;
    }

    .login-message {
      display: block;
    }

    .login-btn {
      margin-top: 20px;
    }

    .error-msg {
      color: red;
      margin-bottom: 15px;
    }

    .formbutton {
      background-color: #ffc107; /* Bright yellow */
      color: #212529; /* Dark text */
      padding: 10px 20px;
      font-weight: 600;
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease-in-out;
    }

    .formbutton:hover {
      background-color: #fd7e14; /* Vivid orange */
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .formbutton2 {
      background-color: #fd7e14; /* Bright yellow */
      color: #212529; /* Dark text */
      padding: 10px 20px;
      font-weight: 600;
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(224, 222, 222, 0.1);
      transition: all 0.3s ease-in-out;
      text-decoration: none; /* Removes underline */
    }

    .formbutton2:hover {
      background-color: #ffc107; /* Vivid orange */
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
  </style>
</head>
<body>

  <div class="bg-container">
    <div class="login-box">
      <div id="messageContainer" class="login-message" style="<?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'display:none;' : ''; ?>">
        <img src="imgs/logo.png" alt="Temple Logo" style="max-width: 150px; margin-bottom: 20px;">
        <h3 class="text-danger">Login Required</h3>
        <button class="btn formbutton2 login-btn" onclick="showLogin()">Login</button>
      </div>

      <div id="formContainer" class="login-form" style="<?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'display:block;' : ''; ?>">
        <img src="./imgs/logo.png" alt="Logo">

        <?php
        // Show login error if exists
        if (isset($_SESSION['login_error'])) {
            echo "<div class='error-msg'>{$_SESSION['login_error']}</div>";
            unset($_SESSION['login_error']);
        }
        ?>

        <form method="POST">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required />
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required />
            </div>
            <button type="submit" class="btn formbutton2 w-100">Login</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function showLogin() {
      document.getElementById("messageContainer").style.display = "none";
      document.getElementById("formContainer").style.display = "block";
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

