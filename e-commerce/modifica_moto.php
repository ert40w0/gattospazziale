<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("connessione.php");

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";

// Gestione dell'aggiornamento del form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $marca = $conn_ecommerce->real_escape_string($_POST['marca']);
    $modello = $conn_ecommerce->real_escape_string($_POST['modello']);
    $anno = intval($_POST['anno']);
    $prezzo = floatval($_POST['prezzo']);
    $quantita = intval($_POST['quantita']);
    $categoria_id = intval($_POST['categoria_id']);
    $cilindrata = isset($_POST['cilindrata']) ? intval($_POST['cilindrata']) : NULL;
    $descrizione = isset($_POST['descrizione']) ? $conn_ecommerce->real_escape_string($_POST['descrizione']) : NULL;
    $immagine = isset($_POST['immagine']) ? $conn_ecommerce->real_escape_string($_POST['immagine']) : NULL;

    // Validazione URL immagine
    if (!empty($immagine) && !filter_var($immagine, FILTER_VALIDATE_URL)) {
        $error = "L'URL dell'immagine non è valido";
    } else {
        $sql = "UPDATE moto SET 
                marca='$marca', 
                modello='$modello', 
                anno=$anno, 
                prezzo=$prezzo, 
                quantita=$quantita, 
                categoria_id=$categoria_id, 
                cilindrata=" . ($cilindrata !== NULL ? $cilindrata : "NULL") . ", 
                descrizione=" . ($descrizione !== NULL ? "'$descrizione'" : "NULL") . ", 
                immagine=" . ($immagine !== NULL ? "'$immagine'" : "NULL") . " 
                WHERE id=$id";

        if ($conn_ecommerce->query($sql)) {
            $message = "Moto aggiornata con successo";
        } else {
            $error = "Errore nell'aggiornamento: " . $conn_ecommerce->error;
        }
    }
}

// Recupera i dati della moto da modificare
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM moto WHERE id=$id";
    $result = $conn_ecommerce->query($sql);

    if ($result->num_rows == 1) {
        $moto = $result->fetch_assoc();
    } else {
        $error = "Moto non trovata";
        header("Location: gestione_moto.php");
        exit();
    }
} else {
    $error = "ID moto non specificato";
    header("Location: gestione_moto.php");
    exit();
}

// Recupera le categorie per il dropdown
$sql_categorie = "SELECT id, nome_categoria FROM categoriemoto";
$result_categorie = $conn_ecommerce->query($sql_categorie);
$categorie = [];
if ($result_categorie->num_rows > 0) {
    while($row = $result_categorie->fetch_assoc()) {
        $categorie[] = $row;
    }
}

$conn_ecommerce->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Moto - Admin</title>
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

        .btn-secondary {
            background-color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
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

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--dark-color);
            font-weight: 500;
        }

        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus, 
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(74, 108, 247, 0.5);
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .form-check input {
            margin-right: 10px;
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

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .image-preview {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .image-preview img {
            max-width: 200px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }

        @media (max-width: 992px) {
            .admin-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .image-preview img {
                max-width: 100%;
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
                <a href="aggiungi_moto.php" class="menu-item">
                    <i class="fas fa-motorcycle"></i> Aggiungi Moto
                </a>
                <a href="gestione_moto.php" class="menu-item">
                    <i class="fas fa-list"></i> Gestisci Moto
                </a>
                <a href="gestione_ordini.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Ordini
                </a>
                <a href="?logout" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-edit"></i> Modifica Moto</h1>
                <div class="user-info">
                    <span>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong></span>
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
                    <h2>Modifica Dati Moto</h2>
                </div>

                <div class="card-body">
                    <form action="modifica_moto.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $moto['id']; ?>">

                        <div class="form-group">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($moto['marca']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="modello">Modello</label>
                            <input type="text" id="modello" name="modello" value="<?php echo htmlspecialchars($moto['modello']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="anno">Anno</label>
                            <input type="number" id="anno" name="anno" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($moto['anno']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="cilindrata">Cilindrata (cc)</label>
                            <input type="number" id="cilindrata" name="cilindrata" min="50" max="3000" value="<?php echo htmlspecialchars($moto['cilindrata'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="prezzo">Prezzo (€)</label>
                            <input type="number" id="prezzo" name="prezzo" step="0.01" min="0" value="<?php echo htmlspecialchars($moto['prezzo']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="quantita">Quantità disponibile</label>
                            <input type="number" id="quantita" name="quantita" min="0" value="<?php echo htmlspecialchars($moto['quantita']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="categoria_id">Categoria</label>
                            <select name="categoria_id" id="categoria_id" required>
                                <?php foreach ($categorie as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" <?php if($moto['categoria_id'] == $categoria['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($categoria['nome_categoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="immagine">URL Immagine</label>
                            <input type="url" id="immagine" name="immagine" value="<?php echo htmlspecialchars($moto['immagine'] ?? ''); ?>" placeholder="Inserisci l'URL completo dell'immagine">
                            <?php if (!empty($moto['immagine'])): ?>
                                <div class="image-preview">
                                    <small>Anteprima immagine corrente:</small>
                                    <img src="<?php echo htmlspecialchars($moto['immagine']); ?>" alt="Anteprima immagine" id="imagePreview" onerror="this.style.display='none'">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="descrizione">Descrizione</label>
                            <textarea id="descrizione" name="descrizione"><?php echo htmlspecialchars($moto['descrizione'] ?? ''); ?></textarea>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Salva Modifiche
                            </button>
                            <a href="gestione_moto.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Annulla
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostra l'anteprima dell'immagine quando si modifica l'URL
        document.getElementById('immagine').addEventListener('input', function() {
            const preview = document.getElementById('imagePreview');
            const url = this.value.trim();
            
            if (url) {
                if (!preview) {
                    const imagePreview = document.createElement('img');
                    imagePreview.id = 'imagePreview';
                    imagePreview.alt = "Anteprima immagine";
                    imagePreview.onerror = function() { this.style.display = 'none'; };
                    
                    const container = document.createElement('div');
                    container.className = 'image-preview';
                    
                    const label = document.createElement('small');
                    label.textContent = 'Anteprima immagine:';
                    
                    container.appendChild(label);
                    container.appendChild(imagePreview);
                    this.parentNode.appendChild(container);
                }
                
                document.getElementById('imagePreview').src = url;
                document.getElementById('imagePreview').style.display = 'block';
            } else if (preview) {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>