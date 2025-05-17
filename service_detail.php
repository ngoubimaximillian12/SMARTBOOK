<?php include "header.php"; ?>

<?php
if(!isset($_GET['service_id'])) { header("Location: index.php"); exit; }
include "smartbook_db_con.php";
$service_id = intval($_GET['service_id']);

$bookingSuccess = "";
$feedbackSuccess = "";
$alert = "";

// Ensure user is logged in
$user_id   = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_role = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

// Fetch service details (including working_days)
$stmt = $conn->prepare("
  SELECT provider_id, service_name, category, description, icon, price, working_days 
  FROM services 
  WHERE service_id = ?
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($provider_id, $service_name, $category, $description, $icon, $price, $working_days);
if(!$stmt->fetch()){
  $stmt->close();
  header("Location: index.php");
  exit;
}
$stmt->close();

// Process form submissions
if($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST['form_type'])) {
  if(!$user_id) {
    $alert = '<div class="alert alert-danger">Please login first.</div>';
  } else {
    // Booking submission
    if($_POST['form_type'] == 'booking'){
      if($user_role !== 'customer'){
        $alert = '<div class="alert alert-danger">Only customers can book services.</div>';
      } else {
        $date   = $_POST['bookingDate'];
        $time   = $_POST['bookingTime'];
        $hours  = intval($_POST['hours']);
        $notes  = trim($_POST['bookingNotes']);
        $usePoints = isset($_POST['use_points']) ? intval($_POST['use_points']) : 0;
        
        if(empty($date) || empty($time) || $hours < 1){
          $alert = '<div class="alert alert-danger">Date, time, and hours are required.</div>';
        } else {
          // Check if the service is available on the selected day
          $dayOfWeek = date('N', strtotime($date)); // 1=Mon ... 7=Sun
          $idx = $dayOfWeek - 1; 
          if($working_days[$idx] !== '1'){
            $alert = '<div class="alert alert-danger">This service is not available on the selected day.</div>';
          } else {
            // Calculate base price = service price * hours
            $basePrice = $price * $hours;
            
            // Fetch user's current loyalty points from loyalty_points table
            $lpstmt = $conn->prepare("SELECT points FROM loyalty_points WHERE user_id=?");
            $lpstmt->bind_param("i", $user_id);
            $lpstmt->execute();
            $lpstmt->bind_result($user_points);
            $lpstmt->fetch();
            $lpstmt->close();
            
            $usePoints = ($usePoints > $user_points) ? $user_points : $usePoints;
            $discount = $usePoints; // £1 discount per point
            $finalAmount = $basePrice - $discount;
            if($finalAmount < 0) { $finalAmount = 0; }
            
            // Combine date and time into booking start/end times
            $start = date("Y-m-d H:i:s", strtotime("$date $time"));
            $end   = date("Y-m-d H:i:s", strtotime("$start +$hours hour"));
            
            // Insert booking (with hours and total_amount)
            $stmtB = $conn->prepare("
              INSERT INTO bookings 
                (customer_id, service_id, booking_start, booking_end, status, hours, total_amount)
              VALUES (?,?,?,?, 'pending', ?, ?)
            ");
            $stmtB->bind_param("isssid", $user_id, $service_id, $start, $end, $hours, $finalAmount);
            if($stmtB->execute()){
              // Deduct loyalty points if used, and update loyalty_points table
              if($usePoints > 0){
                $uStmt = $conn->prepare("UPDATE loyalty_points SET points = points - ? WHERE user_id = ?");
                $uStmt->bind_param("ii", $usePoints, $user_id);
                $uStmt->execute();
                $uStmt->close();
                // Optionally, log the transaction in loyalty_points table
                $lt = $conn->prepare("UPDATE loyalty_points SET points = ? WHERE user_id = ?");
                $negPoints = -$usePoints;
                $lt->bind_param("ii", $user_id, $negPoints);
                $lt->execute();
                $lt->close();
              }
              $bookingSuccess = '<div class="alert alert-success">Booking created successfully! Total amount: £'.number_format($finalAmount,2).'</div>';
            } else {
              $alert = '<div class="alert alert-danger">Booking failed. Try again.</div>';
            }
            $stmtB->close();
            if ($hours >= 3) {
              $points_earned = floor($hours / 3);
              $bookingSuccess .= '<div class="alert alert-info mt-2">You will earn ' . $points_earned . ' loyalty point' . ($points_earned > 1 ? 's' : '') . ' once this booking is completed!</div>';
          }
          
          }
        }
      }
    }
    // Feedback submission
    elseif($_POST['form_type'] == 'feedback'){
      if($user_id == $provider_id){
        $alert = '<div class="alert alert-danger">You cannot review your own service.</div>';
      } else {
        $rating = intval($_POST['feedbackRating']);
        $comment = trim($_POST['feedbackComment']);
        // Check for a completed booking for this service by this customer
        $stmtC = $conn->prepare("
          SELECT booking_id 
          FROM bookings 
          WHERE customer_id=? AND service_id=? AND status='completed'
          LIMIT 1
        ");
        $stmtC->bind_param("ii", $user_id, $service_id);
        $stmtC->execute();
        $stmtC->store_result();
        if($stmtC->num_rows < 1){
          $alert = '<div class="alert alert-danger">No completed booking found for feedback.</div>';
          $stmtC->close();
        } else {
          $stmtC->bind_result($b_id);
          $stmtC->fetch();
          $stmtC->close();
          $stmtI = $conn->prepare("
            INSERT INTO reviews (booking_id, customer_id, rating, review_text) 
            VALUES (?,?,?,?)
          ");
          $stmtI->bind_param("iiis", $b_id, $user_id, $rating, $comment);
          if($stmtI->execute()){
            $feedbackSuccess = '<div class="alert alert-success">Feedback submitted. Thank you!</div>';
          } else {
            $alert = '<div class="alert alert-danger">Failed to submit feedback.</div>';
          }
          $stmtI->close();
        }
      }
    }
  }
}

// Calculate average rating and fetch reviews
$avg_rating = 0;
$reviews = [];
$stmtR = $conn->prepare("
  SELECT r.rating, r.review_text, r.created_at, 'Anonymous' AS customer
  FROM reviews r
  JOIN bookings b ON r.booking_id = b.booking_id
  WHERE b.service_id=?
");
$stmtR->bind_param("i", $service_id);
$stmtR->execute();
$res = $stmtR->get_result();
$sum = 0; $count = 0;
while($row = $res->fetch_assoc()){
  $reviews[] = $row;
  $sum += $row['rating'];
  $count++;
}
if($count > 0) { $avg_rating = round($sum / $count, 1); }
$stmtR->close();

// Fetch related services (same category, excluding current service)
$related = [];
$stmt2 = $conn->prepare("
  SELECT service_id, service_name, price, icon 
  FROM services 
  WHERE category = ? AND service_id <> ? 
  LIMIT 3
");
$stmt2->bind_param("si", $category, $service_id);
$stmt2->execute();
$rr = $stmt2->get_result();
while($row = $rr->fetch_assoc()){
  $related[] = $row;
}
$stmt2->close();
?>

<div class="container my-5">
  <?php echo $bookingSuccess . $feedbackSuccess . $alert; ?>
  <!-- Service Detail Section -->
  <div class="row mb-5">
    <div class="col-md-6">
      <img src="<?php echo $icon ?: 'https://via.placeholder.com/600x350?text=Service+Image'; ?>" alt="Service Image" class="service-image">
    </div>
    <div class="col-md-6">
      <h2><?php echo htmlspecialchars($service_name); ?></h2>
      <p class="text-muted">Category: <?php echo htmlspecialchars($category); ?></p>
      <div class="mb-3">
        <?php
          if($avg_rating > 0){
            $full = floor($avg_rating);
            $half = ($avg_rating - $full) >= 0.5;
            for($i = 0; $i < $full; $i++) echo '<i class="fas fa-star"></i>';
            if($half) echo '<i class="fas fa-star-half-alt"></i>';
            echo " ($avg_rating/5)";
          } else {
            echo "No ratings yet.";
          }
        ?>
      </div>
      <h4 class="text-primary">$<?php echo htmlspecialchars($price); ?></h4>
      <p><?php echo htmlspecialchars($description); ?></p>
      <?php if($user_id && $user_role=='customer'): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bookingModal">
          <i class="fas fa-calendar-check"></i> Book Now
        </button>
      <?php elseif(!$user_id): ?>
        <div class="alert alert-info">Please <a href="login.php">login</a> to book this service.</div>
      <?php else: ?>
        <div class="alert alert-info">Only customers can book this service.</div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Ratings & Reviews Section -->
  <div class="mb-5">
    <h3 class="mb-4">Ratings & Reviews</h3>
    <?php if(count($reviews) > 0): ?>
      <?php foreach($reviews as $r): ?>
        <div class="card p-3 mb-3">
          <div class="d-flex justify-content-between">
            <div>
              <strong><?php echo htmlspecialchars($r['customer']); ?></strong>
              <div class="star-rating">
                <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
              </div>
            </div>
            <small class="text-muted"><?php echo date("M d, Y", strtotime($r['created_at'])); ?></small>
          </div>
          <p class="mt-2"><?php echo htmlspecialchars($r['review_text']); ?></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="alert alert-info">No reviews yet.</div>
    <?php endif; ?>
  </div>
  
  <!-- Feedback Form Section -->
  <?php if($user_id && $user_id != $provider_id && $user_role=='customer'): ?>
    <div class="mb-5 form-container">
      <h3 class="form-title">Provide Your Feedback</h3>
      <form id="feedbackForm" method="post" action="?service_id=<?php echo $service_id; ?>">
        <input type="hidden" name="form_type" value="feedback">
        <div class="mb-3">
          <label for="feedbackRating" class="form-label">Your Rating</label>
          <select id="feedbackRating" name="feedbackRating" class="form-control" required>
            <option value="">Select Rating</option>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Very Good</option>
            <option value="3">3 - Good</option>
            <option value="2">2 - Fair</option>
            <option value="1">1 - Poor</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="feedbackComment" class="form-label">Your Review</label>
          <textarea id="feedbackComment" name="feedbackComment" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-100">Submit Feedback</button>
      </form>
    </div>
  <?php elseif($user_id && $user_id == $provider_id): ?>
    <div class="alert alert-info">You cannot review your own service.</div>
  <?php else: ?>
    <div class="alert alert-info">Please <a href="login.php">login</a> to provide feedback.</div>
  <?php endif; ?>
  
  <!-- Related Services Section -->
  <div class="mb-5">
    <h3 class="mb-4">Related Services</h3>
    <div class="row">
      <?php if(count($related) > 0): ?>
        <?php foreach($related as $rel): 
          $rel_img = !empty($rel['icon']) ? $rel['icon'] : 'https://via.placeholder.com/400x220'; ?>
          <div class="col-md-4">
            <div class="card related-card mb-3">
              <img src="<?php echo $rel_img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($rel['service_name']); ?>">
              <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($rel['service_name']); ?></h5>
                <p class="card-text">$<?php echo htmlspecialchars($rel['price']); ?></p>
                <a href="service_detail.php?service_id=<?php echo $rel['service_id']; ?>" class="btn btn-outline-primary btn-view">
                  <i class="fas fa-eye"></i> View
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-info">No related services available.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Booking Modal -->
<?php if($user_id && $user_role=='customer'): ?>
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="bookingForm" method="post" action="?service_id=<?php echo $service_id; ?>" class="modal-content">
      <input type="hidden" name="form_type" value="booking">
      <div class="modal-header">
        <h5 class="modal-title" id="bookingModalLabel">Book Service</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body form-container form-pop-up">
        <div class="mb-3">
          <label for="bookingDate" class="form-label">Booking Date</label>
          <input type="date" name="bookingDate" id="bookingDate" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="bookingTime" class="form-label">Booking Time</label>
          <input type="time" name="bookingTime" id="bookingTime" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="hours" class="form-label">Number of Hours</label>
          <input type="number" name="hours" id="hours" class="form-control" min="1" value="1" required>
        </div>
        <div class="mb-3">
          <label for="bookingNotes" class="form-label">Additional Notes</label>
          <textarea name="bookingNotes" id="bookingNotes" class="form-control" rows="3"></textarea>
        </div>
        <?php
          // Fetch loyalty points from loyalty_points table for this user
          $lpstmt = $conn->prepare("SELECT points FROM loyalty_points WHERE user_id=?");
          $lpstmt->bind_param("i", $user_id);
          $lpstmt->execute();
          $lpstmt->bind_result($user_lp);
          $lpstmt->fetch();
          $lpstmt->close();
          if($user_lp > 0):
        ?>
          <div class="mb-3">
            <label for="use_points" class="form-label">Use Loyalty Points</label>
            <select name="use_points" id="use_points" class="form-control">
              <option value="0">0 (No discount)</option>
              <?php for($i=1; $i<=$user_lp; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
            <small class="text-muted">Each point = £1 discount.</small>
          </div>
        <?php endif; ?>
        <div class="mb-3">
          <label class="form-label">Total Amount: <span id="totalAmountDisplay">$0.00</span></label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Confirm Booking</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
  function calculateTotal(){
    let hrs = parseInt($("#hours").val()) || 0;
    let servicePrice = <?php echo $price; ?>;
    let basePrice = hrs * servicePrice;
    let usePts = parseInt($("#use_points").val()) || 0;
    let total = basePrice - usePts;
    if(total < 0) total = 0;
    $("#totalAmountDisplay").text("$" + total.toFixed(2));
  }
  $("#hours, #use_points").on("change keyup", calculateTotal);
  calculateTotal();
  
  $("#feedbackForm, #bookingForm").submit(function(e){
    // Submit the from to Server.
  });
});
</script>

<?php include "footer.php"; ?>