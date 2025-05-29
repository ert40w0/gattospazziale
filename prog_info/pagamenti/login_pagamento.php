<?php
session_start();
require_once __DIR__.'/../connessione.php'; // DB banca
$conn_ecommerce = new mysqli("localhost", "root", "", "e_commerce");
if ($conn_ecommerce->connect_error) {
    die("Connessione al database e_commerce fallita: " . $conn_ecommerce->connect_error);
}

$secret_key = 'CHIAVE_SEGRETA_CONDIVISA';

// Se già loggato nel flusso pagamento
if (isset($_SESSION['pagamento_loggato'])) {
    header("Location: processa_pagamento.php");
    exit();
}

// Verifica parametri obbligatori
$required_params = ['prezzo', 'moto_id', 'link_ritorno', 'hash'];
foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        die("Parametri mancanti");
    }
}

// Verifica hash
$hash_calcolato = hash_hmac('sha256', $_GET['moto_id'].$_GET['prezzo'], $secret_key);
if ($_GET['hash'] !== $hash_calcolato) {
    die("Manomissione parametri rilevata");
}

// Salva dati pagamento in sessione
$_SESSION['dati_pagamento'] = [
    'prezzo' => floatval($_GET['prezzo']),
    'moto_id' => intval($_GET['moto_id']),
    'link_ritorno' => $_GET['link_ritorno'],
    'expires' => time() + 900
];

// Gestione login e conferma dati
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $data_ritiro = $_POST['data_ritiro'] ?? null;
    $sede_id = $_POST['sede_id'] ?? null;

    // Validazione base
    if (!$data_ritiro || !$sede_id) {
        $error = "Completa tutti i campi.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM clienti WHERE nome_utente = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['pagamento_loggato'] = true;
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;

                // Salva anche la sede e data ritiro
                $_SESSION['dati_pagamento']['data_ritiro'] = $data_ritiro;
                $_SESSION['dati_pagamento']['sede_id'] = intval($sede_id);

                header("Location: processa_pagamento.php");
                exit();
            } else {
                $error = "Credenziali errate";
            }
        } else {
            $error = "Utente non trovato";
        }
        $stmt->close();
    }
}

// Recupera sedi da e_commerce
$sedi = [];
$sedi_result = $conn_ecommerce->query("SELECT id, nome_sede, citta FROM sedi");
if ($sedi_result) {
    while ($row = $sedi_result->fetch_assoc()) {
        $sedi[] = $row;
    }
}

// Link ritorno
$link_ritorno = $_SESSION['dati_pagamento']['link_ritorno'] ?? 'dashboard.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma Pagamento - Banca Digitale</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            max-width: 400px;
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
        input, select {
            width: 100%; padding: 0.75rem;
            border-radius: 5px; border: none;
            background-color: #1f1435; color: white;
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
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #d1d5db;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .back-btn:hover {
            color: #c4b5fd;
        }
        .info-text {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #d1d5db;
        }
    </style>
</head>
<body>
    <canvas id="bg"></canvas>
    <div class="form-container">
        <h2><i class="fas fa-credit-card"></i> Conferma Pagamento</h2>
        <p class="info-text">Inserisci le credenziali e seleziona data e sede di ritiro</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="data_ritiro">Data prevista per il ritiro</label>
                <input type="date" name="data_ritiro" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label for="sede_id">Seleziona la sede di ritiro</label>
                <select name="sede_id" required>
                    <option value="">-- Scegli una sede --</option>
                    <?php foreach ($sedi as $sede): ?>
                        <option value="<?= $sede['id'] ?>">
                            <?= htmlspecialchars($sede['nome_sede']) ?> - <?= htmlspecialchars($sede['citta']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn" type="submit">Conferma Pagamento</button>
        </form>

        <a href="<?= htmlspecialchars($link_ritorno) ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Torna indietro
        </a>
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
