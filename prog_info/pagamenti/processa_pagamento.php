<?php
session_start();
require_once __DIR__.'/../connessione.php';

// Configurazioni
$conto_sito_ecommerce = '5'; // ID del conto del sito

// Verifica accesso
if (!isset($_SESSION['pagamento_loggato']) || !isset($_SESSION['dati_pagamento'])) {
    die("Accesso non autorizzato");
}

// Recupera dati
$dati = $_SESSION['dati_pagamento'];
$username_banca = $_SESSION['username']; // Username del login bancario

// Verifica scadenza
if (time() > $dati['expires']) {
    session_destroy();
    die("Sessione di pagamento scaduta");
}

// Connessione al database e_commerce
$conn_ecommerce = new mysqli("localhost", "root", "", "e_commerce");
if ($conn_ecommerce->connect_error) {
    die("Connessione al database e_commerce fallita: " . $conn_ecommerce->connect_error);
}

// Elabora pagamento
$conn->begin_transaction();
$conn_ecommerce->begin_transaction();

try {
    // 1. Trova l'id del conto bancario
    $stmt = $conn->prepare("SELECT c.id, c.saldo
                           FROM clienti cl
                           JOIN conti c ON cl.id = c.id_cliente
                           WHERE cl.nome_utente = ?");
    $stmt->bind_param("s", $username_banca);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception("Conto bancario non trovato");
    }
    
    $row = $result->fetch_assoc();
    $conto_id_mittente = $row['id'];
    $saldo_mittente = $row['saldo'];
    $stmt->close();

    // 2. Verifica saldo
    if ($saldo_mittente < $dati['prezzo']) {
        throw new Exception("Saldo insufficiente. Disponibile: €" . number_format($saldo_mittente, 2, ',', '.'));
    }

    // 3. Operazioni bancarie
    $stmt = $conn->prepare("UPDATE conti SET saldo = saldo - ? WHERE id = ?");
    $stmt->bind_param("di", $dati['prezzo'], $conto_id_mittente);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE conti SET saldo = saldo + ? WHERE id = ?");
    $stmt->bind_param("di", $dati['prezzo'], $conto_sito_ecommerce);
    $stmt->execute();
    $stmt->close();

    // Registra transazioni
    $stmt = $conn->prepare("INSERT INTO transazioni 
                          (id_conto_mittente, id_conto_destinatario, importo, tipo_operazione, link_sito) 
                          VALUES (?, ?, ?, 'bonifico', ?)");
    $stmt->bind_param("iids", $conto_id_mittente, $conto_sito_ecommerce, $dati['prezzo'], $dati['link_ritorno']);
    $stmt->execute();
    $stmt->close();

    // 4. Operazioni e-commerce (usa l'ID dalla sessione e-commerce)
    if (!isset($_SESSION['ecommerce_user_id'])) {
        throw new Exception("ID utente e-commerce mancante");
    }

    $utente_id = $_SESSION['ecommerce_user_id'];
    $data_ritiro = date('Y-m-d', strtotime('+5 weekdays'));

    // Crea ordine
    $stmt = $conn_ecommerce->prepare("INSERT INTO ordini 
        (utente_id, moto_id, sede_id, data_ordine, data_prevista_ritiro, importo_pagato) 
        VALUES (?, ?, 1, NOW(), ?, ?)");
    $stmt->bind_param("iisd", $utente_id, $dati['moto_id'], $data_ritiro, $dati['prezzo']);
    $stmt->execute();
    $ordine_id = $stmt->insert_id;
    $stmt->close();

    // Riduci quantità moto
    $stmt = $conn_ecommerce->prepare("UPDATE moto SET quantita = quantita - 1 WHERE id = ?");
    $stmt->bind_param("i", $dati['moto_id']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $conn_ecommerce->commit();

    $pagamento_riuscito = true;
    $errore_pagamento = "";
    $nuovo_saldo = $saldo_mittente - $dati['prezzo'];

} catch (Exception $e) {
    $conn->rollback();
    $conn_ecommerce->rollback();
    $pagamento_riuscito = false;
    $errore_pagamento = $e->getMessage();
}
// Distruggi sessione della banca ma non quella dell'e-commerce
unset($_SESSION['pagamento_loggato']);
unset($_SESSION['dati_pagamento']);
unset($_SESSION['username']);


$conn->close();
$conn_ecommerce->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risultato Pagamento</title>
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
        .order-info {
            background: rgba(123, 58, 237, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
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
        <?php if ($pagamento_riuscito): ?>
            <div class="success"><i class="fas fa-check-circle"></i></div>
            <h1>Pagamento completato!</h1>
            
            <div class="order-info">
                <p><strong>Importo:</strong> €<?= number_format($dati['prezzo'], 2, ',', '.') ?></p>
                <p><strong>Nuovo saldo:</strong> €<?= number_format($nuovo_saldo, 2, ',', '.') ?></p>
                <p><strong>Numero ordine:</strong> #<?= $ordine_id ?></p>
                <p><strong>Ritiro previsto:</strong> <?= date('d/m/Y', strtotime($data_ritiro)) ?></p>
            </div>
            
            <div>
                <a href="<?= htmlspecialchars($dati['link_ritorno']) ?>" class="btn">
                    <i class="fas fa-check"></i> Torna al sito
                </a>
            </div>
            
            <script>
                setTimeout(function() {
                    window.location.href = "<?= htmlspecialchars($dati['link_ritorno']) ?>";
                }, 5000);
            </script>
            
        <?php else: ?>
            <div class="error"><i class="fas fa-times-circle"></i></div>
            <h1>Pagamento fallito</h1>
            <p><?= htmlspecialchars($errore_pagamento) ?></p>
            
            <div>
                <a href="login_pagamento.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Riprova
                </a>
                <a href="<?= htmlspecialchars($dati['link_ritorno'] ?? 'javascript:history.back()') ?>" class="btn">
                    <i class="fas fa-arrow-left"></i> Torna indietro
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>