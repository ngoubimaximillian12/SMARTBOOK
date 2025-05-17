<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SmartBook: A Dynamic Service Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Pacifico&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS v5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
  </head>
  <body>
    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <div class="container">
        <a class="navbar-brand" href="#">SmartBook</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" aria-controls="navbarNav" 
                aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
          <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>

            <?php if(isset($_SESSION['user_id'])): ?>
              <!-- Links for Logged-in Users -->
              <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
              <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
              <li class="nav-item">
                  <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'provider') : ?>
                      <a class="nav-link" href="register_service.php">Add Service</a>
                  <?php endif; ?>
              </li> 
              <li class="nav-item"><a class="nav-link" href="update_profile.php">Profile</a></li>
              <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>

            <?php else: ?>
              <!-- Links for Guests -->
              <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
              <li class="nav-item"><a class="nav-link" href="register.php">Signup</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </body>
</html>
