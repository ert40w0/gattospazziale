<?php
session_start();
include("connessione.php");

if (!isset($_SESSION['nome_utente'])) {
    header("Location: registrazione.php");
    exit();
}

$id_cliente = $_SESSION['id_cliente'];
$check_conto = mysqli_query($conn, "SELECT id FROM conti WHERE id_cliente = $id_cliente");
if (mysqli_num_rows($check_conto) > 0) {
    header("Location: dashboard.php");
    exit();
}

$errori = [];

function generaNumeroConto($conn) {
    do {
        $numero = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $check = mysqli_query($conn, "SELECT id FROM conti WHERE numero_conto = '$numero'");
    } while (mysqli_num_rows($check) > 0);
    return $numero;
}

$numero_casuale = generaNumeroConto($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $numero_conto = mysqli_real_escape_string($conn, $_POST['numero_conto'] ?? '');
    $saldo_input = $_POST['saldo_iniziale'] ?? '';

    if (empty($numero_conto) || strlen($numero_conto) != 4 || !ctype_digit($numero_conto)) {
        $errori[] = "Il numero conto deve essere di 4 cifre numeriche.";
    }

    if (!is_numeric($saldo_input) || floatval($saldo_input) < 0) {
        $errori[] = "Il saldo iniziale deve essere un numero positivo.";
    }

    if (empty($errori)) {
        $verifica = mysqli_query($conn, "SELECT id FROM conti WHERE numero_conto = '$numero_conto'");
        if (mysqli_num_rows($verifica) > 0) {
            $errori[] = "Numero conto già esistente. Scegli un altro numero.";
        } else {
            $tipo_conto = 'corrente';
            $saldo_iniziale = floatval($saldo_input);

            $sql = "INSERT INTO conti (id_cliente, numero_conto, saldo, saldo_iniziale, tipo_conto) 
            VALUES ($id_cliente, '$numero_conto', $saldo_iniziale, $saldo_iniziale, '$tipo_conto')";

            if (mysqli_query($conn, $sql)) {
                header("Location: dashboard.php");
                exit();
            } else {
                $errori[] = "Errore nella creazione del conto: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Crea Conto</title>
  <style>
    body {
      font-family: sans-serif;
      background-color: #0e0b1f;
      color: #fff;
      padding: 2rem;
    }
    .form-container {
      background: #1f1435;
      max-width: 400px;
      margin: auto;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(255,255,255,0.1);
    }
    input {
      width: 100%;
      padding: 0.5rem;
      margin-top: 0.5rem;
      background: #2c1d4a;
      border: none;
      border-radius: 5px;
      color: #fff;
    }
    button {
      margin-top: 1rem;
      width: 100%;
      padding: 0.75rem;
      background: #7c3aed;
      border: none;
      color: white;
      font-weight: bold;
      cursor: pointer;
      border-radius: 5px;
    }
    .error {
      color: #ff6b6b;
      margin-bottom: 1rem;
    }
    label {
      display: block;
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Crea il tuo Conto</h2>

    <?php if (!empty($errori)): ?>
      <div class="error">
        <?php foreach ($errori as $e) echo "<p>$e</p>"; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <label>Numero conto (4 cifre):</label>
      <input type="text" name="numero_conto" value="<?= htmlspecialchars($numero_casuale) ?>" 
             required maxlength="4" minlength="4"
             oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,4);">

      <label>Saldo iniziale (€):</label>
      <input type="number" name="saldo_iniziale" min="0" step="0.01" placeholder="Es: 1000.00" required>

      <button type="submit">Crea Conto</button>
    </form>
  </div>
</body>
</html>
