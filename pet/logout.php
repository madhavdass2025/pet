<?php
require_once 'includes/auth_functions.php';
session_destroy();
header("Location: index.php");
exit();
?>
