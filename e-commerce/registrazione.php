<?php
include('connessione.php');

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $birthday = $_POST['birthday'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($birthday)) {
        $error = "Tutti i campi sono obbligatori!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido!";
    } elseif ($password !== $confirm_password) {
        $error = "Le password non coincidono!";
    } elseif (strlen($password) < 8) {
        $error = "La password deve essere di almeno 8 caratteri!";
    } else {
        $date_parts = explode('/', $birthday);
        if (count($date_parts) === 3) {
            $mysql_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        } else {
            $mysql_date = date('Y-m-d', strtotime($birthday));
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn_ecommerce->prepare("SELECT id FROM utenti WHERE username = ? OR email = ?");
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Email o username già registrato!";
            } else {
                $stmt = $conn_ecommerce->prepare("
                    INSERT INTO utenti (username, email, password, data_nascita)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param('ssss', $username, $email, $hashed_password, $mysql_date);

                if ($stmt->execute()) {
                    $success = "Registrazione completata con successo!";
                    $_POST = array();
                } else {
                    $error = "Errore durante la registrazione!";
                }
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Errore durante la registrazione: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - NextGear</title>
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
            --container-bg: rgba(255, 255, 255, 0.9);
            --text-color:rgb(0, 0, 0);
            --input-border: rgba(221, 221, 221, 0.3);
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
            background: linear-gradient(rgba(255, 255, 255, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://cdn.inmoto.it/images/2022/08/03/123839706-c4efec98-3c7f-40bf-a41b-3cf7fdf57b23.jpg') no-repeat center center fixed;
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
            border: 1px solid rgba(255, 255, 255, 0.1);
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

        .form-group input::placeholder {
            color: rgba(0, 0, 0, 0.5);
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
        

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
        }

        /* Stile per la data di nascita */
        .birthday-field {
            position: relative;
        }

        .birthday-field::after {
            content: "\f073";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 38px;
            color: rgba(255, 255, 255, 0.5);
            pointer-events: none;
        }
    </style>
</head>
<body class="animate__animated animate__fadeIn">

    <div class="container">
    

        <h1>Registrati su NextGear</h1>

        <?php if ($error): ?>
            <div class="message error animate__animated animate__fadeIn"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success animate__animated animate__fadeIn"><?php echo htmlspecialchars($success); ?></div>
            <script>
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            </script>
        <?php endif; ?>

        <form id="registerForm" method="post" action="registrazione.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Conferma Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="form-group birthday-field">
                <label for="birthday">Data di Nascita (GG/MM/AAAA)</label>
                <input type="text" id="birthday" name="birthday" placeholder="DD/MM/YYYY" required 
                       value="<?php echo isset($_POST['birthday']) ? htmlspecialchars($_POST['birthday']) : ''; ?>">
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-user-plus"></i> Registrati
            </button>
        </form>

        <div class="login-links">
            <a href="login.php">Hai già un account? Accedi</a>
        </div>
    </div>

    <script>
        // Animazione durante il submit
        const registerForm = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');

        registerForm.addEventListener('submit', function() {
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrazione in corso...';
        });

        // Toggle tema scuro/chiaro
        

        // Input mask per la data di nascita
        document.getElementById('birthday').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2 && value.length <= 4) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            } else if (value.length > 4) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4, 8);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>