<?php
session_start();
include("connessione.php");

if (isset($_SESSION['nome_utente'])) {
    header("Location: crea_conto.php");
    exit();
}

$errori = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = mysqli_real_escape_string($conn, $_POST['nome'] ?? '');
    $cognome = mysqli_real_escape_string($conn, $_POST['cognome'] ?? '');
    $nome_utente = mysqli_real_escape_string($conn, $_POST['nome_utente'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono'] ?? '');
    $data_nascita = $_POST['data_nascita'] ?? '';
    $indirizzo = mysqli_real_escape_string($conn, $_POST['indirizzo'] ?? '');

    if (empty($nome) || empty($cognome) || empty($nome_utente) || empty($password) || empty($email) || empty($data_nascita)) {
        $errori[] = "Tutti i campi obbligatori devono essere compilati.";
    } else {
        $verifica = mysqli_query($conn, "SELECT id FROM clienti WHERE nome_utente = '$nome_utente' OR email = '$email'");
        if (mysqli_num_rows($verifica) > 0) {
            $errori[] = "Nome utente o email già esistenti. Scegli credenziali diverse.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO clienti (nome, cognome, nome_utente, password, email, telefono, data_nascita, indirizzo)
                    VALUES ('$nome', '$cognome', '$nome_utente', '$hash', '$email', '$telefono', '$data_nascita', '$indirizzo')";

            if (mysqli_query($conn, $sql)) {
                $_SESSION['nome_utente'] = $nome_utente;
                $_SESSION['id_cliente'] = mysqli_insert_id($conn);
                header("Location: crea_conto.php");
                exit();
            } else {
                $errori[] = "Errore nella registrazione utente: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrazione - Banca Digitale</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #0e0b1f;
      color: #fff;
      min-height: 100vh;
      overflow-x: hidden;
      padding-bottom: 2rem;
    }
    canvas {
      position: fixed;
      z-index: -1;
      top: 0; left: 0;
      width: 100%; height: 100%;
      pointer-events: none;
    }
    .form-container {
      background: rgba(30, 20, 50, 0.9);
      max-width: 500px;
      margin: 5vh auto;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 0 40px rgba(170, 100, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .form-container h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #d8b4fe;
    }
    .form-group { margin-bottom: 1rem; }
    label { display: block; margin-bottom: 0.5rem; color: #fff; }
    input, textarea {
      width: 100%;
      padding: 0.75rem;
      border-radius: 5px;
      border: none;
      background-color: #1f1435;
      color: white;
      font-size: 1rem;
    }
    .btn {
      width: 100%;
      background: #7c3aed;
      color: white;
      padding: 0.75rem;
      border: none;
      font-size: 1rem;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 1rem;
      transition: background 0.3s;
    }
    .btn:hover { background: #5b21b6; }
    .error {
      background: #dc3545;
      color: white;
      padding: 0.5rem;
      border-radius: 5px;
      margin-bottom: 1rem;
      text-align: center;
    }
    .register-link {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 0.95rem;
      color: #d1d5db;
    }
    .register-link a {
      color: #a78bfa;
      text-decoration: none;
      font-weight: bold;
      margin-left: 0.25rem;
      transition: color 0.3s ease;
    }
    .register-link a:hover {
      color: #c4b5fd;
    }
  </style>
</head>
<body>
  <canvas id="bg"></canvas>
  <div class="form-container">
    <h2><i class="fas fa-user-plus"></i> Registrati</h2>

    <?php if (!empty($errori)): ?>
      <div class="error">
        <?php foreach ($errori as $e) echo "<p>$e</p>"; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Nome *</label>
        <input type="text" name="nome" required>
      </div>
      <div class="form-group">
        <label>Cognome *</label>
        <input type="text" name="cognome" required>
      </div>
      <div class="form-group">
        <label>Nome utente *</label>
        <input type="text" name="nome_utente" required>
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input type="password" name="password" required>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Telefono</label>
        <input type="text" name="telefono">
      </div>
      <div class="form-group">
        <label>Data di nascita *</label>
        <input type="date" name="data_nascita" required>
      </div>
      <div class="form-group">
        <label>Indirizzo</label>
        <input type="text" name="indirizzo">
      </div>
      <button class="btn" type="submit">Registrati</button>
    </form>

    <div class="register-link">
      Hai già un account?
      <a href="login.php">Accedi</a>
    </div>
  </div>

  <script>
    const canvas = document.getElementById("bg");
    const ctx = canvas.getContext("2d");

    function resizeCanvas() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }

    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();

    const particles = Array.from({length: 150}, () => ({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      r: Math.random() * 2 + 2,
      dx: Math.random() * 0.5 - 0.25,
      dy: Math.random() * 0.5 - 0.25
    }));

    function draw() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = "rgba(255,255,255,0.30)";
      particles.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();

        p.x += p.dx;
        p.y += p.dy;

        if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
      });
      requestAnimationFrame(draw);
    }

    draw();
  </script>
</body>
</html>
