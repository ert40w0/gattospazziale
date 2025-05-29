<?php
session_start();
$_SESSION['logout_success'] = "Logout eseguito con successo.";
session_unset();
session_destroy();
header("Location: login.php");
exit();
