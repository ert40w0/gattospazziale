<?php
session_start();
include("connessione.php");

if (!isset($_SESSION['nome_utente'])) {
  header("Location: login.php");
  exit();
}

$nome_utente = $_SESSION['nome_utente'];

// Recupera dati utente e conto
$cliente_q = mysqli_query($conn, "SELECT * FROM clienti WHERE nome_utente = '$nome_utente'");
$cliente = mysqli_fetch_assoc($cliente_q);
$id_cliente = $cliente['id'];

$conto_q = mysqli_query($conn, "SELECT * FROM conti WHERE id_cliente = $id_cliente");
$conto = mysqli_fetch_assoc($conto_q);
$id_conto = $conto['id'];
$numero_conto = $conto['numero_conto'];
$saldo_attuale = floatval($conto['saldo']);
$saldo_iniziale = floatval($conto['saldo_iniziale']); // Aggiungi questa linea
// Recupera dati per il grafico

$stats_mensili = [];
$saldo_cumulativo = $saldo_iniziale; // Parti dal saldo iniziale

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $query = mysqli_query($conn, "
        SELECT 
            SUM(CASE WHEN (tipo_operazione = 'deposito' OR (tipo_operazione = 'bonifico' AND id_conto_destinatario = $id_conto)) THEN importo ELSE 0 END) as entrate,
            SUM(CASE WHEN (tipo_operazione = 'prelievo' OR (tipo_operazione = 'bonifico' AND id_conto_mittente = $id_conto)) THEN importo ELSE 0 END) as uscite
        FROM transazioni
        WHERE (id_conto_mittente = $id_conto OR id_conto_destinatario = $id_conto)
        AND DATE_FORMAT(data_transazione, '%Y-%m') = '$month'
    ");
    $stats = mysqli_fetch_assoc($query);
    
    // Calcola il saldo cumulativo
    $entrate_mese = floatval($stats['entrate'] ?? 0);
    $uscite_mese = floatval($stats['uscite'] ?? 0);
    $saldo_cumulativo += ($entrate_mese - $uscite_mese);
    
    $stats_mensili[] = [
        'month' => date('M', strtotime($month . '-01')),
        'entrate' => $entrate_mese,
        'uscite' => $uscite_mese,
        'saldo' => $saldo_cumulativo
    ];
}

