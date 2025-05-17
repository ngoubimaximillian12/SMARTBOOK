<?php include "header.php"; ?>
<?php
include "smartbook_db_con.php";

// Fetch popular services (for example, the latest 3 services)
$query = "SELECT service_id, service_name, description, price, icon FROM services ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($query);
?> 
  <!-- Hero Section -->
  <section id="hero" class="hero">
    <div class="hero-content">
      <h1 class="display-3 fw-bold">Welcome to SmartBook</h1>
      <p class="lead mb-4">Connecting you with top-notch service providers for a seamless experience.</p>
      <a href="#services" class="btn btn-light btn-lg">Explore Services</a>
    </div>
  </section>

  <!-- Popular Services Section -->
  <section id="services" class="py-5">
    <div class="container">
      <h2 class="section-title text-center">Popular Services</h2>
      <div class="row g-4">
        <?php while($row = $result->fetch_assoc()){ 
          $img = !empty($row['icon']) ? $row['icon'] : "https://via.placeholder.com/400x220"; ?>
        <div class="col-md-4">
          <div class="card services-card">
            <img src="<?php echo $img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['service_name']); ?>">
            <div class="card-body">
              <h5 class="card-title"><?php echo htmlspecialchars($row['service_name']); ?></h5>
              <p class="card-text">
                <?php 
                    $words = explode(" ", htmlspecialchars($row['description']));
                    $short_desc = implode(" ", array_slice($words, 0, 10));
                    echo $short_desc . (count($words) > 10 ? "..." : "");
                ?>
            </p>
            </div>
            <div class="card-footer">
              <a href="service_detail.php?service_id=<?php echo $row['service_id']; ?>" class="btn btn-outline-primary w-100">Learn More</a>
            </div>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <?php
// Fetch total happy clients (users with role 'customer')
$happyClientsQuery = "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'";
$result = $conn->query($happyClientsQuery);
$row = $result->fetch_assoc();
$happyClients = $row['total'];

// Fetch total service providers (users with role 'provider')
$serviceProvidersQuery = "SELECT COUNT(*) AS total FROM users WHERE role = 'provider'";
$result = $conn->query($serviceProvidersQuery);
$row = $result->fetch_assoc();
$serviceProviders = $row['total'];

// Fetch total services booked (all bookings, or you may filter by status if desired)
$servicesBookedQuery = "SELECT COUNT(*) AS total FROM bookings";
$result = $conn->query($servicesBookedQuery);
$row = $result->fetch_assoc();
$servicesBooked = $row['total'];
?>

<!-- Statistics Section -->
<section id="stats" class="stats-section">
  <div class="container text-center">
    <h2 class="section-title">Our Impact in Numbers</h2>
    <div class="row">
      <div class="col-md-4 mb-4">
        <div class="stats"><?php echo number_format($happyClients); ?></div>
        <p>Happy Clients</p>
      </div>
      <div class="col-md-4 mb-4">
        <div class="stats"><?php echo number_format($serviceProviders); ?></div>
        <p>Service Providers</p>
      </div>
      <div class="col-md-4 mb-4">
        <div class="stats"><?php echo number_format($servicesBooked); ?></div>
        <p>Services Booked</p>
      </div>
    </div>
  </div>
</section>

  <!-- Mission Statement Section -->
  <section id="mission" class="mission">
    <div class="container">
      <h2 class="section-title">Our Mission</h2>
      <p class="lead">To create a dynamic, user-friendly platform that connects service providers with customers for seamless bookings, transparent reviews, and continuous improvement through actionable insights.</p>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section id="testimonials" class="py-5">
    <div class="container">
      <h2 class="section-title text-center">Testimonials</h2>
      <div class="row">
        <div class="col-md-6">
          <div class="testimonial-card">
            <blockquote>
              <p>"SmartBook revolutionised how I book services. The platform is user-friendly and efficient."</p>
            </blockquote>
            <footer>— Aninwede Maxwell </footer>
          </div>
        </div>
        <div class="col-md-6">
          <div class="testimonial-card">
            <blockquote> 
              <p>"With transparent reviews and seamless booking, SmartBook is my go-to solution for all service needs."</p>
            </blockquote>
            <footer>— Ngoubi Maximillian Diangha </footer>
          </div>
        </div>
      </div>
    </div>
  </section> 
<?php include "footer.php"; ?>