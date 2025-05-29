<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$_SESSION['ecommerce_user_id'] = $_SESSION['user_id'];
include('connessione.php'); // Questo file deve essere e_commerce/connessione.php

// Ottieni ID moto dall'URL
$moto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Recupera dati moto
$motoQuery = "SELECT m.*, cm.nome_categoria 
              FROM moto m 
              JOIN categoriemoto cm ON m.categoria_id = cm.id 
              WHERE m.id = $moto_id";
$motoResult = $conn_ecommerce->query($motoQuery);

if ($motoResult->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$moto = $motoResult->fetch_assoc();

// Recupera tutte le sedi
$sediQuery = "SELECT * FROM sedi";
$sediResult = $conn_ecommerce->query($sediQuery);

// Processa l'ordine
$ordine_riuscito = false; // Inizializza la variabile
$errore_ordine = "";  // Inizializza la variabile
$pagamento_in_corso = isset($_GET['pagamento']) && $_GET['pagamento'] == 'true';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conferma_ordine']) && !$pagamento_in_corso) {
    $sede_id = intval($_POST['sede_id']);
    $data_ritiro = $conn_ecommerce->real_escape_string($_POST['data_ritiro']);
    $user_id = $_SESSION['user_id'];

    // Inizia una transazione per garantire l'atomicità delle operazioni
    $conn_ecommerce->begin_transaction();

    try {
        // 1. Inserisci ordine
        $insertQuery = "INSERT INTO ordini (utente_id, moto_id, sede_id, data_prevista_ritiro) 
                      VALUES ($user_id, $moto_id, $sede_id, '$data_ritiro')";

        if (!$conn_ecommerce->query($insertQuery)) {
            throw new Exception("Errore durante l'inserimento dell'ordine: " . $conn_ecommerce->error);
        }

        // 2. Verifica che la quantità sia ancora disponibile
        $checkQuantityQuery = "SELECT quantita FROM moto WHERE id = $moto_id FOR UPDATE";
        $checkResult = $conn_ecommerce->query($checkQuantityQuery);
        $currentQuantity = $checkResult->fetch_assoc()['quantita'];

        if ($currentQuantity < 1) {
            throw new Exception("La moto non è più disponibile");
        }

        // Se tutto va bene, conferma la transazione
        $conn_ecommerce->commit();
        $ordine_riuscito = true;

    } catch (Exception $e) {
        // In caso di errore, annulla tutte le operazioni
        $conn_ecommerce->rollback();
        $errore_ordine = $e->getMessage();
    }
}

// Avvia il processo di pagamento
if ($ordine_riuscito && !$pagamento_in_corso) {
    $secret_key = 'CHIAVE_SEGRETA_CONDIVISA';  // Assicurati che sia la stessa in login_pagamento.php
    $hash = hash_hmac('sha256', $moto_id . $moto['prezzo'], $secret_key);
    $link_ritorno = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "?pagamento=true"; // Aggiungi il parametro pagamento
    header("Location: prog_info/pagamenti/login_pagamento.php?prezzo=" . urlencode($moto['prezzo']) . "&moto_id=" . urlencode($moto_id) . "&link_ritorno=" . urlencode($link_ritorno) . "&hash=" . urlencode($hash));
    exit();
}

// Gestione del messaggio di conferma pagamento
$pagamento_riuscito = isset($_SESSION['pagamento_riuscito']) ? $_SESSION['pagamento_riuscito'] : false;
unset($_SESSION['pagamento_riuscito']); // Consuma il messaggio

$pagamento_fallito = isset($_SESSION['pagamento_fallito']) ? $_SESSION['pagamento_fallito'] : false;
unset($_SESSION['pagamento_fallito']);

// Aggiorna la quantità dopo il pagamento (fuori dalla transazione dell'ordine)
if ($pagamento_riuscito) { 
    include('connessione.php'); // Includi nuovamente per sicurezza, ma dovrebbe essere già incluso
    if (isset($_SESSION['dati_pagamento']['moto_id'])) {
        $moto_id_to_update = $_SESSION['dati_pagamento']['moto_id'];
        $updateResult = $conn_ecommerce->query("UPDATE moto SET quantita = quantita - 1 WHERE id = $moto_id_to_update");
        if (!$updateResult) {
            error_log("Errore aggiornamento quantità: " . $conn_ecommerce->error);
            // Gestisci l'errore (es: messaggio all'utente, rollback transazione pagamento?)
        }
    } else {
        error_log("Errore: moto_id non trovato in sessione dopo il pagamento!");
        // Gestisci l'errore
    }
}

