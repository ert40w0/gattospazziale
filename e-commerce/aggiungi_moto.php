<?php
session_start();

// Verifica se l'admin è loggato
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include('connessione.php');

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Inizializza variabili e messaggi
$message = '';
$error = '';

// Recupera le categorie per il dropdown
$categorie_query = "SELECT id, nome_categoria FROM categoriemoto ORDER BY nome_categoria";
$categorie_result = $conn_ecommerce->query($categorie_query);

// Gestione del form di aggiunta moto
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $marca = trim($_POST['marca']);
    $modello = trim($_POST['modello']);
    $anno = trim($_POST['anno']);
    $prezzo = floatval(trim($_POST['prezzo']));
    $quantita = trim($_POST['quantita']);
    $categoria_id = trim($_POST['categoria_id']);
    $immagine = trim($_POST['immagine_url']);
    $descrizione = trim($_POST['descrizione']);
    $cilindrata = trim($_POST['cilindrata']);

    // Validazione dei dati
    if (empty($marca) || empty($modello) || empty($anno) || empty($prezzo) || empty($categoria_id) || empty($immagine) || empty($descrizione) || empty($cilindrata)) {
        $error = "Tutti i campi sono obbligatori!";
    } elseif (!is_numeric($anno) || $anno < 1900 || $anno > date('Y')) {
        $error = "Anno non valido!";
    } elseif (!is_numeric($prezzo) || $prezzo <= 0) {
        $error = "Prezzo non valido!";
    } elseif (!is_numeric($quantita) && !empty($quantita)) {
        $error = "Quantità non valida!";
    } elseif (!is_numeric($cilindrata) && !empty($cilindrata)) {
        $error = "Cilindrata non valida!";
    } elseif (!filter_var($immagine, FILTER_VALIDATE_URL)) {
        $error = "URL immagine non valido!";
    } else {
        // Se non ci sono errori, procedi con l'inserimento
        try {
            $query = "INSERT INTO moto (marca, modello, anno, prezzo, quantita, categoria_id, descrizione, cilindrata, immagine) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn_ecommerce->prepare($query);
            $stmt->bind_param("ssidiisis", $marca, $modello, $anno, $prezzo, $quantita, $categoria_id, $descrizione, $cilindrata, $immagine);

            if ($stmt->execute()) {
                $message = "Moto aggiunta con successo!";
                // Resetta i valori del form
                $marca = $modello = $anno = $prezzo = $quantita = $descrizione = $cilindrata = $immagine = '';
                $categoria_id = '';
            } else {
                $error = "Errore durante l'aggiunta della moto: " . $conn_ecommerce->error;
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error = "Errore database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Moto - Admin</title>
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

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #fff;
            box-shadow: var(--shadow);
            padding: 20px 0;
            position: fixed;
            height: 100%;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header h3 {
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin: 0;
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

        .menu-item:hover,
        .menu-item.active {
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
            margin-left: 250px; /* Larghezza sidebar */
            max-width: calc(100% - 250px);
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
            background-color: #fff;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            overflow: hidden; /* Previene sbordamenti */
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
        }

        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 5px;
        }

        .form-container {
            max-width: 100%;
            overflow-x: hidden;
        }

        .form-group {
            margin-bottom: 20px;
            max-width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box; /* Include padding nel width */
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
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

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--secondary-color);
            text-decoration: none;
        }

        .back-btn:hover {
            color: var(--dark-color);
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 992px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                max-width: 100%;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 15px;
            }
            
            .card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>E-commerce Moto</h3>
            </div>

            <div class="sidebar-menu">
                <a href="aggiungi_moto.php" class="menu-item active">
                    <i class="fas fa-motorcycle"></i> Aggiungi Moto
                </a>
                <a href="gestione_moto.php" class="menu-item">
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

        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-motorcycle"></i> Aggiungi Nuova Moto</h1>
                <div class="user-info">
                    <span>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                    <a href="?logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <div class="card">
                <?php if (!empty($message)) : ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php elseif (!empty($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" class="form-control" value="<?php echo isset($marca) ? htmlspecialchars($marca) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="modello">Modello</label>
                            <input type="text" id="modello" name="modello" class="form-control" value="<?php echo isset($modello) ? htmlspecialchars($modello) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="anno">Anno</label>
                            <input type="number" id="anno" name="anno" class="form-control" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo isset($anno) ? htmlspecialchars($anno) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="prezzo">Prezzo (€)</label>
                            <input type="number" id="prezzo" name="prezzo" class="form-control" min="0" step="0.01" value="<?php echo isset($prezzo) ? htmlspecialchars($prezzo) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="quantita">Quantità</label>
                            <input type="number" id="quantita" name="quantita" class="form-control" min="0" value="<?php echo isset($quantita) ? htmlspecialchars($quantita) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="categoria_id">Categoria</label>
                            <select id="categoria_id" name="categoria_id" class="form-control" required>
                                <option value="">Seleziona una categoria</option>
                                <?php while ($row = $categorie_result->fetch_assoc()) : ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($categoria_id) && $categoria_id == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['nome_categoria']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea id="descrizione" name="descrizione" class="form-control" required><?php echo isset($descrizione) ? htmlspecialchars($descrizione) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="cilindrata">Cilindrata</label>
                            <input type="number" id="cilindrata" name="cilindrata" class="form-control" min="0" required value="<?php echo isset($cilindrata) ? htmlspecialchars($cilindrata) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="immagine_url">URL Immagine</label>
                            <input type="url" id="immagine_url" name="immagine_url" class="form-control" required value="<?php echo isset($immagine_url) ? htmlspecialchars($immagine_url) : ''; ?>">
                        </div>

                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Salva Moto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>