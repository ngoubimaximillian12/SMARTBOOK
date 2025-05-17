<?php include "header.php"; ?>

<?php 
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'provider') { 
    header("Location: login.php"); 
    exit; 
}

include "smartbook_db_con.php";
$alert = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_name = trim($_POST['service_name']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $working_days = $_POST['working_days'];
    $errors = [];

    // Validation
    if (strlen($service_name) < 3) { $errors[] = "Service name must be at least 3 characters long."; }
    if (empty($category)) { $errors[] = "Please select a category."; }
    if (strlen($description) < 10) { $errors[] = "Description should be at least 10 characters long."; }
    if (!is_numeric($price) || $price < 0) { $errors[] = "Please enter a valid price."; }

    // Image Upload
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        $allowed = ["jpg" => "image/jpeg", "png" => "image/png", "gif" => "image/gif"];
        $filename = $_FILES['service_image']['name'];
        $filetype = $_FILES['service_image']['type'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['service_image']['tmp_name'], $targetFile)) {
        die("File uploaded successfully!");
    } else {
        die("Failed to upload file.");
    }

        //if (!array_key_exists($ext, $allowed) || $allowed[$ext] != $filetype) {
          //  $errors[] = "Please select a valid image (JPEG, PNG, GIF).";
        //} else {
          //  $service_image = "uploads/" . time() . "_" . basename($filename);
           // move_uploaded_file($_FILES['service_image']['tmp_name'], $service_image);
        //}
    } else {
        $errors[] = "Service image is required.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO services (provider_id, service_name, category, description, icon, price, working_days) VALUES (?,?,?,?,?,?,?)");
        $provider_id = $_SESSION['user_id'];
        $stmt->bind_param("issssss", $provider_id, $service_name, $category, $description, $service_image, $price, $working_days);

        if ($stmt->execute()) {
            $alert = '<div class="alert alert-success mt-3" role="alert">Service registered successfully!</div>';
        } else {
            $errors[] = "Registration failed. Try again.";
        }

        $stmt->close();
    }

    // Display error messages
    if (!empty($errors)) {
        $alert = '<div class="alert alert-danger mt-3" role="alert">' . implode("<br>", $errors) . '</div>';
    }
}
?>

<div class="container">
    <div class="form-container">
        <h2 class="form-title">Register Your Service</h2>

        <?php if (!empty($alert)) { echo $alert; } ?>

        <form id="registerServiceForm" method="post" action="register_service.php" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="service_name" class="form-label">Service Name</label>
                <input type="text" class="form-control" id="service_name" name="service_name" required>
                <div class="error" id="serviceNameError"></div>
            </div>

            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-control" id="category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Home Repair">Home Repair</option>
                    <option value="Digital Marketing">Digital Marketing</option>
                    <option value="Event Planning">Event Planning</option>
                    <option value="Tutoring">Tutoring</option>
                </select>
                <div class="error" id="categoryError"></div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                <div class="error" id="descriptionError"></div>
            </div>

            <div class="mb-3">
                <label for="service_image" class="form-label">Service Image</label>
                <input type="file" class="form-control" id="service_image" name="service_image" accept="image/*" required>
                <div class="error" id="serviceImageError"></div>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price ($)</label>
                <input type="number" class="form-control" id="price" name="price" required step="0.01" min="0">
                <div class="error" id="priceError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Working Days</label>
                <div class="checkbox-group">
                    <?php
                    $days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
                    foreach ($days as $index => $day) {
                        echo '
                        <div class="form-check form-check-inline">
                            <input class="form-check-input working-day" type="checkbox" id="day' . ($index + 1) . '" value="1" checked>
                            <label class="form-check-label" for="day' . ($index + 1) . '">' . $day . '</label>
                        </div>';
                    }
                    ?>
                </div>
                <input type="hidden" name="working_days" id="working_days" value="1111111">
            </div>

            <button type="submit" class="btn btn-primary w-100">Register Service</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function(){
    function updateWorkingDays(){
        var workingDays = '';
        $('.working-day').each(function(){
            workingDays += $(this).is(':checked') ? '1' : '0';
        });
        $('#working_days').val(workingDays);
    }

    $('.working-day').change(updateWorkingDays);

    $("#service_name").on('blur', function(){
        $("#serviceNameError").html($(this).val().trim().length < 3 ? "Service name must be at least 3 characters long." : "");
    });

    $("#category").on('blur', function(){
        $("#categoryError").html($(this).val() == "" ? "Please select a category." : "");
    });

    $("#description").on('blur', function(){
        $("#descriptionError").html($(this).val().trim().length < 10 ? "Description should be at least 10 characters long." : "");
    });

    $("#price").on('blur', function(){
        $("#priceError").html(($(this).val() == "" || parseFloat($(this).val()) < 0) ? "Please enter a valid price." : "");
    });

    $("#service_image").on('change', function(){
        var file = this.files[0];
        if(file){
            var fileType = file.type;
            var validImageTypes = ["image/jpeg", "image/png", "image/gif"];
            $("#serviceImageError").html($.inArray(fileType, validImageTypes) < 0 ? "Please select a valid image (JPEG, PNG, GIF)." : "");
        }
    });

    $("#registerServiceForm").submit(function(e){
        updateWorkingDays();
        if ($(".error").text().trim().length > 0) { 
            e.preventDefault(); 
        }
    });
});
</script>

<?php include "footer.php"; ?>