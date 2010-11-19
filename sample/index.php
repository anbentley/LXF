<?php 
ob_start();
$success = include_once '../LXF/library/site.php'; 
SITE::makePage(); 
?>