<?php
session_start();
include("connessione.php");

if (!isset($_SESSION['nome_utente'])) {
    header("Location: login.php");
    exit();
}

$nome_utente = $_SESSION['nome_utente'];

// Recupera saldo e numero conto
$sql = "SELECT conti.saldo, conti.numero_conto
        FROM conti 
        JOIN clienti ON conti.id_cliente = clienti.id 
        WHERE clienti.nome_utente = '$nome_utente' 
        LIMIT 1";

$result = mysqli_query($conn, $sql);
$saldo = 0.00;
$numero_conto = '';

if ($row = mysqli_fetch_assoc($result)) {
    $saldo = floatval($row['saldo']);
    $numero_conto = $row['numero_conto'];
    $_SESSION['saldo_corrente'] = $saldo;
    $_SESSION['numero_conto'] = $numero_conto;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VecchiniMoneys</title>
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

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(2, 6, 23, 0.9));
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
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
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(123, 58, 237, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary);
            font-size: 1.25rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--light);
        }

        .card-content {
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .balance-amount {
            font-size: 2.25rem;
            font-weight: 700;
            margin: 1rem 0;
            background: linear-gradient(to right, var(--light), var(--gray));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .balance-label {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .card-footer {
            margin-top: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
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

        .btn i {
            font-size: 0.9rem;
        }

        /* Background Animation */
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
            
            .cards-grid {
                grid-template-columns: 1fr;
                max-width: 100%;
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
        
        <div class="welcome-section">
            <h1 class="welcome-title">Benvenuto, <?= htmlspecialchars($nome_utente) ?></h1>
            <p class="welcome-subtitle">Ecco il riepilogo del tuo conto</p>
        </div>

        <div class="cards-grid">
            <!-- Saldo Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="card-title">Saldo Disponibile</h3>
                </div>
                <div class="card-content">
                    <p class="balance-label">Saldo attuale</p>
                    <div class="balance-amount">€ <?= number_format($saldo, 2, ',', '.') ?></div>
                </div>
                <div class="card-footer">
                    <a href="conto.php" class="btn">
                        <i class="fas fa-info-circle"></i> Dettagli conto
                    </a>
                </div>
            </div>

            <!-- Transazioni Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3 class="card-title">Ultime Transazioni</h3>
                </div>
                <div class="card-content">
                    <p class="card-description">Visualizza lo storico completo delle tue operazioni bancarie e i movimenti recenti del tuo conto.</p>
                </div>
                <div class="card-footer">
                    <a href="transazioni.php" class="btn">
                        <i class="fas fa-list"></i> Visualizza transazioni
                    </a>
                </div>
            </div>
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

        // Simple animation for cards on load
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>