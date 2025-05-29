<?php
session_start();
include("connessione.php");

if (!isset($_SESSION['nome_utente'])) {
    header("Location: login.php");
    exit();
}

$nome_utente = $_SESSION['nome_utente'];

// Recupera l'id conto dell'utente
$sql = "SELECT c.id AS conto_id, c.numero_conto 
        FROM conti c
        JOIN clienti cl ON cl.id = c.id_cliente
        WHERE cl.nome_utente = '$nome_utente'
        LIMIT 1";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$conto_id = $row['conto_id'];
$numero_conto = $row['numero_conto'];

// Ottiene le transazioni
$transazioni = mysqli_query($conn, "
  SELECT * FROM transazioni 
  WHERE id_conto_mittente = $conto_id OR id_conto_destinatario = $conto_id 
  ORDER BY data_transazione DESC
");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transazioni - VecchiniMoneys</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .main-content {
            flex: 1;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.03;
            z-index: -1;
            background-image: 
                radial-gradient(circle at 25% 25%, var(--primary) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, var(--secondary) 0%, transparent 50%);
            background-size: 50% 50%;
            background-repeat: no-repeat;
            animation: moveBackground 30s infinite alternate;
        }

        @keyframes moveBackground {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }

        .welcome-section {
            margin-bottom: 2.5rem;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--light), var(--gray));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .welcome-subtitle {
            color: var(--gray);
            font-size: 1rem;
            font-weight: 400;
        }

        /* Transaction Card */
        .transaction-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(2, 6, 23, 0.9));
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .transaction-card::before {
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

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .card-header h2 {
            font-size: 1.8rem;
            color: var(--light);
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .card-header p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        th {
            background-color: rgba(123, 58, 237, 0.1);
            color: var(--primary-light);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: rgba(123, 58, 237, 0.05);
        }

        .entrata {
            color: var(--success);
            font-weight: 600;
        }

        .uscita {
            color: var(--danger);
            font-weight: 600;
        }

        .link {
            color: #93c5fd;
            text-decoration: none;
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            transition: color 0.3s;
        }

        .link:hover {
            color: var(--primary-light);
        }

        .copy-btn {
            background: rgba(123, 58, 237, 0.2);
            color: var(--primary-light);
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 0.5rem;
            transition: all 0.3s;
            font-size: 0.8rem;
            border: 1px solid rgba(123, 58, 237, 0.3);
        }

        .copy-btn:hover {
            background: rgba(123, 58, 237, 0.4);
        }

        .no-transactions {
            text-align: center;
            padding: 3rem 0;
            color: var(--gray);
        }

        .no-transactions i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            opacity: 0.5;
        }

        .no-transactions p {
            font-size: 1.1rem;
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

    <main class="main-content">
        <div class="bg-pattern"></div>
        
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Torna alla Dashboard
        </a>

        <div class="welcome-section">
            <h1 class="welcome-title">Storico Transazioni</h1>
            <p class="welcome-subtitle">Tutte le operazioni del tuo conto</p>
        </div>

        <div class="transaction-card">
            <div class="card-header">
                <h2>Le tue transazioni</h2>
                <p><i class="fas fa-id-card"></i> Conto N° <?= htmlspecialchars($numero_conto) ?></p>
            </div>

            <?php if (mysqli_num_rows($transazioni) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Importo</th>
                            <th>Descrizione</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = mysqli_fetch_assoc($transazioni)):
                            $tipo = $t['tipo_operazione'];
                            $entrata = false;

                            if ($tipo === 'deposito') {
                                $entrata = true;
                            } elseif ($tipo === 'prelievo') {
                                $entrata = false;
                            } elseif ($tipo === 'bonifico') {
                                $entrata = ($t['id_conto_destinatario'] == $conto_id);
                            }

                            $segno = $entrata ? '+' : '-';
                            $classe = $entrata ? 'entrata' : 'uscita';
                            $descrizione = $t['descrizione'] ?? ucfirst($tipo);
                        ?>
                        <tr>
                            <td><?= date("d/m/Y H:i", strtotime($t['data_transazione'])) ?></td>
                            <td><?= ucfirst($tipo) ?></td>
                            <td class="<?= $classe ?>"><?= $segno ?>€<?= number_format($t['importo'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($descrizione) ?></td>
                            <td>
                                <?php if (!empty($t['link_sito'])): ?>
                                    <a class="link" href="<?= htmlspecialchars($t['link_sito']) ?>" target="_blank">
                                        <?= parse_url($t['link_sito'], PHP_URL_HOST) ?>
                                    </a>
                                    <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($t['link_sito']) ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-transactions">
                    <i class="fas fa-exchange-alt"></i>
                    <p>Nessuna transazione trovata</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

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

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert("Link copiato negli appunti!");
            });
        }
    </script>
</body>
</html>