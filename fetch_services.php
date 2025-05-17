<?php
include "smartbook_db_con.php";
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";
$minPrice = isset($_GET['minPrice']) && is_numeric($_GET['minPrice']) ? floatval($_GET['minPrice']) : 0;
$maxPrice = isset($_GET['maxPrice']) && is_numeric($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : 1000000;
$query = "SELECT * FROM services WHERE price BETWEEN ? AND ?";
$params = [$minPrice, $maxPrice];
$types = "dd";
if(!empty($search)){
  $query .= " AND service_name LIKE ?";
  $params[] = "%".$search."%";
  $types .= "s";
}
if(!empty($category)){
  $query .= " AND category = ?";
  $params[] = $category;
  $types .= "s";
}
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$html = "";
while($row = $result->fetch_assoc()){
  $img = !empty($row['icon']) ? $row['icon'] : "https://via.placeholder.com/400x220";
  $html .= '<div class="col-md-6 col-lg-4 service-card" data-name="'.htmlspecialchars($row['service_name']).'" data-category="'.htmlspecialchars($row['category']).'" data-price="'.htmlspecialchars($row['price']).'">';
  $html .= '<div class="card">';
  $html .= '<img src="'.$img.'" class="card-img-top" alt="'.htmlspecialchars($row['service_name']).'">';
  $html .= '<div class="card-body">';
  $html .= '<h5 class="card-title">'.htmlspecialchars($row['service_name']).'</h5>';
  $html .= '<p class="card-text">$'.htmlspecialchars($row['price']).'</p>';
  $html .= '<a href="service_detail.php?service_id='.$row['service_id'].'" class="btn btn-outline-primary btn-view"><i class="fas fa-eye"></i> View Details</a>';
  $html .= '</div></div></div>';
}
echo $html;
?> 