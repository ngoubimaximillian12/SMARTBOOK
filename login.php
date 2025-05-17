<?php include "header.php"; ?>

<?php 
include "smartbook_db_con.php"; 
$alert = "";
if($_SERVER["REQUEST_METHOD"]=="POST"){
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $errors = [];
  if(empty($email)) { $errors[] = "Email is required."; }
  if(empty($password)) { $errors[] = "Password is required."; }
  if(empty($errors)){
    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows==1){
      $stmt->bind_result($user_id, $password_hash, $role);
      $stmt->fetch();
      if(password_verify($password, $password_hash)){
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_type'] = $role;
        header("Location: index.php");
        exit;
      } else { $errors[] = "Invalid email or password."; }
    } else { $errors[] = "Invalid email or password."; }
    $stmt->close();
  }
  if(!empty($errors)){
    $alert = '<div class="alert alert-danger" role="alert">'.implode("<br>",$errors).'</div>';
  }
}
?>

<div class="container">
  <div class="form-container">
    <h2 class="form-title">Login</h2>
    <?php if(!empty($alert)){ echo $alert; } ?>
    <form id="loginForm" method="post" action="login.php">
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" required>
        <div class="error" id="emailError"></div>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <div class="error" id="passwordError"></div>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <p class="text-center mt-3">Don't have an account? <a href="register.php">Register here</a></p>
  </div>
</div>
<script>
$(document).ready(function(){
  $("#email").on('blur',function(){
    var email = $(this).val().trim();
    var pat = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    $("#emailError").html( pat.test(email) ? "" : "Please enter a valid email address." );
  });
  $("#password").on('blur',function(){
    $("#passwordError").html($(this).val()=="" ? "Please enter your password." : "");
  });
  $("#loginForm").submit(function(e){
    if($(".error").text().trim().length>0){ e.preventDefault(); }
  });
});
</script> 
<?php include "footer.php"; ?>
