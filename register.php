<?php 
include "smartbook_db_con.php";
$alert = "";
if($_SERVER["REQUEST_METHOD"]=="POST"){
  $full_name = trim($_POST['full_name']);
  $email = trim($_POST['email']);
  $role = $_POST['role'];
  $phone = trim($_POST['phone']);
  $address = trim($_POST['address']);
  $password = $_POST['password'];
  $confirm = $_POST['confirm_password'];
  $errors = [];
  if(strlen($full_name)<3){ $errors[] = "Full name must be at least 3 characters."; }
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $errors[] = "Invalid email address."; }
  if(!preg_match("/^[0-9]{7,15}$/",$phone)){ $errors[] = "Invalid phone number."; }
  if(empty($address)){ $errors[] = "Address cannot be empty."; }
  if(strlen($password)<6){ $errors[] = "Password must be at least 6 characters."; }
  if($password !== $confirm){ $errors[] = "Passwords do not match."; }
  $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
  $stmt->bind_param("s",$email);
  $stmt->execute();
  $stmt->store_result();
  if($stmt->num_rows>0){ $errors[] = "Email already exists."; }
  $stmt->close();
  $profile_picture = "";
  if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error']==0){
    $allowed = ["jpg"=>"image/jpeg","png"=>"image/png","gif"=>"image/gif"];
    $filename = $_FILES['profile_picture']['name'];
    $filetype = $_FILES['profile_picture']['type'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if(!array_key_exists($ext, $allowed) || $allowed[$ext] != $filetype){
      $errors[] = "Select a valid image (jpg, png, gif).";
    } else {
      $profile_picture = "uploads/".time()."_".basename($filename);
      move_uploaded_file($_FILES['profile_picture']['tmp_name'],$profile_picture);
    }
  }
  if(empty($errors)){
    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, phone, address, profile_picture) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssss",$full_name,$email,$pass_hash,$role,$phone,$address,$profile_picture);
    if($stmt->execute()){
      header("Location: login.php?registered=1");
      exit;
    } else { $errors[] = "Registration failed. Try again."; }
    $stmt->close();
  }
  if(!empty($errors)){
    $alert = '<div class="alert alert-danger" role="alert">'.implode("<br>",$errors).'</div>';
  }
}
?>
<?php include "header.php"; ?>
<div class="container">
  <div class="form-container">
    <h2 class="form-title">Register</h2>
    <?php if(!empty($alert)){ echo $alert; } ?>
    <form id="registerForm" method="post" action="register.php" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="full_name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="full_name" name="full_name" required>
        <div class="error" id="nameError"></div>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" required>
        <div class="error" id="emailError"></div>
      </div>
      <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select class="form-control" id="role" name="role">
          <option value="customer" selected>Customer</option>
          <option value="provider">Provider</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" required>
        <div class="error" id="phoneError"></div>
      </div>
      <div class="mb-3">
        <label for="address" class="form-label">Address</label>
        <input type="text" class="form-control" id="address" name="address" required>
        <div class="error" id="addressError"></div>
      </div>
      <div class="mb-3">
        <label for="profile_picture" class="form-label">Profile Picture</label>
        <input type="file" class="form-control" id="profile_picture" name="profile_picture">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <div class="error" id="passwordError"></div>
      </div>
      <div class="mb-3">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        <div class="error" id="confirmPasswordError"></div>
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
    <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
  </div>
</div>
<script>
$(document).ready(function(){
  $("#full_name").on('blur',function(){
    var n = $(this).val().trim();
    $("#nameError").html(n.length<3 ? "Full name must be at least 3 characters." : "");
  });
  $("#email").on('blur',function(){
    var email = $(this).val().trim();
    var pat = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    $("#emailError").html(pat.test(email) ? "" : "Please enter a valid email address.");
  });
  $("#phone").on('blur',function(){
    var p = $(this).val().trim();
    var pat = /^[0-9]{7,15}$/;
    $("#phoneError").html(pat.test(p) ? "" : "Please enter a valid phone number (7-15 digits).");
  });
  $("#address").on('blur',function(){
    var a = $(this).val().trim();
    $("#addressError").html(a=="" ? "Address cannot be empty." : "");
  });
  $("#password").on('blur',function(){
    $("#passwordError").html($(this).val().length<6 ? "Password must be at least 6 characters long." : "");
  });
  $("#confirm_password").on('blur',function(){
    var pass = $("#password").val();
    $("#confirmPasswordError").html($(this).val()!==pass ? "Passwords do not match." : "");
  });
  $("#registerForm").submit(function(e){
    if($(".error").text().trim().length>0){ e.preventDefault(); }
  });
});
</script>
<?php include "footer.php"; ?> 