<?php
date_default_timezone_set('America/New_York');
$conexion = mysqli_connect("localhost:3307", "usuario", "", "nutrition");
if (mysqli_connect_errno())
{
echo "Failed to connect to MySQL: " . mysqli_connect_error();
}else{
echo "";
}
?>
