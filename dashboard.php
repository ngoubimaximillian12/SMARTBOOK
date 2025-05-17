<?php include "header.php"; ?>

<?php 
if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
include "smartbook_db_con.php";
 
$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$alertMsg  = "";

// If a provider updates booking status
if($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'updateBooking'){
  if($user_type === 'provider'){
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];
    $allowed    = ['pending','confirmed','completed','cancelled'];
    
    if(in_array($new_status, $allowed)){
      // Check that booking belongs to this provider
      $check = $conn->query("
        SELECT b.booking_id, b.hours, b.customer_id 
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        WHERE b.booking_id = $booking_id
          AND s.provider_id = $user_id
      ");
      if($check->num_rows === 1){
        $info = $check->fetch_assoc();
        $hours       = intval($info['hours']);
        $customer_id = intval($info['customer_id']);
        
        // Update the booking status
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        if($stmt->execute()){
          $alertMsg = '<div class="alert alert-success">Booking status updated successfully!</div>';
          
          // If status changed to 'completed', calculate points
          if($new_status === 'completed'){
            // Earn 1 point for every 3 hours (floored)
            $points_earned = floor($hours / 3);
            if($points_earned > 0){
              // Check if loyalty_points record exists for this customer
              $chk = $conn->prepare("SELECT points FROM loyalty_points WHERE user_id=?");
              $chk->bind_param("i", $customer_id);
              $chk->execute();
              $chk->store_result();
              
              if($chk->num_rows > 0){
                // update existing record
                $chk->bind_result($existingPoints);
                $chk->fetch();
                $chk->close();
                $up = $conn->prepare("UPDATE loyalty_points SET points = points + ?, updated_date=NOW() WHERE user_id=?");
                $up->bind_param("ii", $points_earned, $customer_id);
                $up->execute();
                $up->close();
              } else {
                // insert a new record
                $chk->close();
                $ins = $conn->prepare("INSERT INTO loyalty_points (user_id, points, updated_date) VALUES (?,?,NOW())");
                $ins->bind_param("ii", $customer_id, $points_earned);
                $ins->execute();
                $ins->close();
              }
              $alertMsg .= "<div class='alert alert-info mt-2'>Customer earned $points_earned loyalty point(s).</div>";
            }
          }
        } else {
          $alertMsg = '<div class="alert alert-danger">Failed to update booking status. Try again.</div>';
        }
        $stmt->close();
      } else {
        $alertMsg = '<div class="alert alert-danger">Invalid booking or not authorized.</div>';
      }
    } else {
      $alertMsg = '<div class="alert alert-danger">Invalid status.</div>';
    }
  } else {
    $alertMsg = '<div class="alert alert-danger">You are not authorized to update booking status.</div>';
  }
}

// --------------------------------------------------
// Gather dashboard data (cards, charts, calendars)
// --------------------------------------------------

$totalPayments = 0;
$today = date('Y-m-d');

