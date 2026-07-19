<?php
/**
 * Student portal session destroyer
 */
session_start();
$_SESSION = array();
session_destroy();
header("Location: index.php");
exit;
?>
