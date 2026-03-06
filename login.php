<?php
session_start();

require 'config.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = $_POST["username"];
    $input_password = $_POST["password"];

    if (empty($input_username) || empty($input_password)) {
        $error_message = "Harap isi Username dan Password!";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($input_password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Username atau Password salah!";
            }
        } else {
            $error_message = "Username atau Password salah!";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Karya Ilmiah Remaja SMKN 1 Pacitan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @keyframes floatAnimation {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: floatAnimation 6s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(118, 75, 162, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            right: -150px;
            animation: floatAnimation 8s ease-in-out infinite reverse;
            pointer-events: none;
            z-index: 0;
        }
        
        .login-class {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 50px 40px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(31, 38, 135, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.4);
            width: 100%;
            max-width: 420px;
            animation: slideInUp 0.6s ease-out;
            position: relative;
            z-index: 1;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            color: rgba(0, 0, 0, 0.85);
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.8px;
            margin-bottom: 10px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-header h2 {
            color: rgba(0, 0, 0, 0.6);
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            display: block;
            color: rgba(0, 0, 0, 0.7);
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: rgba(0, 0, 0, 0.8);
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
        }

        .form-group input::placeholder {
            color: rgba(0, 0, 0, 0.35);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: rgba(102, 126, 234, 0.6);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 8px 24px rgba(102, 126, 234, 0.2);
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #c33;
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #c33;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1.5px solid rgba(220, 53, 69, 0.3);
            animation: slideInDown 0.4s ease-out;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            color: #2d5016;
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #28a745;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1.5px solid rgba(40, 167, 69, 0.3);
            animation: slideInDown 0.4s ease-out;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            color: white;
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            margin-top: 10px;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .login-button:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 1) 0%, rgba(118, 75, 162, 1) 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.35);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .login-button:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.25);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: rgba(0, 0, 0, 0.5);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .login-footer p {
            letter-spacing: 0.3px;
        }

        /* Responsive Design for Tablets (768px and below) */
        @media (max-width: 768px) {
            .login-class {
                width: 95%;
                max-width: 500px;
                padding: 40px 30px;
                margin: 20px;
            }

            .login-header h1 {
                font-size: 28px;
            }

            .login-header h2 {
                font-size: 15px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .form-group label {
                font-size: 12px;
            }

            .form-group input {
                padding: 12px 14px;
                font-size: 13px;
            }

            .login-button {
                padding: 12px;
                font-size: 14px;
            }

            .login-footer {
                margin-top: 25px;
                font-size: 11px;
            }
        }

        /* Responsive Design for Mobile Phones (480px and below) */
        @media (max-width: 480px) {
            .login-class {
                width: 95%;
                max-width: 100%;
                padding: 35px 20px;
                border-radius: 16px;
            }

            .login-header {
                margin-bottom: 30px;
            }

            .login-header h1 {
                font-size: 24px;
                margin-bottom: 8px;
            }

            .login-header h2 {
                font-size: 14px;
            }

            .form-group {
                margin-bottom: 16px;
            }

            .form-group label {
                font-size: 11px;
                margin-bottom: 6px;
            }

            .form-group input {
                padding: 11px 12px;
                font-size: 14px;
                border-radius: 10px;
            }

            .form-group input::placeholder {
                font-size: 12px;
            }

            .login-button {
                padding: 11px;
                font-size: 13px;
                margin-top: 8px;
            }

            .error-message,
            .success-message {
                padding: 12px 14px;
                font-size: 12px;
                margin-bottom: 18px;
            }

            .login-footer {
                margin-top: 20px;
                font-size: 10px;
            }

            body::before,
            body::after {
                width: 300px;
                height: 300px;
            }
        }

        /* Extra small devices (less than 380px) */
        @media (max-width: 380px) {
            .login-class {
                padding: 30px 15px;
            }

            .login-header h1 {
                font-size: 22px;
            }

            .login-header h2 {
                font-size: 13px;
            }

            .form-group input {
                padding: 10px 11px;
                font-size: 13px;
            }

            .login-button {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class = "login-class">
        <div class = "login-header">
            <h1>Karya Ilmiah Remaja</h1>
            <h2>SMKN 1 Pacitan</h2>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class = "error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class = "success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>    

        <form method = "POST" action = "">
            <div class = "form-group">
                <label for = "username">Username</label>
                <input type = "text" id = "username" name = "username" placeholder = "Masukkan Username Anda" required>
            </div>

            <div class = "form-group">
                <label for = "password">Password</label>
                <input type = "password" id = "password" name = "password" placeholder = "Masukkan Password Anda" required>
            </div>

            <button type = "submit" class = "login-button">Masuk</button>
        </form>

        <div class = "login-footer">
           <p>&copy; 2025 KIR SMKN 1 Pacitan. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
