<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connessione al database
include('connessione.php');

// Ottieni tutte le moto con quantità > 0 e informazioni sulla categoria
$motoQuery = "SELECT m.*, cm.nome_categoria 
              FROM moto m 
              JOIN categoriemoto cm ON m.categoria_id = cm.id 
              WHERE m.quantita > 0
              ORDER BY m.marca, m.modello";
$motoResult = $conn_ecommerce->query($motoQuery);

// Ottieni le categorie di moto
$categorieQuery = "SELECT * FROM categoriemoto ORDER BY nome_categoria";
$categorieResult = $conn_ecommerce->query($categorieQuery);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextGear - Il tuo negozio di moto online</title>
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
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1558981806-ec527fa84c39?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            position: relative;
            margin-bottom: 50px;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
        }

        header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 50px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="%23f8f9fa" opacity=".25"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" fill="%23f8f9fa" opacity=".5"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%23f8f9fa"/></svg>');
            background-size: cover;
            z-index: 1;
        }

        .header-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        h1 {
            font-family: 'Racing Sans One', cursive;
            font-size: 3.5rem;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            animation: fadeInDown 1s;
        }

        .tagline {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
            animation: fadeIn 1.5s;
        }

        /* Navigation */
        .user-nav {
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

        /* Filter Buttons */
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .filter-title {
            font-family: 'Racing Sans One', cursive;
            color: var(--secondary);
            margin-bottom: 15px;
            font-size: 1.5rem;
            text-align: center;
        }

        .filter-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-btn {
            background-color: var(--light);
            color: var(--dark);
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn i {
            font-size: 16px;
        }

        .filter-btn:hover {
            background-color: var(--accent);
            color: white;
            transform: translateY(-3px);
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: white;
        }

        /* Moto Grid */
        .moto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }

        @media (max-width: 768px) {
            .moto-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        .moto-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            transform: translateY(0);
        }

        .moto-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .moto-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, 0.7)));
            z-index: 1;
            opacity: 0;
            transition: var(--transition);
        }

        .moto-card:hover::before {
            opacity: 1;
        }

        .moto-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .moto-image {
            height: 220px;
            background-color: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .moto-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .moto-card:hover .moto-image img {
            transform: scale(1.05);
        }

        .moto-image .no-image {
            font-size: 50px;
            color: #ccc;
        }

        .moto-info {
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .moto-info h3 {
            font-family: 'Racing Sans One', cursive;
            color: var(--secondary);
            margin-bottom: 10px;
            font-size: 1.4rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .moto-specs {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: #555;
        }

        .spec-item i {
            color: var(--accent);
        }

        .moto-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0;
        }

        .moto-price small {
            font-size: 1rem;
            color: #777;
            font-weight: 400;
        }

        .moto-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 14px;
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

        .btn-outline {
            background-color: transparent;
            color: var(--secondary);
            border: 2px solid var(--secondary);
        }

        .btn-outline:hover {
            background-color: var(--secondary);
            color: white;
        }

        .moto-link {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
        }

        /* No Results */
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .no-results i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-results h3 {
            color: var(--secondary);
            margin-bottom: 10px;
        }

        /* Footer */
        footer {
            background-color: var(--secondary);
            color: white;
            padding: 50px 0 20px;
            margin-top: 80px;
            clip-path: polygon(0 20%, 100% 0, 100% 100%, 0 100%);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
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
        }

        /* Animations */
        @keyframes slideInFromLeft {
            from {
                transform: translateX(-50px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideInFromRight {
            from {
                transform: translateX(50px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }

            header {
                padding: 100px 0 60px;
            }

            .user-nav {
                position: static;
                justify-content: center;
                margin-bottom: 20px;
            }

            .filter-buttons {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="animate__animated animate__fadeIn">
            <div class="user-nav">
                <a href="profilo.php" class="nav-btn">
                    <i class="fas fa-user"></i>
                    <span>Profilo</span>
                </a>
                <a href="login.php" class="nav-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            
            <div class="header-content">
                <h1 class="animate__animated animate__fadeInDown">NextGear</h1>
                <p class="tagline animate__animated animate__fadeIn">Scopri la tua prossima avventura su due ruote</p>
            </div>
        </header>

        <!-- Filtri per categoria -->
        <div class="filter-section animate__animated animate__fadeIn">
            <h3 class="filter-title">Filtra per categoria</h3>
            <div class="filter-buttons">
                <button class="filter-btn active" data-category="all">
                    <i class="fas fa-bars"></i>
                    <span>Tutte le moto</span>
                </button>
                <?php 
                $categorieResult->data_seek(0);
                while ($categoria = $categorieResult->fetch_assoc()): ?>
                    <button class="filter-btn" data-category="<?php echo $categoria['id']; ?>">
                        <i class="fas fa-motorcycle"></i>
                        <span><?php echo htmlspecialchars($categoria['nome_categoria']); ?></span>
                    </button>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Griglia delle moto -->
        <div class="moto-grid" id="motoGrid">
            <?php 
            $motoResult->data_seek(0);
            while ($moto = $motoResult->fetch_assoc()): ?>
                <div class="moto-card animate__animated animate__fadeInUp" data-category="<?php echo $moto['categoria_id']; ?>">
                    <span class="moto-badge">Disponibili: <?php echo $moto['quantita']; ?></span>
                    
                    <div class="moto-image">
                        <?php if (!empty($moto['immagine'])): ?>
                            <img src="<?php echo htmlspecialchars($moto['immagine']); ?>" alt="<?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modello']); ?>">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="moto-info">
                        <h3><?php echo htmlspecialchars($moto['marca']); ?> <?php echo htmlspecialchars($moto['modello']); ?></h3>
                        
                        <div class="moto-specs">
                            <div class="spec-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo $moto['anno']; ?></span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span><?php echo isset($moto['cilindrata']) ? $moto['cilindrata'] . 'cc' : 'N/A'; ?></span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-tags"></i>
                                <span><?php echo htmlspecialchars($moto['nome_categoria']); ?></span>
                            </div>
                        </div>
                        
                        <div class="moto-price">
                            €<?php echo number_format($moto['prezzo'], 2, ',', '.'); ?> <small>IVA inclusa</small>
                        </div>
                    </div>
                    
                    <a href="dettaglio_moto.php?id=<?php echo $moto['id']; ?>" class="moto-link"></a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <footer class="animate__animated animate__fadeIn">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>NextGear</h3>
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
                    <p><i class="fas fa-envelope"></i> info@NextGear.it</p>
                </div>
                
                <div class="footer-column">
                    <h3>Orari</h3>
                    <p>Lun-Ven: 9:00 - 19:00</p>
                    <p>Sab: 10:00 - 18:00</p>
                    <p>Dom: Chiuso</p>
                </div>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> NextGear. Tutti i diritti riservati.
            </div>
        </div>
    </footer>

    <script>
        // Funzione per filtrare le moto per categoria
        function filterMoto(categoryId) {
            // Aggiorna lo stato attivo dei pulsanti
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.category === categoryId.toString()) {
                    btn.classList.add('active');
                }
            });

            // Mostra/nascondi le moto in base alla categoria
            const motoCards = document.querySelectorAll('.moto-card');
            let visibleCards = 0;
            
            motoCards.forEach(card => {
                if (categoryId === 'all' || card.dataset.category === categoryId.toString()) {
                    card.style.display = 'block';
                    card.classList.add('animate__fadeIn');
                    card.classList.remove('animate__fadeOut');
                    visibleCards++;
                } else {
                    card.classList.add('animate__fadeOut');
                    card.classList.remove('animate__fadeIn');
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });

            // Gestione messaggio "nessun risultato"
            const motoGrid = document.getElementById('motoGrid');
            let noResultsDiv = document.querySelector('.no-results');
            
            if (visibleCards === 0) {
                if (!noResultsDiv) {
                    noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-results animate__animated animate__fadeIn';
                    noResultsDiv.innerHTML = `
                        <i class="fas fa-motorcycle"></i>
                        <h3>Nessuna moto disponibile in questa categoria</h3>
                        <p>Spiacenti, al momento non abbiamo moto disponibili per questa categoria.</p>
                        <button class="btn btn-outline" onclick="resetFilter()">Mostra tutte le moto</button>
                    `;
                    motoGrid.appendChild(noResultsDiv);
                }
            } else if (noResultsDiv) {
                noResultsDiv.classList.add('animate__fadeOut');
                setTimeout(() => {
                    noResultsDiv.remove();
                }, 300);
            }
        }

        // Resetta il filtro
        function resetFilter() {
            filterMoto('all');
        }

        // Aggiungi event listener ai pulsanti di filtro
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                filterMoto(this.dataset.category);
            });
        });

        // Animazione al caricamento della pagina
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.moto-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>