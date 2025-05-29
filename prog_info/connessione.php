<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "banca";

// Crea connessione
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Controlla connessione
if (!$conn) {
    die("Connessione fallita: " . mysqli_connect_error());
}
?>