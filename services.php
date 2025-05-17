<?php include "header.php"; ?>
<div class="container my-4">
  <h1 class="text-center mb-4">Our Services</h1>
  <div class="row">
    <!-- Filters Sidebar -->
    <div class="col-lg-3 mb-4"> 
      <div class="filter-container">
        <h4>Filters</h4>
        <div class="mb-3">
          <label for="search" class="form-label">Search</label>
          <input type="text" id="search" class="form-control" placeholder="Service name">
        </div>
        <div class="mb-3">
          <label for="filterCategory" class="form-label">Category</label>
          <select id="filterCategory" class="form-control">
            <option value="">All</option>
            <option value="Home Repair">Home Repair</option>
            <option value="Digital Marketing">Digital Marketing</option>
            <option value="Event Planning">Event Planning</option>
            <option value="Tutoring">Tutoring</option>
          </select>
        </div> 
        <div class="mb-3">
          <label for="minPrice" class="form-label">Min Price ($)</label>
          <input type="number" id="minPrice" class="form-control" placeholder="0" min="0">
        </div>
        <div class="mb-3">
          <label for="maxPrice" class="form-label">Max Price ($)</label>
          <input type="number" id="maxPrice" class="form-control" placeholder="1000" min="0">
        </div>
        <button id="resetFilters" class="btn btn-secondary w-100">Reset Filters</button>
      </div>
    </div>
    <!-- Services Display Area -->
    <div class="col-lg-9">
      <div class="row" id="servicesContainer">
        <!-- Service cards loaded via Ajax -->
      </div>
    </div>
  </div>
</div>
<script>
$(document).ready(function(){
  function fetchServices(){
    var data = {
      search: $("#search").val(),
      category: $("#filterCategory").val(),
      minPrice: $("#minPrice").val(),
      maxPrice: $("#maxPrice").val()
    };
    $.ajax({
      url: "fetch_services.php",
      method: "GET",
      data: data,
      success: function(response){
        $("#servicesContainer").html(response);
      }
    });
  }
  fetchServices();
  $("#search, #filterCategory, #minPrice, #maxPrice").on("keyup change", function(){
    fetchServices();
  });
  $("#resetFilters").click(function(){
    $("#search").val("");
    $("#filterCategory").val("");
    $("#minPrice").val("");
    $("#maxPrice").val("");
    fetchServices();
  });
});
</script>
<?php include "footer.php"; ?>