<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include('connessione.php');

if (isset($_GET['logout'])) {
    session_unset();    // Rimuove tutte le variabili di sessione
    session_destroy();  // Distrugge la sessione
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Gestione eliminazione moto
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $query = "SELECT immagine FROM moto WHERE id = ?";
    $stmt = $conn_ecommerce->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $moto = $result->fetch_assoc();
    
    if ($moto) {
        if (!empty($moto['immagine']) && file_exists($moto['immagine'])) {
            unlink($moto['immagine']);
        }
        
        $stmt = $conn_ecommerce->prepare("DELETE FROM moto WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Moto eliminata con successo!";
        } else {
            $error = "Errore durante l'eliminazione: " . $conn_ecommerce->error;
        }
    } else {
        $error = "Moto non trovata!";
    }
}

// Recupera tutte le moto con le relative categorie
$query = "SELECT m.*, cm.nome_categoria 
          FROM moto m 
          JOIN categoriemoto cm ON m.categoria_id = cm.id 
          ORDER BY m.marca, m.modello";
$moto_result = $conn_ecommerce->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestisci Moto - Admin</title>
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
                <a href="gestione_moto.php" class="menu-item active">
                    <i class="fas fa-list"></i> Gestisci Moto
                </a>
                <a href="gestisci_ordini.php" class="menu-item">
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
                <h1><i class="fas fa-list"></i> Gestisci Moto</h1>
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
                    <h2>Elenco Moto</h2>
                    <a href="aggiungi_moto.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Aggiungi Moto
                    </a>
                </div>
                
                <div class="card-body">
                    <?php if ($moto_result->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Immagine</th>
                                    <th>Marca</th>
                                    <th>Modello</th>
                                    <th>Anno</th>
                                    <th>Cilindrata</th>
                                    <th>Prezzo</th>
                                    <th>Quantità</th>
                                    <th>Categoria</th>
                                    <th>Descrizione</th>
                                    <th>Disponibilità</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($moto = $moto_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($moto['immagine'])): ?>
                                                <img src="<?php echo htmlspecialchars($moto['immagine']); ?>" alt="<?php echo htmlspecialchars($moto['marca'].' '.$moto['modello']); ?>" class="moto-img">
                                            <?php else: ?>
                                                <span>Nessuna immagine</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($moto['marca']); ?></td>
                                        <td><?php echo htmlspecialchars($moto['modello']); ?></td>
                                        <td><?php echo htmlspecialchars($moto['anno']); ?></td>
                                        <td><?php echo htmlspecialchars($moto['cilindrata'] ?? 'N/A'); ?> cc</td>
                                        <td><?php echo number_format($moto['prezzo'], 2, ',', '.'); ?> €</td>
                                        <td><?php echo htmlspecialchars($moto['quantita']); ?></td>
                                        <td><?php echo htmlspecialchars($moto['nome_categoria']); ?></td>
                                        <td><?php echo htmlspecialchars($moto['descrizione'] ?? 'Nessuna descrizione'); ?></td>
                                        <td>
                                            <?php if ($moto['quantita'] > 0): ?>
                                                <span class="badge badge-success">Disponibile</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Esaurito</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="modifica_moto.php?id=<?php echo $moto['id']; ?>" class="btn btn-sm">
                                                    <i class="fas fa-edit"></i> Modifica
                                                </a>
                                                <a href="gestione_moto.php?delete=<?php echo $moto['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questa moto?');">
                                                    <i class="fas fa-trash"></i> Elimina
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Nessuna moto presente nel database.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>