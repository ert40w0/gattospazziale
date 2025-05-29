<?php
session_start();

// Verifica autenticazione admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Connessione al database
$conn_ecommerce = new mysqli("localhost", "root", "", "e_commerce");
if ($conn_ecommerce->connect_error) {
    die("Connessione al database fallita: " . $conn_ecommerce->connect_error);
}

// Gestione logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Inizializza variabili messaggi
$message = '';
$error = '';

// Gestione messaggi dopo reindirizzamento
if (isset($_GET['success'])) {
    $message = "Ordine annullato con successo e quantità moto ripristinata!";
}
if (isset($_GET['error'])) {
    $error = isset($_GET['message']) ? urldecode($_GET['message']) : "Errore durante l'operazione";
}

// GESTIONE ANNULLAMENTO ORDINE SEMPLIFICATA (VERSIONE CORRETTA)
if (isset($_GET['annulla']) && is_numeric($_GET['annulla'])) {
    $id = intval($_GET['annulla']);
    
    // 1. Recupera ID moto dall'ordine
    $stmt = $conn_ecommerce->prepare("SELECT moto_id FROM ordini WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $ordine = $result->fetch_assoc();
        $moto_id = $ordine['moto_id'];
        
        // 2. Incrementa quantità moto
        $stmt = $conn_ecommerce->prepare("UPDATE moto SET quantita = quantita + 1 WHERE id = ?");
        $stmt->bind_param("i", $moto_id);
        $stmt->execute();
        
        // 3. Cancella ordine
        $stmt = $conn_ecommerce->prepare("DELETE FROM ordini WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Reindirizza senza parametri problematici
        header("Location: gestisci_ordini.php?success=1");
        exit();
    } else {
        header("Location: gestisci_ordini.php?error=1&message=Ordine non trovato");
        exit();
    }
}

// Recupera tutti gli ordini (query originale)
$query = "SELECT o.id, o.data_ordine, o.data_prevista_ritiro, 
                 u.username AS utente_username,
                 m.marca, m.modello, m.immagine, m.prezzo, m.id AS moto_id,
                 s.nome_sede, s.citta AS sede_citta
          FROM ordini o
          JOIN utenti u ON o.utente_id = u.id
          JOIN moto m ON o.moto_id = m.id
          JOIN sedi s ON o.sede_id = s.id
          ORDER BY o.data_ordine DESC";
$ordini_result = $conn_ecommerce->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Ordini - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* [TUTTI GLI STILI CSS RIMANGONO IDENTICI] */
        /* ... */
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>E-commerce Moto</h3>
            </div>
            
            <div class="sidebar-menu">
                <a href="aggiungi_moto.php" class="menu-item">
                    <i class="fas fa-motorcycle"></i> Aggiungi Moto
                </a>
                <a href="gestione_moto.php" class="menu-item">
                    <i class="fas fa-list"></i> Gestisci Moto
                </a>
                <a href="gestisci_ordini.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i> Ordini
                </a>
                <a href="?logout" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-shopping-cart"></i> Gestione Ordini</h1>
                <div class="user-info">
                    <span>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                    <a href="?logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            
            <div class="card">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="card-header">
                    <h2>Elenco Ordini</h2>
                </div>
                
                <div class="card-body">
                    <?php if ($ordini_result->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data Ordine</th>
                                    <th>Utente</th>
                                    <th>Moto</th>
                                    <th>Prezzo</th>
                                    <th>Consegna</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ordine = $ordini_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ordine['id']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ordine['data_ordine'])); ?></td>
                                        <td><?php echo htmlspecialchars($ordine['utente_username']); ?></td>
                                        <td>
                                            <div class="order-moto">
                                                <?php if (!empty($ordine['immagine'])): ?>
                                                    <img src="<?php echo htmlspecialchars($ordine['immagine']); ?>" alt="<?php echo htmlspecialchars($ordine['marca'].' '.$ordine['modello']); ?>" class="moto-img">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($ordine['marca']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($ordine['modello']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($ordine['prezzo'], 2, ',', '.'); ?> €</td>
                                        <td class="order-sede">
                                            <strong><?php echo htmlspecialchars($ordine['nome_sede']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($ordine['sede_citta']); ?></small><br>
                                            <small><?php echo date('d/m/Y', strtotime($ordine['data_prevista_ritiro'])); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                                $today = new DateTime();
                                                $ritiro = new DateTime($ordine['data_prevista_ritiro']);
                                                
                                                if ($ritiro > $today) {
                                                    echo '<span class="badge badge-info">In attesa</span>';
                                                } else {
                                                    echo '<span class="badge badge-success">Completato</span>';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="gestisci_ordini.php?annulla=<?php echo $ordine['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro di voler annullare questo ordine? La moto tornerà disponibile nel magazzino.');">
                                                    <i class="fas fa-times-circle"></i> Annulla
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Nessun ordine presente nel database.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn_ecommerce->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Ordini - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6cf7;
            --primary-hover: #3a5bd9;
            --secondary-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --border-color: #dee2e6;
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--dark-color);
            min-height: 100vh;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: white;
            box-shadow: var(--shadow);
            padding: 20px 0;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header h3 {
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 12px 20px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
        }

        .menu-item:hover, .menu-item.active {
            background-color: rgba(74, 108, 247, 0.1);
            color: var(--primary-color);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background-color: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .logout-btn {
            color: var(--danger-color);
            text-decoration: none;
            margin-left: 20px;
        }

        .logout-btn:hover {
            text-decoration: underline;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-success {
            background-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background-color: var(--light-color);
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .table tr:hover {
            background-color: rgba(74, 108, 247, 0.05);
        }

        .moto-img {
            width: 80px;
            height: auto;
            border-radius: 5px;
            object-fit: cover;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: #d4edda;
            color: var(--success-color);
        }

        .badge-danger {
            background-color: #f8d7da;
            color: var(--danger-color);
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: var(--danger-color);
            border: 1px solid #f5c6cb;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .order-moto {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-sede {
            line-height: 1.4;
        }

        @media (max-width: 992px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 576px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
            
            .order-moto {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>E-commerce Moto</h3>
            </div>
            
            <div class="sidebar-menu">
                <a href="aggiungi_moto.php" class="menu-item">
                    <i class="fas fa-motorcycle"></i> Aggiungi Moto
                </a>
                <a href="gestione_moto.php" class="menu-item">
                    <i class="fas fa-list"></i> Gestisci Moto
                </a>
                <a href="gestisci_ordini.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i> Ordini
                </a>
                <a href="?logout" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-shopping-cart"></i> Gestione Ordini</h1>
                <div class="user-info">
                    <span>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                    <a href="?logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            
            <div class="card">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="card-header">
                    <h2>Elenco Ordini</h2>
                </div>
                
                <div class="card-body">
                    <?php if ($ordini_result->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data Ordine</th>
                                    <th>Utente</th>
                                    <th>Moto</th>
                                    <th>Prezzo</th>
                                    <th>Consegna</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ordine = $ordini_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ordine['id']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ordine['data_ordine'])); ?></td>
                                        <td><?php echo htmlspecialchars($ordine['utente_username']); ?></td>
                                        <td>
                                            <div class="order-moto">
                                                <?php if (!empty($ordine['immagine'])): ?>
                                                    <img src="<?php echo htmlspecialchars($ordine['immagine']); ?>" alt="<?php echo htmlspecialchars($ordine['marca'].' '.$ordine['modello']); ?>" class="moto-img">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($ordine['marca']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($ordine['modello']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($ordine['prezzo'], 2, ',', '.'); ?> €</td>
                                        <td class="order-sede">
                                            <strong><?php echo htmlspecialchars($ordine['nome_sede']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($ordine['sede_citta']); ?></small><br>
                                            <small><?php echo date('d/m/Y', strtotime($ordine['data_prevista_ritiro'])); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                                $today = new DateTime();
                                                $ritiro = new DateTime($ordine['data_prevista_ritiro']);
                                                
                                                if ($ritiro > $today) {
                                                    echo '<span class="badge badge-info">In attesa</span>';
                                                } else {
                                                    echo '<span class="badge badge-success">Completato</span>';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="gestisci_ordini.php?annulla=<?php echo $ordine['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro di voler annullare questo ordine? La moto tornerà disponibile nel magazzino.');">
                                                    <i class="fas fa-times-circle"></i> Annulla
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Nessun ordine presente nel database.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn_ecommerce->close();
?>