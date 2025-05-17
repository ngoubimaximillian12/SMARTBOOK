<?php include "header.php"; ?>

<?php 
if(!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

include "smartbook_db_con.php";
$alert = "";
$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT full_name, email, phone, address, profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $phone, $address, $profile_picture);
$stmt->fetch();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_full_name = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $new_address = trim($_POST['address']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $errors = [];

    // Validation
    if (strlen($new_full_name) < 3) { $errors[] = "Full name must be at least 3 characters."; }
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email address."; }
    if (!preg_match("/^[0-9]{7,15}$/", $new_phone)) { $errors[] = "Invalid phone number."; }
    if (empty($new_address)) { $errors[] = "Address cannot be empty."; }
    if (!empty($new_password) && strlen($new_password) < 6) { $errors[] = "Password must be at least 6 characters."; }
    if (!empty($new_password) && $new_password !== $confirm_password) { $errors[] = "Passwords do not match."; }

    $new_profile_picture = $profile_picture;

    // Profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ["jpg" => "image/jpeg", "png" => "image/png", "gif" => "image/gif"];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = $_FILES['profile_picture']['type'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!array_key_exists($ext, $allowed) || $allowed[$ext] != $filetype) {
            $errors[] = "Please select a valid image (JPEG, PNG, GIF).";
        } else {
            $new_profile_picture = "uploads/" . time() . "_" . basename($filename);
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $new_profile_picture);
        }
    }

    // If no errors, update the database
    if (empty($errors)) {
        if (!empty($new_password)) {
            $pass_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, profile_picture = ?, password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $new_full_name, $new_email, $new_phone, $new_address, $new_profile_picture, $pass_hash, $user_id);
            $profile_picture=$new_profile_picture;
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, profile_picture = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $new_full_name, $new_email, $new_phone, $new_address, $new_profile_picture, $user_id);
        }

        if ($stmt->execute()) {
            $alert = '<div class="alert alert-success mt-3" role="alert">Profile updated successfully!</div>';
        } else {
            $errors[] = "Update failed. Try again.";
        }

        $stmt->close();
    }

    if (!empty($errors)) {
        $alert = '<div class="alert alert-danger mt-3" role="alert">' . implode("<br>", $errors) . '</div>';
    }
}
?>

<div class="container">
    <div class="form-container">
        <h2 class="form-title">Update Profile</h2>

        <?php if (!empty($alert)) { echo $alert; } ?>

        <form id="updateProfileForm" method="post" action="update_profile.php" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4 text-center">
                    <img src="<?php echo $profile_picture ? $profile_picture : 'https://via.placeholder.com/150'; ?>" 
                        alt="Profile Picture" 
                        class="img-fluid rounded-circle profile-picture-preview" 
                        id="profilePreview">

                    <div class="mt-2">
                        <label for="profile_picture" class="form-label">Change Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                        <div class="error" id="profilePictureError"></div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                        <div class="error" id="nameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <div class="error" id="emailError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                        <div class="error" id="phoneError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                        <div class="error" id="addressError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password <small>(leave blank if not changing)</small></label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="error" id="passwordError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        <div class="error" id="confirmPasswordError"></div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#profile_picture").on("change", function(){
        var file = this.files[0];
        if(file){
            var type = file.type;
            var validTypes = ["image/jpeg","image/png","image/gif"];
            $("#profilePictureError").html($.inArray(type, validTypes)<0 ? "Please select a valid image (JPEG, PNG, GIF)." : "");
            if($.inArray(type, validTypes)>=0){
                var reader = new FileReader();
                reader.onload = function(e){ $("#profilePreview").attr("src", e.target.result); }
                reader.readAsDataURL(file);
            }
        }
    });
});
</script>

<?php include "footer.php"; ?>