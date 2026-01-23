<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "aplikasi_pengelolaan_ekstrakurikuler_kir_esensial";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi ke database gagal!: " . $conn->connect_error);
}

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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-class {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .login-header h2 {
            color: #333;
            font-size: 20px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }
        
        .success-message {
            background-color: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #3c3;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .login-button:hover {
            background-color: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .login-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(102, 126, 234, 0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #888;
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
