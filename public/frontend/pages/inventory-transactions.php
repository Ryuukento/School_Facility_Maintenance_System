<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$target = '/School_Facility_Maintenance_System/frontend/pages/inventory.php';
header('Location: ' . $target, true, 302);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=/School_Facility_Maintenance_System/frontend/pages/inventory.php">
    <title>Redirecting...</title>
</head>
<body>
<script>
window.location.replace('/School_Facility_Maintenance_System/frontend/pages/inventory.php');
</script>
</body>
</html>
<?php exit; ?>

</body>
</html>
