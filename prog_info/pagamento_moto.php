<?php
session_start();
require_once __DIR__.'/connessione.php';

// Configurazione
$secret_key = 'TUO_SEGRETO'; // Deve essere uguale a quello usato nell'e-commerce

// 1. Verifica hash di sicurezza
$hash_calculato = hash_hmac('sha256', $_GET['moto_id'].$_GET['prezzo'], $secret_key);
if (!isset($_GET['hash']) || $hash_calculato !== $_GET['hash']) {
    die("Manomissione parametri rilevata");
}

// 2. Forza nuovo login ad ogni pagamento
session_destroy();
session_start();

// 3. Salva dati transazione in sessione (con timeout)
$_SESSION['payment_data'] = [
    'prezzo' => floatval($_GET['prezzo']),
    'moto_id' => intval($_GET['moto_id']),
    'link_sito' => $_GET['link_sito'],
    'expires' => time() + 900 // 15 minuti di validità
];

// 4. Se non loggato, redirect al login
if (!isset($_SESSION['banca_loggato'])) {
    header("Location: login.php?redirect=".urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// 5. Se loggato ma senza dati di pagamento, errore
if (!isset($_SESSION['payment_data'])) {
    die("Sessione di pagamento scaduta o non valida");
}

// 6. Recupera dati pagamento
$payment_data = $_SESSION['payment_data'];
unset($_SESSION['payment_data']);

// 7. Verifica saldo
$saldo_query = "SELECT c.saldo, c.id as conto_id 
                FROM conti c 
                JOIN clienti cl ON c.id_cliente = cl.id 
                WHERE cl.id = {$_SESSION['user_id']} 
                LIMIT 1";
$saldo_result = $conn->query($saldo_query);
$conto = $saldo_result->fetch_assoc();

if (!$conto) {
    $errore_pagamento = "Nessun conto bancario associato";
} elseif ($conto['saldo'] < $payment_data['prezzo']) {
    $errore_pagamento = "Saldo insufficiente. Disponibile: €".number_format($conto['saldo'], 2, ',', '.');
}

// 8. Processa pagamento se tutto ok
if (!isset($errore_pagamento)) {
    $conn->begin_transaction();
    try {
        // Aggiorna saldo
        $nuovo_saldo = $conto['saldo'] - $payment_data['prezzo'];
        $conn->query("UPDATE conti SET saldo = $nuovo_saldo WHERE id = {$conto['conto_id']}");
        
        // Registra transazione
        $conn->query("INSERT INTO transazioni 
                     (id_conto_mittente, importo, tipo_operazione, link_sito) 
                     VALUES ({$conto['conto_id']}, {$payment_data['prezzo']}, 'prelievo', '{$payment_data['link_sito']}')");
        
        $conn->commit();
        $pagamento_riuscito = true;
    } catch (Exception $e) {
        $conn->rollback();
        $errore_pagamento = "Errore durante il pagamento: ".$e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Pagamento</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0e0b1f;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .payment-container {
            background: rgba(30, 20, 50, 0.9);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 0 30px rgba(123, 58, 237, 0.3);
        }
        .success {
            color: #2ecc71;
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: pulse 1.5s infinite;
        }
        .error {
            color: #e74c3c;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        h1 {
            margin-bottom: 1.5rem;
            color: #d8b4fe;
        }
        .price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #7c3aed;
            margin: 1.5rem 0;
        }
        .btn {
            background: #7c3aed;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5b21b6;
            transform: translateY(-2px);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <?php if (isset($pagamento_riuscito) && $pagamento_riuscito): ?>
            <div class="success"><i class="fas fa-check-circle"></i></div>
            <h1>Pagamento completato!</h1>
            <div class="price">€<?= number_format($payment_data['prezzo'], 2, ',', '.') ?></div>
            <p>Nuovo saldo: €<?= number_format($nuovo_saldo, 2, ',', '.') ?></p>
            
            <div>
                <a href="<?= htmlspecialchars($payment_data['link_sito']) ?>" class="btn">
                    <i class="fas fa-check"></i> Torna al sito
                </a>
            </div>
            
            <script>
                setTimeout(function() {
                    window.location.href = "<?= htmlspecialchars($payment_data['link_sito']) ?>";
                }, 5000);
            </script>
            
        <?php else: ?>
            <div class="error"><i class="fas fa-times-circle"></i></div>
            <h1>Pagamento fallito</h1>
            <p><?= htmlspecialchars($errore_pagamento) ?></p>
            
            <div>
                <a href="login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Accedi di nuovo
                </a>
                <a href="<?= htmlspecialchars($payment_data['link_sito'] ?? 'javascript:history.back()') ?>" class="btn">
                    <i class="fas fa-arrow-left"></i> Torna indietro
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>