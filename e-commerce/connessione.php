<?php
$servername_ecommerce = "localhost";
$username_ecommerce = "root";
$password_ecommerce = "";
$dbname_ecommerce = "e_commerce";

// Crea connessione per e_commerce
$conn_ecommerce = new mysqli($servername_ecommerce, $username_ecommerce, $password_ecommerce, $dbname_ecommerce);

// Controlla connessione
if ($conn_ecommerce->connect_error) {
    die("Connessione a e_commerce fallita: " . $conn_ecommerce->connect_error);
}
?>