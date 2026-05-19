<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Correct path to db_connect.php
include(__DIR__ . '/../db_connect.php');  // Correcting the path to the database connection

// Fetch all photos from the gallery
$sql = "SELECT * FROM gallery";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery | Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <?php include 'includes/topbar.php'; ?>
        <div class="content-wrapper">
            <h2>Gallery</h2>

            <!-- Photo Gallery Section -->
            <section class="section-padding2">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <h2 class="section-title"><span>Photo Gallery</span></h2>
                        </div>
                    </div>

                    <div class="row">
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="col-md-4 gallery-item">
                                <a href="<?php echo $row['photo_path']; ?>" title="<?php echo $row['photo_title']; ?>" class="img-zoom">
                                    <div class="gallery-box">
                                        <div class="gallery-img">
                                            <img src="../<?php echo $row['photo_path']; ?>" class="img-fluid mx-auto d-block" alt="Gallery Image">
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../js/main.js"></script>
</body>
</html>

<?php $conn->close(); ?>
