<?php
 session_start();
 if (!isset($_SESSION['user_id'])) {
  header("Location: ./index.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>National College(Autonomous), Trichy - 620 001.</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../SUPPORTFILES/CSS/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../SUPPORTFILES/CSS/style.css" rel="stylesheet">
    
</head>
<body>
<div class="container-fluid position-relative d-flex p-0">
        <!-- Sidebar Start -->
        <?php
            include('../SUPPORTFILES/COMMON/Sidebar.php');
        ?>
        <!-- Sidebar End -->
        <div class="content" style="background-color: #eee;">
            <!-- Navbar Start -->
            <?php
               include('../SUPPORTFILES/COMMON/Navbar.php');
            ?>
            <!-- Navbar End -->
            
            <!-- Student's Profile Form View -->
            
            <div class="container py-5">
                <!-- Top Menu -->
                <div class="row">
                    <div class="col">
                        <nav aria-label="breadcrumb" class="bg-body-tertiary rounded-3 p-3 mb-3">
                            <ol class="breadcrumb mb-0">
                               <li class="breadcrumb-item"> Home </li>
                               <li class="breadcrumb-item" aria-current="page">Dash Board</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                
                <?php echo $_SESSION['user_id']; ?>
                <?php echo $_SESSION['emp_code']; ?>
                <?php echo $_SESSION['email']; ?>
                <?php echo $_SESSION['logged_in']; ?>
                
                
            </div>
                  <!-- Footer -->
            <div style="color:#DE3163" class="footer-copyright text-center py-2">© 2025 National College, Trichy - 620 001.</div>
        </div>     
</div>
    <!-- JavaScript Libraries -->
<script src="../SUPPORTFILES/JS/jquery-3.4.1.min.js"></script>
<script src="../SUPPORTFILES/JS/bootstrap.bundle.min.js"></script>
<script src="../SUPPORTFILES/JS/main.js"></script>
</body>
</html>
 