?>

 <!DOCTYPE html>
 <html lang="it">

 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modello']); ?> - Dettaglio</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Racing+Sans+One&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <style>
  :root {
   --primary: #e63946;
   --secondary: #1d3557;
   --accent: #457b9d;
   --light: #f1faee;
   --dark: #0d1b2a;
   --shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
   --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  }

  * {
   margin: 0;
   padding: 0;
   box-sizing: border-box;
  }

  body {
   font-family: 'Poppins', sans-serif;
   background-color: #f8f9fa;
   color: var(--dark);
   line-height: 1.6;
   overflow-x: hidden;
   display: flex;
   flex-direction: column;
   min-height: 100vh;
  }

  .main-content {
   flex: 1;
   display: flex;
   flex-direction: column;
  }

  .container {
   max-width: 1200px;
   width: 100%;
   margin: 20px auto;
   padding: 0 20px;
   flex: 1;
   display: flex;
   flex-direction: column;
  }

  h1 {
   font-family: 'Racing Sans One', cursive;
   font-size: 3.5rem;
   margin-bottom: 20px;
   text-transform: uppercase;
   letter-spacing: 2px;
  }

  .tagline {
   font-size: 1.2rem;
   margin-bottom: 30px;
   opacity: 0.9;
   animation: fadeIn 1.5s;
  }

  /* Navigation */
  .user-nav {
   display: none;
   position: absolute;
   top: 20px;
   right: 20px;
   display: flex;
   gap: 15px;
   z-index: 10;
  }

  .nav-btn {
   background: rgba(255, 255, 255, 0.2);
   color: white;
   padding: 10px 20px;
   border-radius: 30px;
   text-decoration: none;
   font-weight: 500;
   display: flex;
   align-items: center;
   gap: 8px;
   transition: var(--transition);
   backdrop-filter: blur(5px);
   border: 1px solid rgba(255, 255, 255, 0.1);
  }

  .nav-btn:hover {
   background: var(--primary);
   transform: translateY(-3px);
   box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }

  .nav-btn i {
   font-size: 16px;
  }

  /* Back Button */
  .back-btn {
   display: inline-flex;
   align-items: center;
   gap: 8px;
   background-color: var(--secondary);
   color: white;
   padding: 10px 20px;
   border-radius: 30px;
   text-decoration: none;
   margin-bottom: 30px;
   transition: var(--transition);
   align-self: flex-start;
  }

  .back-btn:hover {
   background-color: #14213d;
   transform: translateY(-3px);
   box-shadow: 0 5px 15px rgba(29, 53, 87, 0.3);
  }

  /* Moto Detail */
  .moto-detail-container {
   display: flex;
   justify-content: center;
   width: 100%;
   margin-bottom: 40px;
  }

  .moto-detail {
   display: flex;
   background-color: white;
   border-radius: 10px;
   overflow: hidden;
   box-shadow: var(--shadow);
   max-width: 1000px;
   width: 100%;
  }

  @media (max-width: 768px) {
   .moto-detail {
    flex-direction: column;
   }
  }

  .moto-image {
   flex: 1;
   padding: 40px;
   display: flex;
   align-items: center;
   justify-content: center;
   background-color: #f1f1f1;
   position: relative;
  }

  .moto-image img {
   max-width: 100%;
   max-height: 500px;
   object-fit: contain;
   transition: transform 0.5s ease;
  }

  .moto-info {
   flex: 1;
   padding: 40px;
  }

  .moto-info h1 {
   font-family: 'Racing Sans One', cursive;
   color: var(--secondary);
   margin-bottom: 15px;
   font-size: 2.2rem;
   text-transform: uppercase;
   letter-spacing: 1px;
  }

  .moto-category {
   display: inline-block;
   background-color: var(--accent);
   color: white;
   padding: 5px 15px;
   border-radius: 20px;
   font-size: 14px;
   margin-bottom: 20px;
  }

  .detail-row {
   display: flex;
   margin-bottom: 15px;
   padding-bottom: 15px;
   border-bottom: 1px solid #eee;
  }

  .detail-label {
   flex: 1;
   font-weight: 500;
   color: #7f8c8d;
  }

  .detail-value {
   flex: 2;
   font-weight: 500;
  }

  .price {
   font-size: 2rem;
   font-weight: 700;
   color: var(--primary);
   margin: 25px 0;
  }

  .moto-description {
   margin-top: 20px;
   line-height: 1.8;
   color: #555;
  }

  /* Order Form */
  .order-form-container {
   display: flex;
   justify-content: center;
   width: 100%;
   margin-bottom: 40px;
  }

  .order-form {
   background-color: white;
   padding: 30px;
   border-radius: 10px;
   box-shadow: var(--shadow);
   max-width: 1000px;
   width: 100%;
  }

  .order-form h2 {
   font-family: 'Racing Sans One', cursive;
   color: var(--secondary);
   margin-bottom: 20px;
   font-size: 1.8rem;
  }

  .form-group {
   margin-bottom: 20px;
  }

  .form-group label {
   display: block;
   margin-bottom: 8px;
   font-weight: 500;
   color: var(--secondary);
  }

  .form-group select,
  .form-group input {
   width: 100%;
   padding: 12px 15px;
   border: 1px solid #ddd;
   border-radius: 8px;
   font-family: 'Poppins', sans-serif;
   font-size: 16px;
   transition: var(--transition);
  }

  .form-group select:focus,
  .form-group input:focus {
   outline: none;
   border-color: var(--accent);
   box-shadow: 0 0 0 3px rgba(69, 123, 157, 0.2);
  }

  .btn {
   display: inline-flex;
   align-items: center;
   gap: 8px;
   padding: 12px 25px;
   background-color: var(--primary);
   color: white;
   border: none;
   border-radius: 30px;
   cursor: pointer;
   font-size: 16px;
   font-weight: 600;
   transition: var(--transition);
   text-decoration: none;
  }

  .btn:hover {
   background-color: #c1121f;
   transform: translateY(-3px);
   box-shadow: 0 5px 15px rgba(230, 57, 70, 0.3);
  }

  .btn i {
   font-size: 16px;
  }

  /* Payment Button */
  .payment-btn {
   display: inline-flex;
   align-items: center;
   gap: 8px;
   padding: 12px 25px;
   background-color: var(--primary);
   color: white;
   border: none;
   border-radius: 30px;
   cursor: pointer;
   font-size: 16px;
   font-weight: 600;
   transition: var(--transition);
   text-decoration: none;
  }

  .payment-btn:hover {
   background-color: #c1121f;
   transform: translateY(-3px);
   box-shadow: 0 5px 15px rgba(230, 57, 70, 0.3);
  }

  .payment-btn i {
   font-size: 16px;
  }

  /* Success/Error Messages */
  .success-message,
  .error-message {
   padding: 15px;
   margin-bottom: 20px;
   border-radius: 8px;
  }

  .success-message {
   background-color: #d4edda;
   color: #155724;
   border: 1px solid #c3e6cb;
  }

  .error-message {
   background-color: #f8d7da;
   color: #721c24;
   border: 1px solid #f5c6cb;
  }

  /* Footer */
  footer {
   background-color: var(--secondary);
   color: white;
   padding: 40px 0;
   margin-top: 60px;
  }

  .footer-container {
   max-width: 1200px;
   margin: 0 auto;
   padding: 0 20px;
   display: flex;
   justify-content: space-between;
   flex-wrap: wrap;
  }

  .footer-column {
   flex: 1;
   margin-bottom: 30px;
  }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="container">
            <a href="dashboard.php" class="back-btn animate__animated animate__fadeIn">
                <i class="fas fa-arrow-left"></i>
                <span>Torna alla Dashboard</span>
            </a>

            <?php if (isset($ordine_riuscito) && $ordine_riuscito) : ?>
                <div class="message-container">
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span>Ordine confermato con successo!</span>
                    </div>
                </div>
            <?php elseif (isset($errore_ordine)) : ?>
                <div class="message-container">
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($errore_ordine); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="moto-detail-container">
                <div class="moto-detail animate__animated animate__fadeIn">
                    <div class="moto-image">
                        <?php if (!empty($moto['immagine'])) : ?>
                            <img src="<?php echo htmlspecialchars($moto['immagine']); ?>" alt="<?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modello']); ?>">
                        <?php else : ?>
                            <i class="fas fa-motorcycle" style="font-size: 100px; color: #ccc;"></i>
                        <?php endif; ?>
                    </div>

                    <div class="moto-info">
                        <h1><?php echo htmlspecialchars($moto['marca']); ?> <?php echo htmlspecialchars($moto['modello']); ?></h1>
                        <span class="moto-category"><?php echo htmlspecialchars($moto['nome_categoria']); ?></span>

                        <div class="detail-row">
                            <div class="detail-label">Anno</div>
                            <div class="detail-value"><?php echo htmlspecialchars($moto['anno']); ?></div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">Cilindrata</div>
                            <div class="detail-value"><?php echo isset($moto['cilindrata']) ? htmlspecialchars($moto['cilindrata']) . ' cc' : 'N/A'; ?></div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">Disponibilità</div>
                            <div class="detail-value">
                                <?php if ($moto['quantita'] > 0) : ?>
                                    <span style="color: var(--primary);">Disponibile (<?php echo htmlspecialchars($moto['quantita']); ?>)</span>
                                <?php else : ?>
                                    <span style="color: #e74c3c;">Esaurito</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="price">
                            €<?php echo number_format($moto['prezzo'], 2, ',', '.'); ?>
                        </div>

                        <div class="moto-description">
                            <?php echo isset($moto['descrizione']) ? nl2br(htmlspecialchars($moto['descrizione'])) : 'Descrizione non disponibile'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($moto['quantita'] > 0) : ?>
                <div class="order-form-container">
                    <div class="order-form animate__animated animate__fadeIn">
                        <h2><i class="fas fa-truck"></i> Completa l'ordine</h2>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="sede_id"><i class="fas fa-store"></i> Sede di ritiro</label>
                                <select id="sede_id" name="sede_id" required>
                                    <option value="">Seleziona una sede</option>
                                    <?php
                                    $sediResult->data_seek(0);
                                    while ($sede = $sediResult->fetch_assoc()) : ?>
                                        <option value="<?php echo htmlspecialchars($sede['id']); ?>">
                                            <?php echo htmlspecialchars($sede['nome_sede']); ?> - <?php echo htmlspecialchars($sede['citta']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="data_ritiro"><i class="fas fa-calendar-alt"></i> Data di ritiro prevista</label>
                                <input type="date" id="data_ritiro" name="data_ritiro" required min="<?php echo date('Y-m-d', strtotime('+2 days')); ?>">
                            </div>

                            
                            <a href="../prog_info/pagamenti/login_pagamento.php?prezzo=<?= $moto['prezzo'] ?>&moto_id=<?= $moto['id'] ?>&link_ritorno=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>&hash=<?= hash_hmac('sha256', $moto['id'].$moto['prezzo'], 'CHIAVE_SEGRETA_CONDIVISA') ?>" 
                            class="payment-btn">
                                <i class="fas fa-university"></i> Paga con Banca Digitale
                            </a>

                            <button type="submit" name="conferma_ordine" class="btn">
                                <i class="fas fa-check"></i> Conferma Ordine
                            </button>
                        </form>
                    </div>
                </div>
            <?php else : ?>
                <div class="message-container">
                    <div class="error-message animate__animated animate__fadeIn">
                        <i class="fas fa-times-circle"></i>
                        <span>Prodotto attualmente non disponibile</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="animate__animated animate__fadeIn">
        <div class="footer-content">
            <div class="footer-column">
                <h3>MotoMarket</h3>
                <p>Il tuo negozio di fiducia per moto di qualità dal 2005. Offriamo le migliori marche a prezzi competitivi.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <div class="footer-column">
                <h3>Contatti</h3>
                <p><i class="fas fa-map-marker-alt"></i> Via delle Moto 123, 00100 Roma</p>
                <p><i class="fas fa-phone"></i> +39 06 1234567</p>
                <p><i class="fas fa-envelope"></i> info@motomarket.it</p>
            </div>

            <div class="footer-column">
                <h3>Orari</h3>
                <p>Lun-Ven: 9:00 - 19:00</p>
                <p>Sab: 10:00 - 18:00</p>
                <p>Dom: Chiuso</p>
            </div>
        </div>

        <div class="copyright">
            &copy; <?php echo date('Y'); ?> MotoMarket. Tutti i diritti riservati.
        </div>
    </footer>

    <script>
        // Imposta la data minima per il ritiro (oggi + 2 giorni)
        document.getElementById('data_ritiro').min = new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    </script>
</body>

</html>