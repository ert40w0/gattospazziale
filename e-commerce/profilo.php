<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connessione al database
include('connessione.php');

// Ottieni i dati dell'utente
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM utenti WHERE id = ?";
$userStmt = $conn_ecommerce->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

// Ottieni gli ordini dell'utente
$ordersQuery = "SELECT o.id, o.data_ordine, m.marca, m.modello, m.prezzo, 
                s.nome_sede, s.citta, o.data_prevista_ritiro
                FROM ordini o
                JOIN moto m ON o.moto_id = m.id
                JOIN sedi s ON o.sede_id = s.id
                WHERE o.utente_id = ?
                ORDER BY o.data_ordine DESC";
$ordersStmt = $conn_ecommerce->prepare($ordersQuery);
$ordersStmt->bind_param("i", $user_id);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente - E-commerce Moto</title>
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
            --success-color: #28a745;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
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

        h1, h2, h3 {
            font-family: 'Racing Sans One', cursive;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--secondary);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        /* Navigation */
        .user-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--secondary);
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .nav-btn {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .nav-btn:hover {
            background: #c1121f;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-btn i {
            font-size: 16px;
        }

        /* Profile Section */
        .profile-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-right: 20px;
            font-family: 'Racing Sans One', cursive;
        }

        .profile-info h2 {
            margin: 0 0 5px;
        }

        .profile-info p {
            margin: 0;
            color: #7f8c8d;
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-group {
            margin-bottom: 20px;
        }

        .detail-group label {
            display: block;
            font-weight: 500;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .detail-group .value {
            font-size: 16px;
            padding: 10px;
            background-color:rgb(205, 200, 200);
            border-radius: 5px;
            border-left: 4px solid var(--accent);
        }

        /* Orders Section */
        .orders-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            transition: var(--transition);
        }

        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-weight: 700;
            color: var(--secondary);
        }

        .order-date {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Stile migliorato per lo stato dell'ordine */
        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
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

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .order-products {
            margin-bottom: 15px;
        }

        .order-products p {
            margin-bottom: 5px;
        }

        .order-total {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
            text-align: right;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #c1121f;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(230, 57, 70, 0.3);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #14213d;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(29, 53, 87, 0.3);
        }

        /* Footer */
        footer {
            background-color: var(--secondary);
            color: white;
            padding: 50px 0 20px;
            margin-top: auto;
            clip-path: polygon(0 20%, 100% 0, 100% 100%, 0 100%);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-column h3 {
            font-family: 'Racing Sans One', cursive;
            margin-bottom: 20px;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary);
        }

        .footer-column p, .footer-column a {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
            display: block;
            transition: var(--transition);
        }

        .footer-column a:hover {
            color: white;
            transform: translateX(5px);
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .profile-details, .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <div class="user-nav animate__animated animate__fadeIn">
                <a href="dashboard.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Torna alla Dashboard</span>
                </a>
                <a href="?logout=1" class="nav-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>

            <div class="profile-section animate__animated animate__fadeIn">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-group">
                        <label>Username</label>
                        <div class="value"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>

                    <div class="detail-group">
                        <label>Email</label>
                        <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>

                    <div class="detail-group">
                        <label>Data di Nascita</label>
                        <div class="value"><?php echo date('d/m/Y', strtotime($user['data_nascita'])); ?></div>
                    </div>
                </div>
            </div>

            <div class="orders-section animate__animated animate__fadeIn">
                <h2><i class="fas fa-clipboard-list"></i> I tuoi ordini</h2>
                
                <?php if ($ordersResult->num_rows > 0): ?>
                    <div class="orders-list">
                        <?php while ($order = $ordersResult->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <span class="order-id">Ordine #<?php echo $order['id']; ?></span>
                                        <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['data_ordine'])); ?></span>
                                    </div>
                                    <?php 
                                        $today = new DateTime();
                                        $ritiro = new DateTime($order['data_prevista_ritiro']);
                                        
                                        if ($ritiro > $today) {
                                            echo '<span class="badge badge-info">In attesa</span>';
                                        } else {
                                            echo '<span class="badge badge-success">Completato</span>';
                                        }
                                    ?>
                                </div>
                                
                                <div class="order-details">
                                    <div>
                                        <p><strong>Moto:</strong> <?php echo htmlspecialchars($order['marca']) . ' ' . htmlspecialchars($order['modello']); ?></p>
                                        <p class="order-total">€<?php echo number_format($order['prezzo'], 2, ',', '.'); ?></p>
                                    </div>
                                    
                                    <div>
                                        <p><strong>Sede:</strong> <?php echo htmlspecialchars($order['nome_sede']) . ' - ' . htmlspecialchars($order['citta']); ?></p>
                                        <p><strong>Ritiro previsto:</strong> <?php echo date('d/m/Y', strtotime($order['data_prevista_ritiro'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <p>Non hai ancora effettuato ordini.</p>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-motorcycle"></i> Vai allo shop
                        </a>
                    </div>
                <?php endif; ?>
            </div>
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
</body>
</html>