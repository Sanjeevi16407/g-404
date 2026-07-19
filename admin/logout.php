<?php
/**
 * Admin logout session destroyer
 */
session_start();
$_SESSION = array();
session_destroy();
header("Location: index.php");
exit;
?>
