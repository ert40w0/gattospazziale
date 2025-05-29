<?php
include('connessione.php');

$error = '';
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username_or_email) || empty($password)) {
        $error = "Tutti i campi sono obbligatori!";
    } else {
        try {
            // PRIMA verifica se è un admin (password in chiaro)
            $admin_stmt = $conn_ecommerce->prepare("SELECT id, username, password FROM admin WHERE username = ? OR email = ?");
            $admin_stmt->bind_param('ss', $username_or_email, $username_or_email);
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            
            if ($admin_result->num_rows == 1) {
                $admin = $admin_result->fetch_assoc();
                
                // Confronto diretto password in chiaro
                if ($password === $admin['password']) {
                    // Login admin riuscito
                    session_start();
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    header("Location: aggiungi_moto.php");
                    exit();
                }
            }
            
            // Se non è un admin, verifica utente normale (password hashata)
            $user_stmt = $conn_ecommerce->prepare("SELECT id, username, password FROM utenti WHERE username = ? OR email = ?");
            $user_stmt->bind_param('ss', $username_or_email, $username_or_email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows == 1) {
                $user = $user_result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Login utente riuscito
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Password non corretta!";
                }
            } else {
                $error = "Utente non trovato!";
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Errore durante il login: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NextGear</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Racing+Sans+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --container-bg:rgb(255, 255, 255);
            --text-color:rgb(0, 0, 0);
            --input-border: #ddd;
            --input-focus: #e63946;
            --btn-hover: #c1121f;
            --link-color: #e63946;
            --link-hover: #c1121f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(rgba(255, 255, 255, 0.7), rgba(0, 0, 0, 0.7)), url('https://cdn.inmoto.it/images/2022/08/03/123839706-c4efec98-3c7f-40bf-a41b-3cf7fdf57b23.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;    
            padding: 20px;
            color: var(--text-color);
            transition: var(--transition);
            position: relative;
            line-height: 1.6;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }

        .container {
            background: var(--container-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            transform: translateY(0);
            opacity: 1;
            transition: var(--transition);
            animation: fadeInUp 0.5s ease-out;
            position: relative;
            backdrop-filter: blur(5px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            font-family: 'Racing Sans One', cursive;
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 3px solid var(--input-border);
            border-radius: 30px;
            font-size: 16px;
            transition: var(--transition);
            background-color: rgba(90, 90, 90, 0.1);
            color: var(--text-color);
        }

        .form-group input:focus {
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.2);
            outline: none;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            background: var(--btn-hover);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(230, 57, 70, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--text-color);
            border: 1px solid var(--primary);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .error {
            background-color: rgba(230, 57, 70, 0.2);
            color: var(--text-color);
            border: 1px solid var(--primary);
        }

        .success {
            background-color: rgba(69, 123, 157, 0.2);
            color: var(--text-color);
            border: 1px solid var(--accent);
        }

        .login-links {
            text-align: center;
            margin-top: 25px;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .login-links a {
            color: var(--link-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .login-links a:hover {
            color: var(--link-hover);
            text-decoration: underline;
        }

        /* Animazione per il pulsante al submit */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .btn-loading {
            animation: pulse 1.5s infinite;
        }

        /* Pulsante tema */
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .theme-toggle:hover {
            background: var(--btn-hover);
            transform: scale(1.1);
        }

        .theme-toggle i {
            font-size: 18px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="animate__animated animate__fadeIn">

    <div class="container">

        <h1>Accedi a NextGear</h1>

        <?php if (!empty($error)): ?>
            <div class="message error animate__animated animate__fadeIn"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="loginForm" method="post" action="login.php">
            <div class="form-group">
                <label for="username">Username o Email</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($username); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-sign-in-alt"></i> Accedi
            </button>
        </form>
        
        <div class="login-links">
            <a href="registrazione.php">Non hai un account? Registrati</a>
        </div>
    </div>

    <script>
        // Animazione durante il submit
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');

        loginForm.addEventListener('submit', function() {
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accesso in corso...';
        });
    </script>
</body>
</html>