// Gestione operazioni
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST['azione'], $_POST['importo'])) {
    $azione = $_POST['azione'];
    $importo = floatval($_POST['importo']);

    if ($importo > 0) {
      if ($azione === 'ricarica') {
        mysqli_query($conn, "UPDATE conti SET saldo = saldo + $importo WHERE id = $id_conto");
        mysqli_query($conn, "INSERT INTO transazioni (id_conto_mittente, id_conto_destinatario, importo, tipo_operazione, descrizione)
                             VALUES ($id_conto, NULL, $importo, 'deposito', 'Ricarica conto')");
        $_SESSION['saldo_corrente'] = $saldo_attuale + $importo;
        $_SESSION['msg'] = "Conto ricaricato con €" . number_format($importo, 2, ',', '.');
        $_SESSION['msg_tipo'] = 'successo';
      } elseif ($azione === 'prelievo') {
        if ($saldo_attuale >= $importo) {
          mysqli_query($conn, "UPDATE conti SET saldo = saldo - $importo WHERE id = $id_conto");
          mysqli_query($conn, "INSERT INTO transazioni (id_conto_mittente, id_conto_destinatario, importo, tipo_operazione, descrizione)
                               VALUES ($id_conto, NULL, $importo, 'prelievo', 'Prelievo conto')");
          $_SESSION['saldo_corrente'] = $saldo_attuale - $importo;
          $_SESSION['msg'] = "Hai prelevato €" . number_format($importo, 2, ',', '.');
          $_SESSION['msg_tipo'] = 'successo';
        } else {
          $_SESSION['msg'] = "Saldo insufficiente per il prelievo.";
          $_SESSION['msg_tipo'] = 'errore';
        }
      }
    } else {
      $_SESSION['msg'] = "Inserisci un importo valido.";
      $_SESSION['msg_tipo'] = 'errore';
    }
  } elseif (isset($_POST['bonifico'], $_POST['importo_bonifico'], $_POST['conto_destinatario'])) {
    // Gestione bonifico
    $importo = floatval($_POST['importo_bonifico']);
    $conto_destinatario = mysqli_real_escape_string($conn, $_POST['conto_destinatario']);
    $descrizione = isset($_POST['descrizione_bonifico']) ? mysqli_real_escape_string($conn, $_POST['descrizione_bonifico']) : 'Bonifico';
    
    // Verifica se il conto destinatario esiste ed è diverso dal mittente
    $destinatario_q = mysqli_query($conn, "SELECT id, saldo FROM conti WHERE numero_conto = '$conto_destinatario' AND id != $id_conto");
    
    if (mysqli_num_rows($destinatario_q) > 0) {
      $destinatario = mysqli_fetch_assoc($destinatario_q);
      $id_destinatario = $destinatario['id'];
      
      if ($saldo_attuale >= $importo && $importo > 0) {
        // Inizia transazione
        mysqli_begin_transaction($conn);
        
        try {
          // Aggiorna saldo mittente
          mysqli_query($conn, "UPDATE conti SET saldo = saldo - $importo WHERE id = $id_conto");
          
          // Aggiorna saldo destinatario
          mysqli_query($conn, "UPDATE conti SET saldo = saldo + $importo WHERE id = $id_destinatario");
          
          // Registra transazione
          mysqli_query($conn, "INSERT INTO transazioni (id_conto_mittente, id_conto_destinatario, importo, tipo_operazione, descrizione)
                               VALUES ($id_conto, $id_destinatario, $importo, 'bonifico', '$descrizione')");
          
          // Commit transazione
          mysqli_commit($conn);
          
          $_SESSION['saldo_corrente'] = $saldo_attuale - $importo;
          $_SESSION['msg'] = "Bonifico di €" . number_format($importo, 2, ',', '.') . " effettuato con successo";
          $_SESSION['msg_tipo'] = 'successo';
        } catch (Exception $e) {
          // Rollback in caso di errore
          mysqli_rollback($conn);
          $_SESSION['msg'] = "Errore durante il bonifico: " . $e->getMessage();
          $_SESSION['msg_tipo'] = 'errore';
        }
      } else {
        $_SESSION['msg'] = $importo <= 0 ? "Inserisci un importo valido." : "Saldo insufficiente per il bonifico.";
        $_SESSION['msg_tipo'] = 'errore';
      }
    } else {
      $_SESSION['msg'] = "Conto destinatario non valido o non trovato.";
      $_SESSION['msg_tipo'] = 'errore';
    }
  }

  header("Location: conto.php");
  exit();
}

// Recupera messaggio
$msg = $_SESSION['msg'] ?? null;
$msg_tipo = $_SESSION['msg_tipo'] ?? 'successo';
unset($_SESSION['msg'], $_SESSION['msg_tipo']);

// Recupera storico transazioni
$transazioni_q = mysqli_query($conn, "
  SELECT t.*, 
         c1.numero_conto as mittente, 
         c2.numero_conto as destinatario
  FROM transazioni t
  LEFT JOIN conti c1 ON t.id_conto_mittente = c1.id
  LEFT JOIN conti c2 ON t.id_conto_destinatario = c2.id
  WHERE t.id_conto_mittente = $id_conto OR t.id_conto_destinatario = $id_conto
  ORDER BY t.data_transazione DESC LIMIT 5
");

// Calcola totali per il riepilogo
$totali_q = mysqli_query($conn, "
  SELECT 
    SUM(CASE WHEN (tipo_operazione = 'deposito' OR (tipo_operazione = 'bonifico' AND id_conto_destinatario = $id_conto)) THEN importo ELSE 0 END) as entrate,
    SUM(CASE WHEN (tipo_operazione = 'prelievo' OR (tipo_operazione = 'bonifico' AND id_conto_mittente = $id_conto)) THEN importo ELSE 0 END) as uscite
  FROM transazioni
  WHERE (id_conto_mittente = $id_conto OR id_conto_destinatario = $id_conto)
  AND data_transazione >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
");
$totali = mysqli_fetch_assoc($totali_q);
$totale_entrate = $totali['entrate'] ?? 0;
$totale_uscite = $totali['uscite'] ?? 0;
$saldo_periodo = $saldo_iniziale + $totale_entrate - $totale_uscite; // Include il saldo iniziale
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Saldo Conto - VecchiniMoneys</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #7c3aed;
      --primary-light: #8b5cf6;
      --primary-dark: #5b21b6;
      --secondary: #10b981;
      --dark: #0f172a;
      --darker: #020617;
      --light: #f8fafc;
      --gray: #94a3b8;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }
    
    body {
      background-color: var(--darker);
      color: var(--light);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      line-height: 1.6;
    }

    /* Header Styles */
    .header {
      background: linear-gradient(135deg, var(--dark), var(--darker));
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
      position: relative;
      z-index: 100;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .bank-logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
    }

    .bank-icon {
      font-size: 1.75rem;
      color: var(--primary);
    }

    .bank-name {
      font-size: 1.5rem;
      font-weight: 600;
      background: linear-gradient(to right, var(--primary), var(--primary-light));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .user-nav {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .account-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: rgba(255, 255, 255, 0.05);
      padding: 0.5rem 1rem;
      border-radius: 50px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .account-number {
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--gray);
    }

    .account-number span {
      color: var(--light);
      font-weight: 600;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      color: white;
      font-size: 1.1rem;
    }

    .user-avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 0 0 3px rgba(123, 58, 237, 0.3);
    }

    .dropdown-menu {
      position: absolute;
      top: 70px;
      right: 30px;
      background: rgba(15, 23, 42, 0.95);
      border-radius: 12px;
      padding: 0.5rem 0;
      min-width: 200px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(15px);
      display: none;
      z-index: 1000;
      overflow: hidden;
    }

    .dropdown-menu.show {
      display: block;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-menu a {
      color: var(--light);
      padding: 0.75rem 1.5rem;
      text-decoration: none;
      display: block;
      transition: all 0.3s ease;
      font-size: 0.95rem;
    }

    .dropdown-menu a:hover {
      background: rgba(123, 58, 237, 0.2);
      padding-left: 1.75rem;
    }

    .dropdown-menu a i {
      margin-right: 0.75rem;
      width: 20px;
      text-align: center;
    }

    .dropdown-menu .logout {
      color: var(--danger);
      border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .dropdown-menu .logout:hover {
      background: rgba(239, 68, 68, 0.1);
    }

    /* Main Content */
    .main {
      flex: 1;
      padding: 2rem;
      position: relative;
    }

    /* Back Button */
.back-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.05);
  color: var(--light);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  text-decoration: none;
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
  position: absolute;
  top: 20px;
  left: 20px;
  z-index: 20;
}

.back-btn:hover {
  background: rgba(123, 58, 237, 0.2);
  border-color: rgba(123, 58, 237, 0.3);
  transform: translateY(-2px);
}

.back-btn i {
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .back-btn {
    top: 15px;
    left: 15px;
  }
}

    /* Card Grid */
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 2rem;
      width: 100%;
      max-width: 1200px;
      margin: 3rem auto 2rem;
    }

    /* Card Styles */
    .card {
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(2, 6, 23, 0.9));
      border-radius: 16px;
      padding: 1.75rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(123, 58, 237, 0.1) 0%, transparent 70%);
      transform: rotate(30deg);
      z-index: -1;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 40px rgba(123, 58, 237, 0.2);
      border-color: rgba(123, 58, 237, 0.3);
    }

    .card-header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .card-header h2 {
      color: #d8b4fe;
      margin-bottom: 0.5rem;
      font-size: 1.5rem;
    }

    .card-header p {
      color: var(--gray);
    }

    .saldo {
      font-size: 2.5rem;
      font-weight: bold;
      margin: 1rem 0;
      text-align: center;
      background: linear-gradient(to right, var(--light), var(--gray));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    /* Buttons */
    .btn-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      padding: 0.75rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s ease;
      text-decoration: none;
      width: 100%;
      font-size: 0.95rem;
    }

    .btn:hover {
      background: linear-gradient(135deg, var(--primary-light), var(--primary));
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(123, 58, 237, 0.3);
    }

    .btn-outline {
      background: transparent;
      border: 1px solid var(--primary);
      color: #d8b4fe;
    }

    .btn-outline:hover {
      background: rgba(123, 58, 237, 0.2);
    }

    /* Forms */
    .form-container {
      margin-top: 1.5rem;
      display: none;
    }

    .form-container.show {
      display: block;
      animation: fadeIn 0.3s;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #d8b4fe;
      font-size: 0.9rem;
    }

    input[type="number"],
    input[type="text"],
    textarea,
    select {
      width: 100%;
      padding: 0.75rem;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(15, 23, 42, 0.8);
      color: white;
      font-size: 1rem;
    }

    input[type="submit"] {
      width: 100%;
      padding: 0.75rem;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      margin-top: 1rem;
      transition: all 0.3s;
    }

    input[type="submit"]:hover {
      background: var(--primary-dark);
    }

    /* Messages */
    .msg {
      padding: 1rem;
      border-radius: 8px;
      margin: 0 auto 2rem;
      font-weight: 500;
      text-align: center;
      animation: fadeIn 0.3s;
      width: 100%;
      max-width: 1200px;
    }

    .msg-success {
      background: rgba(74, 222, 128, 0.2);
      color: #4ade80;
      border: 1px solid #4ade80;
    }

    .msg-error {
      background: rgba(248, 113, 113, 0.2);
      color: #f87171;
      border: 1px solid #f87171;
    }

    /* Transactions */
    .transactions-container {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
    }

    .transactions-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .transactions-header h3 {
      color: #d8b4fe;
      font-size: 1.25rem;
    }

    .transactions-list {
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(2, 6, 23, 0.9));
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .transaction-item {
      display: flex;
      justify-content: space-between;
      padding: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      transition: background 0.3s;
    }

    .transaction-item:last-child {
      border-bottom: none;
    }

    .transaction-item:hover {
      background: rgba(123, 58, 237, 0.1);
    }

    .transaction-amount {
      font-weight: bold;
      min-width: 100px;
      text-align: right;
      font-size: 1.1rem;
    }

    .transaction-in {
      color: var(--success);
    }

    .transaction-out {
      color: var(--danger);
    }

    .transaction-details {
      flex: 1;
      margin-left: 1.5rem;
    }

    .transaction-date {
      color: var(--gray);
      font-size: 0.85rem;
    }

    .transaction-description {
      margin-top: 0.25rem;
    }

    .transaction-account {
      color: #93c5fd;
      font-size: 0.85rem;
      margin-top: 0.25rem;
    }

    /* Chart */
    .chart-container {
      position: relative;
      height: 250px;
      width: 100%;
      margin: 1rem 0;
    }

    /* Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .stat-item {
      text-align: center;
      padding: 0.75rem;
      background: rgba(15, 23, 42, 0.5);
      border-radius: 8px;
    }

    .stat-value {
      font-size: 1.25rem;
      font-weight: bold;
      margin-bottom: 0.25rem;
    }

    .stat-label {
      color: var(--gray);
      font-size: 0.85rem;
    }

    /* Quick Actions */
    .quick-actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 1rem;
      justify-content: center;
    }

    .quick-action-btn {
      background: rgba(123, 58, 237, 0.2);
      color: #d8b4fe;
      border: none;
      border-radius: 5px;
      padding: 0.5rem;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 0.85rem;
    }

    .quick-action-btn:hover {
      background: rgba(123, 58, 237, 0.4);
    }

    /* Footer */
    .card-footer {
      margin-top: 1.5rem;
      text-align: center;
    }

    .card-footer a {
      color: #93c5fd;
      text-decoration: none;
      transition: color 0.3s;
      font-size: 0.9rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .card-footer a:hover {
      color: var(--primary-light);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .header {
        padding: 1rem;
      }
      
      .bank-name {
        font-size: 1.25rem;
      }
      
      .account-number {
        display: none;
      }
      
      .main {
        padding: 1.5rem;
      }
      
      .card-grid {
        grid-template-columns: 1fr;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .btn-container {
        grid-template-columns: 1fr;
      }

      .back-button {
        top: 15px;
        left: 15px;
      }
/* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .back-btn:hover {
            background: rgba(123, 58, 237, 0.2);
            border-color: rgba(123, 58, 237, 0.3);
            transform: translateY(-2px);
        }

        .back-btn i {
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            
            .bank-name {
                font-size: 1.25rem;
            }
            
            .account-number {
                display: none;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            .transaction-card {
                padding: 1.5rem;
            }
          }

    }
  </style>
</head>
<body>


<header class="header">
  <a href="dashboard.php" class="bank-logo">
    <div class="bank-icon">
      <i class="fas fa-university"></i>
    </div>
    <div class="bank-name">VecchiniMoneys</div>
  </a>

  <div class="user-nav">
    <div class="account-info">
      <div class="account-number">Conto: <span><?= htmlspecialchars($numero_conto) ?></span></div>
    </div>
    <div class="user-avatar" id="userAvatar">
      <i class="fas fa-user"></i>
    </div>
  </div>

  <div class="dropdown-menu" id="dropdownMenu">
    <a href="profilo.php"><i class="fas fa-user-cog"></i> Profilo</a>
    <a href="impostazioni.php"><i class="fas fa-cog"></i> Impostazioni</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</header>

<div class="main">
  <a href="dashboard.php" class="back-btn">
    <i class="fas fa-arrow-left"></i> Torna alla Dashboard
  </a>
  <?php if (!empty($msg)): ?>
    <div class="msg <?= $msg_tipo === 'errore' ? 'msg-error' : 'msg-success' ?>">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <div class="card-grid">
    <!-- Card Saldo -->
    <div class="card">
      <div class="card-header">
        <h2>Saldo disponibile</h2>
        <p><i class="fas fa-id-card"></i> Conto N° <?= htmlspecialchars($numero_conto) ?></p>
      </div>

      <div class="saldo">&euro; <?= number_format($_SESSION['saldo_corrente'] ?? $saldo_attuale, 2, ',', '.') ?></div>
      
      <div class="btn-container">
        <button class="btn" onclick="toggleForm('ricarica')">
          <i class="fas fa-plus-circle"></i> Ricarica
        </button>
        <button class="btn" onclick="toggleForm('prelievo')">
          <i class="fas fa-minus-circle"></i> Preleva
        </button>
        <button class="btn btn-outline" onclick="toggleForm('bonifico')">
          <i class="fas fa-exchange-alt"></i> Bonifico
        </button>
      </div>

      <!-- Form Ricarica/Prelievo -->
      <div id="form-operazione" class="form-container">
        <form method="POST">
          <input type="hidden" name="azione" id="tipo-operazione" value="">
          <div class="form-group">
            <label for="importo">Importo (€)</label>
            <input type="number" name="importo" id="importo" step="0.01" min="0.01" placeholder="0,00" required>
          </div>
          <input type="submit" value="Conferma">
        </form>
      </div>

      <!-- Form Bonifico -->
      <div id="form-bonifico" class="form-container">
        <form method="POST">
          <input type="hidden" name="bonifico" value="1">
          <div class="form-group">
            <label for="conto_destinatario">Conto Destinatario</label>
            <input type="text" name="conto_destinatario" id="conto_destinatario" placeholder="Inserisci numero conto" required>
          </div>
          <div class="form-group">
            <label for="importo_bonifico">Importo (€)</label>
            <input type="number" name="importo_bonifico" id="importo_bonifico" step="0.01" min="0.01" placeholder="0,00" required>
          </div>
          <div class="form-group">
            <label for="descrizione_bonifico">Descrizione (opzionale)</label>
            <input type="text" name="descrizione_bonifico" id="descrizione_bonifico" placeholder="Causale del bonifico">
          </div>
          <input type="submit" value="Effettua Bonifico">
        </form>
      </div>

      <div class="quick-actions">
        <button class="quick-action-btn" onclick="setQuickAmount(10)">€10</button>
        <button class="quick-action-btn" onclick="setQuickAmount(50)">€50</button>
        <button class="quick-action-btn" onclick="setQuickAmount(100)">€100</button>
        <button class="quick-action-btn" onclick="setQuickAmount(200)">€200</button>
      </div>

      <div class="card-footer">
        <a href="transazioni.php"><i class="fas fa-history"></i> Visualizza storico completo</a>
      </div>
    </div>

    <!-- Card Statistiche -->
    <div class="card">
      <div class="card-header">
        <h2>Statistiche</h2>
        <p><i class="fas fa-chart-line"></i> Riepilogo mensile</p>
      </div>
      
      <div class="chart-container">
        <canvas id="statsChart"></canvas>
      </div>
      
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-value" style="color: #4ade80;">+€<?= number_format($totale_entrate, 2, ',', '.') ?></div>
          <div class="stat-label">Entrate</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color: #f87171;">-€<?= number_format($totale_uscite, 2, ',', '.') ?></div>
          <div class="stat-label">Uscite</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color: #d8b4fe;">€<?= number_format($saldo_periodo, 2, ',', '.') ?></div>
          <div class="stat-label">Saldo</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Transazioni recenti -->
  <div class="transactions-container">
    <div class="transactions-header">
      <h3><i class="fas fa-clock"></i> Ultime transazioni</h3>
      <a href="transazioni.php" style="color: #93c5fd; text-decoration: none;">Vedi tutte</a>
    </div>

    <div class="transactions-list">
      <?php if (mysqli_num_rows($transazioni_q) > 0): ?>
        <?php while ($t = mysqli_fetch_assoc($transazioni_q)): 
          $tipo = $t['tipo_operazione'];
          $importo = number_format($t['importo'], 2, ',', '.');
          $entrata = $tipo === 'deposito' || ($tipo === 'bonifico' && $t['id_conto_destinatario'] == $id_conto);
          $colore = $entrata ? 'transaction-in' : 'transaction-out';
          $segno = $entrata ? '+' : '-';
          $descrizione = $t['descrizione'] ?? ucfirst($tipo);
          
          $altro_conto = $entrata ? $t['mittente'] : $t['destinatario'];
        ?>
          <div class="transaction-item">
            <div class="transaction-amount <?= $colore ?>"><?= $segno ?>€<?= $importo ?></div>
            <div class="transaction-details">
              <div class="transaction-description"><?= htmlspecialchars($descrizione) ?></div>
              <?php if ($altro_conto): ?>
                <div class="transaction-account">
                  <?= $entrata ? 'Da: ' : 'A: ' ?><?= htmlspecialchars($altro_conto) ?>
                </div>
              <?php endif; ?>
              <div class="transaction-date"><?= date("d/m/Y H:i", strtotime($t['data_transazione'])) ?></div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="text-align: center; padding: 2rem; color: #aaa;">
          <i class="fas fa-exchange-alt" style="font-size: 2rem; margin-bottom: 1rem;"></i>
          <p>Nessuna transazione recente</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div> <!-- Chiude il div.main -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Dropdown menu toggle
  const userAvatar = document.getElementById('userAvatar');
  const dropdownMenu = document.getElementById('dropdownMenu');

  userAvatar.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdownMenu.classList.toggle('show');
  });

  document.addEventListener('click', () => {
    dropdownMenu.classList.remove('show');
  });

  // Gestione form
  function toggleForm(azione) {
    // Nascondi tutti i form
    document.querySelectorAll('.form-container').forEach(form => {
      form.classList.remove('show');
    });
    
    // Mostra il form corretto
    if (azione === 'bonifico') {
      document.getElementById('form-bonifico').classList.add('show');
    } else {
      document.getElementById('tipo-operazione').value = azione;
      document.getElementById('form-operazione').classList.add('show');
    }
  }
  
  // Quick amount buttons
  function setQuickAmount(amount) {
    const activeForm = document.querySelector('.form-container.show');
    if (activeForm) {
      const input = activeForm.querySelector('input[type="number"]');
      if (input) {
        input.value = amount;
        input.focus();
      }
    }
  }

  // Inizializza il grafico
  function initChart() {
    const ctx = document.getElementById('statsChart').getContext('2d');
    
    const chartData = {
  labels: <?= json_encode(array_column($stats_mensili, 'month')) ?>,
  datasets: [
    {
      label: 'Entrate',
      data: <?= json_encode(array_column($stats_mensili, 'entrate')) ?>,
      borderColor: '#4ade80',
      backgroundColor: 'rgba(74, 222, 128, 0.1)',
      tension: 0.3,
      fill: true
    },
    {
      label: 'Uscite',
      data: <?= json_encode(array_column($stats_mensili, 'uscite')) ?>,
      borderColor: '#f87171',
      backgroundColor: 'rgba(248, 113, 113, 0.1)',
      tension: 0.3,
      fill: true
    },
    {
      label: 'Saldo',
      data: <?= json_encode(array_column($stats_mensili, 'saldo')) ?>,
      borderColor: '#d8b4fe',
      backgroundColor: 'rgba(216, 180, 254, 0.1)',
      tension: 0.3,
      fill: false,
      borderWidth: 2
    }
  ]
};

    const config = {
      type: 'line',
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        // Sostituisci la parte relativa al tooltip con questa configurazione
plugins: {
  legend: {
    position: 'top',
    labels: {
      color: '#d8b4fe',
      font: {
        size: 12
      }
    }
  },
  tooltip: {
    mode: 'nearest',
    intersect: false,
    backgroundColor: 'rgba(40, 20, 70, 0.9)',
    titleColor: '#d8b4fe',
    bodyColor: '#fff',
    borderColor: 'rgba(123, 58, 237, 0.5)',
    borderWidth: 1,
    padding: 12,
    callbacks: {
      label: function(context) {
        let label = context.dataset.label || '';
        if (label) {
          label += ': ';
        }
        if (context.parsed.y !== null) {
          label += '€' + context.parsed.y.toFixed(2);
        }
        return label;
      },
      // Aggiungi questo per mostrare il totale nel titolo
      afterTitle: function(context) {
        const month = context[0].label;
        const monthData = <?= json_encode($stats_mensili) ?>.find(m => m.month === month);
        if (monthData) {
          return [
            `Entrate: €${monthData.entrate.toFixed(2)}`,
            `Uscite: €${monthData.uscite.toFixed(2)}`,
            `Saldo: €${(monthData.entrate - monthData.uscite).toFixed(2)}`
          ];
        }
        return '';
      }
    }
  }
},
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(255,255,255,0.1)'
            },
            ticks: {
              color: '#aaa',
              callback: function(value) {
                return '€' + value;
              }
            }
          },
          x: {
            grid: {
              color: 'rgba(255,255,255,0.1)'
            },
            ticks: {
              color: '#aaa'
            }
          }
        },
        interaction: {
          mode: 'index',
          intersect: false
        },
        animation: {
          duration: 1000,
          easing: 'easeOutQuart'
        }
      }
    };

    new Chart(ctx, config);
  }

  // Nascondi messaggio dopo 5 secondi
  const msg = document.querySelector('.msg');
  if (msg) {
    setTimeout(() => {
      msg.style.opacity = '0';
      setTimeout(() => msg.remove(), 300);
    }, 5000);
  }

  // Inizializza il grafico al caricamento
  document.addEventListener('DOMContentLoaded', initChart);
</script>

</body>
</html>