// For customers
if($user_type === 'customer'){
  $r = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE customer_id = $user_id");
  $row = $r->fetch_assoc(); 
  $total_bookings = $row['total'] ?? 0;

  $r = $conn->query("SELECT COUNT(*) AS total FROM reviews WHERE customer_id = $user_id");
  $row = $r->fetch_assoc(); 
  $total_reviews = $row['total'] ?? 0;

  $r = $conn->query("SELECT points FROM loyalty_points WHERE user_id = $user_id");
  $row = $r->fetch_assoc(); 
  $loyalty_points = $row['points'] ?? 0;

  $res = $conn->query("
    SELECT SUM(s.price) AS totalPayment 
    FROM bookings b 
    JOIN services s ON b.service_id = s.service_id 
    WHERE b.customer_id = $user_id 
      AND b.status = 'completed'
  ");
  $pay = $res->fetch_assoc();
  $totalPayments = $pay['totalPayment'] ?? 0;

  $filter = "b.customer_id = $user_id";

// For providers
} else {
  $r = $conn->query("SELECT COUNT(*) AS total FROM services WHERE provider_id = $user_id");
  $row = $r->fetch_assoc(); 
  $total_services = $row['total'] ?? 0;

  $r = $conn->query("
    SELECT COUNT(*) AS total 
    FROM bookings b 
    JOIN services s ON b.service_id = s.service_id 
    WHERE s.provider_id = $user_id
  ");
  $row = $r->fetch_assoc(); 
  $total_bookings = $row['total'] ?? 0;

  $r = $conn->query("
    SELECT AVG(r.rating) AS avg_rating 
    FROM reviews r 
    JOIN bookings b ON r.booking_id = b.booking_id 
    JOIN services s ON b.service_id = s.service_id 
    WHERE s.provider_id = $user_id
  ");
  $row = $r->fetch_assoc(); 
  $avg_rating = $row['avg_rating'] ? round($row['avg_rating'],1) : 0;

  $res = $conn->query("
    SELECT SUM(s.price) AS totalPayment
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE s.provider_id = $user_id
      AND b.status = 'completed'
  ");
  $pay = $res->fetch_assoc();
  $totalPayments = $pay['totalPayment'] ?? 0;

  $filter = "s.provider_id = $user_id";
}

// Load FullCalendar events (confirmed, completed)
if($user_type === 'customer'){
  $bookingQuery = "
    SELECT booking_start, booking_end, status 
    FROM bookings
    WHERE customer_id = $user_id
      AND status IN ('confirmed','completed')
  ";
} else {
  $bookingQuery = "
    SELECT b.booking_start, b.booking_end, b.status
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE s.provider_id = $user_id
      AND b.status IN ('confirmed','completed')
  ";
}
$bookingEvents = [];
$calendarRows = $conn->query($bookingQuery);
while($b = $calendarRows->fetch_assoc()){
  $bookingEvents[] = $b;
}
$bookingEventsJson = json_encode($bookingEvents);

// Last 7 days (daily chart)
$dailyData = [];
for($i=0; $i<7; $i++){
  $day = date("Y-m-d", strtotime("-$i days"));
  $dailyData[$day] = 0;
}
$conn->query("SET time_zone = '+00:00'"); 
$q = $conn->query("
  SELECT DATE(booking_start) AS bday, COUNT(*) AS c
  FROM bookings b
  JOIN services s ON b.service_id = s.service_id
  WHERE $filter
    AND booking_start >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
  GROUP BY DATE(booking_start)
");
while($row = $q->fetch_assoc()){
  $k = $row['bday'];
  if(isset($dailyData[$k])){
    $dailyData[$k] = $row['c'];
  }
}
$dailyLabels = array_reverse(array_keys($dailyData));
$dailyCounts = array_reverse(array_values($dailyData));

// Last 6 months (monthly chart)
$monthlyData = [];
for($i=5; $i>=0; $i--){
  $m = date("Y-m", strtotime("-$i months"));
  $monthlyData[$m] = 0;
}
$q2 = $conn->query("
  SELECT DATE_FORMAT(booking_start, '%Y-%m') AS ym, COUNT(*) AS c
  FROM bookings b
  JOIN services s ON b.service_id = s.service_id
  WHERE $filter
    AND booking_start >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 6 MONTH), '%Y-%m-01')
  GROUP BY DATE_FORMAT(booking_start, '%Y-%m')
");
while($row = $q2->fetch_assoc()){
  $m = $row['ym'];
  if(isset($monthlyData[$m])){
    $monthlyData[$m] = $row['c'];
  }
}
$monthlyLabels = array_keys($monthlyData);
$monthlyCounts = array_values($monthlyData);

// Build table-based calendar (for the current month)
$currentYear  = date("Y");
$currentMonth = date("m");
$firstDayOfMonth = strtotime("$currentYear-$currentMonth-01");
$daysInMonth = date("t", $firstDayOfMonth);
$startWeekday = date("N", $firstDayOfMonth);
$tableCalBookings = [];
$startMonth = "$currentYear-$currentMonth-01";
$endMonth   = "$currentYear-$currentMonth-$daysInMonth 23:59:59";
$q3 = $conn->query("
  SELECT DATE(booking_start) AS bdate, COUNT(*) AS c
  FROM bookings b
  JOIN services s ON b.service_id = s.service_id
  WHERE $filter
    AND booking_start >= '$startMonth'
    AND booking_start <= '$endMonth'
  GROUP BY DATE(booking_start)
");
while($row = $q3->fetch_assoc()){
  $d = date("j", strtotime($row['bdate']));
  $tableCalBookings[$d] = $row['c'];
}

// Bookings Table
if($user_type === 'customer'){
  $bookingsTableQ = $conn->query("
    SELECT b.booking_id, b.booking_start, b.booking_end, b.status, b.hours,
           s.service_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE b.customer_id = $user_id
    ORDER BY b.booking_start DESC
  ");
} else {
  $bookingsTableQ = $conn->query("
    SELECT b.booking_id, b.booking_start, b.booking_end, b.status, b.hours,
           s.service_name, u.full_name AS customer_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN users u ON b.customer_id = u.user_id
    WHERE s.provider_id = $user_id
    ORDER BY b.booking_start DESC
  ");
}
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.table-calendar {
  width: 100%;
  border-collapse: collapse;
}
.table-calendar th, .table-calendar td {
  width: 14.28%;
  border: 1px solid #ddd;
  height: 80px;
  vertical-align: top;
  text-align: right;
  padding: 5px;
  position: relative;
}
.table-calendar td .badge {
  position: absolute;
  left: 5px;
  bottom: 5px;
}
.table-calendar th {
  background: #f8f9fa;
}
</style>

<div class="container my-5">
  <h1 class="mb-4">Dashboard</h1>
  <?php echo $alertMsg; ?>
  <div class="row">
    <?php if($user_type === 'customer'): ?>
      <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
          <div class="card-body">
            <h5 class="card-title">Total Bookings</h5>
            <p class="card-text display-6"><?php echo $total_bookings; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
          <div class="card-body">
            <h5 class="card-title">Total Reviews</h5>
            <p class="card-text display-6"><?php echo $total_reviews; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
          <div class="card-body">
            <h5 class="card-title">Loyalty Points</h5>
            <p class="card-text display-6"><?php echo $loyalty_points; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
          <div class="card-body">
            <h5 class="card-title">Total Paid</h5>
            <p class="card-text display-6">$<?php echo number_format($totalPayments,2); ?></p>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
          <div class="card-body">
            <h5 class="card-title">Services Registered</h5>
            <p class="card-text display-6"><?php echo $total_services; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
          <div class="card-body">
            <h5 class="card-title">Bookings Received</h5>
            <p class="card-text display-6"><?php echo $total_bookings; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
          <div class="card-body">
            <h5 class="card-title">Avg. Rating</h5>
            <p class="card-text display-6"><?php echo $avg_rating ?? 0; ?>/5</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
          <div class="card-body">
            <h5 class="card-title">Total Earned</h5>
            <p class="card-text display-6">$<?php echo number_format($totalPayments,2); ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Bookings in Last 7 Days</h5>
          <canvas id="dailyChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Bookings by Month (Last 6 Months)</h5>
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- FullCalendar Section -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Calendar of Accepted Bookings</h5>
      <div id="calendar"></div>
    </div>
  </div>

  <!-- Table-based Calendar (Current Month) -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Current Month Bookings</h5>
      <?php
      $dowLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
      $blankStart = $startWeekday - 1; 
      $totalCells = $blankStart + $daysInMonth;
      $rows = ceil($totalCells / 7);
      ?>
      <table class="table-calendar">
        <thead>
          <tr>
            <?php foreach($dowLabels as $d){ echo "<th>$d</th>"; } ?>
          </tr>
        </thead>
        <tbody>
          <?php for($r=0;$r<$rows;$r++): ?>
          <tr>
            <?php for($c=0;$c<7;$c++):
              $cellIndex = $r*7 + $c;
              if($cellIndex < $blankStart || ($cellIndex - $blankStart + 1) > $daysInMonth){
                echo "<td></td>";
              } else {
                $thisDay = $cellIndex - $blankStart +1;
                $countHere = $tableCalBookings[$thisDay] ?? 0;
                echo "<td>$thisDay";
                if($countHere > 0){
                  echo "<span class='badge bg-primary'>$countHere</span>";
                }
                echo "</td>";
              }
            endfor; ?>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Bookings Table -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">All Bookings</h5>
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Service</th>
              <?php if($user_type === 'provider'): ?>
                <th>Customer</th>
              <?php endif; ?>
              <th>Booking Start</th>
              <th>Booking End</th>
              <th>Hours</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while($b = $bookingsTableQ->fetch_assoc()): ?>
              <tr>
                <td><?php echo $b['booking_id']; ?></td>
                <td><?php echo htmlspecialchars($b['service_name']); ?></td>
                <?php if($user_type === 'provider'): ?>
                  <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                <?php endif; ?>
                <td><?php echo date("Y-m-d H:i", strtotime($b['booking_start'])); ?></td>
                <td><?php echo date("Y-m-d H:i", strtotime($b['booking_end'])); ?></td>
                <td><?php echo $b['hours']; ?></td>
                <td>
                  <?php if($user_type === 'provider'): ?>
                    <form method="post" action="dashboard.php">
                      <input type="hidden" name="form_type" value="updateBooking">
                      <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                      <select name="status" class="form-select form-select-sm d-inline w-auto me-2">
                        <?php
                          $statuses = ['pending','confirmed','completed','cancelled'];
                          foreach($statuses as $s){
                            $selected = ($s === $b['status']) ? 'selected' : '';
                            echo "<option value='$s' $selected>$s</option>";
                          }
                        ?>
                      </select>
                      <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                    </form>
                  <?php else: ?>
                    <?php echo $b['status']; ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Daily Chart
const dailyLabels = <?php echo json_encode($dailyLabels); ?>;
const dailyCounts = <?php echo json_encode($dailyCounts); ?>;
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
  type: 'line',
  data: {
    labels: dailyLabels,
    datasets: [{
      label: 'Daily Bookings',
      data: dailyCounts,
      borderColor: 'rgba(75, 192, 192, 1)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      fill: true,
      tension: 0.3
    }]
  },
  options: {
    responsive: true
  }
});

// Monthly Chart
const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
const monthlyCounts = <?php echo json_encode($monthlyCounts); ?>;
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
  type: 'bar',
  data: {
    labels: monthlyLabels,
    datasets: [{
      label: 'Monthly Bookings',
      data: monthlyCounts,
      backgroundColor: 'rgba(153, 102, 255, 0.6)',
      borderColor: 'rgba(153, 102, 255, 1)',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: { beginAtZero: true }
    }
  }
});

// FullCalendar
const bookingEvents = <?php echo $bookingEventsJson; ?>;
document.addEventListener('DOMContentLoaded', function() {
  let calendarEl = document.getElementById('calendar');
  let calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: bookingEvents.map(e => ({
      title: e.status === 'confirmed' ? 'Confirmed' : 'Completed',
      start: e.booking_start,
      end: e.booking_end,
      color: (e.status === 'confirmed') ? '#28a745' : '#007bff'
    }))
  });
  calendar.render();
});
</script>

<?php include "footer.php"; ?>