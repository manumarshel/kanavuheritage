<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Thank You</title>

  <!-- ✅ FIX: redirect to real file -->
  <meta http-equiv="refresh" content="3;url=contact.php">

  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: url('img/gallery/01.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .msg {
      font-size: 22px;
      color: #b19777;
      background: rgba(255, 255, 255, 0.1);
      padding: 30px;
      border: 1px solid #fff;
      border-radius: 10px;
      max-width: 600px;
      width: 90%;
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
    h2, p { color: #b19777; margin: 10px 0; }
  </style>
</head>
<body>
  <div class="msg">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.7.1/dist/dotlottie-wc.js" type="module"></script>
    <dotlottie-wc src="img/Checked.json" speed="1" style="width: 300px; height: 300px;" mode="forward" autoplay></dotlottie-wc>

    <h2>Thank you! Your enquiry has been submitted successfully.</h2>
    <p>You will be redirected back in 3 seconds...</p>
  </div>
</body>
</html>
