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

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_page = isset($_GET['page']) ? $_GET['page'] : 'anggota';

function getAnggota($conn) {
    $sql = "SELECT * FROM member";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProyek($conn) {
    $sql = "SELECT DISTINCT 
            p.project_id, 
            p.project_date, 
            p.project_name, 
            p.status, 
            m.member_id, 
            m.name 
            FROM project p 
            JOIN member m ON p.member_id = m.member_id 
            ORDER BY p.project_id, m.name";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUniqueProyek($conn) {
    $sql = "SELECT DISTINCT 
            p.project_id, 
            p.project_date, 
            p.project_name, 
            p.status
            FROM project p
            ORDER BY p.project_id";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getMembersByProject($conn, $project_id) {
    $sql = "SELECT 
            m.member_id, 
            m.name, 
            m.class, 
            m.enrollment_date
            FROM project p
            JOIN member m ON p.member_id = m.member_id
            WHERE p.project_id = ?
            ORDER BY m.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $members = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $members;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "add_member") {
    $name = $_POST["name"];
    $class = $_POST["class"];
    $enrollment_date = $_POST["enrollment_date"];
    
    $sql = "INSERT INTO member (name, class, enrollment_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $class, $enrollment_date);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Anggota berhasil ditambahkan"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menambah anggota"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "edit_member") {
    $member_id = $_POST["member_id"];
    $name = $_POST["name"];
    $class = $_POST["class"];
    $enrollment_date = $_POST["enrollment_date"];
    
    $sql = "UPDATE member SET name = ?, class = ?, enrollment_date = ? WHERE member_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $class, $enrollment_date, $member_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Anggota berhasil diperbarui"];
    } else {
        $response = ["status" => "error", "message" => "Gagal memperbarui anggota"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_member") {
    $member_id = $_POST["member_id"];
    
    $sql = "DELETE FROM member WHERE member_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $member_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Anggota berhasil dihapus"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menghapus anggota"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "add_project") {
    $project_date = $_POST["project_date"];
    $project_name = $_POST["project_name"];
    $status = $_POST["status"];
    $member_id = $_POST["member_id"];
    
    $sql = "INSERT INTO project (project_date, project_name, status, member_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $project_date, $project_name, $status, $member_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Proyek berhasil ditambahkan"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menambah proyek"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "edit_project") {
    $project_id = $_POST["project_id"];
    $project_date = $_POST["project_date"];
    $project_name = $_POST["project_name"];
    $status = $_POST["status"];
    $member_id = $_POST["member_id"];
    
    $sql = "UPDATE project SET project_date = ?, project_name = ?, status = ?, member_id = ? WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $project_date, $project_name, $status, $member_id, $project_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Proyek berhasil diperbarui"];
    } else {
        $response = ["status" => "error", "message" => "Gagal memperbarui proyek"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_project") {
    $project_id = $_POST["project_id"];
    
    $sql = "DELETE FROM project WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Proyek berhasil dihapus"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menghapus proyek"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_member_from_project") {
    $project_id = $_POST["project_id"];
    $member_id = $_POST["member_id"];
    
    $sql = "DELETE FROM project WHERE project_id = ? AND member_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $project_id, $member_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Anggota berhasil dihapus dari proyek"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menghapus anggota dari proyek"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$members = getAnggota($conn);
$projects = getUniqueProyek($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Karya Ilmiah Remaja SMKN 1 Pacitan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @keyframes blurShift {
            0%, 100% { filter: blur(80px); }
            50% { filter: blur(120px); }
        }

        @keyframes floatAnimation {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
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

        @keyframes glassHover {
            0% {
                background: rgba(255, 255, 255, 0.15);
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            }
            50% {
                background: rgba(255, 255, 255, 0.25);
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.25);
            }
            100% {
                background: rgba(255, 255, 255, 0.2);
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            background-attachment: fixed;
            color: #333;
        }
        
        .dashboard-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .dashboard-container::before {
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

        .dashboard-container::after {
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
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 40px rgba(102, 126, 234, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            animation: slideInDown 0.6s ease-out;
            position: relative;
            z-index: 10;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.8px;
            text-shadow: 0 8px 20px rgba(0, 0, 0, 0.25), 0 0 30px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .user-info {
            font-size: 14px;
            font-weight: 500;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 24px 0 rgba(31, 38, 135, 0.2);
            transform: translateY(-2px);
        }

        .logout-btn:active {
            transform: translateY(0);
        }
        
        .nav-bar {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            gap: 0;
            padding: 0 40px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 9;
        }
        
        .nav-item {
            padding: 16px 25px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 15px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            border-bottom: 3px solid transparent;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }
        
        .nav-item:hover {
            color: rgba(255, 255, 255, 0.95);
            transform: translateY(-1px);
            text-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .nav-item.active {
            color: white;
            border-bottom-color: rgba(255, 255, 255, 0.6);
            text-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
        }
        
        .main-content {
            flex: 1;
            padding: 40px;
            position: relative;
            z-index: 5;
            overflow-y: auto;
        }
        
        .content-section {
            display: none;
            animation: slideInUp 0.5s ease-out;
        }
        
        .content-section.active {
            display: block;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 28px;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.8);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .add-btn {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 12px 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
        }
        
        .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.35);
            border-color: rgba(255, 255, 255, 0.3);
            background: linear-gradient(135deg, rgba(102, 126, 234, 1) 0%, rgba(118, 75, 162, 1) 100%);
        }

        .add-btn:active {
            transform: translateY(-1px);
        }
        
        .table-wrapper {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.12);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: rgba(102, 126, 234, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        th {
            padding: 16px 18px;
            text-align: left;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.8);
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        td {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(0, 0, 0, 0.7);
            font-size: 14px;
        }
        
        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.08);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .edit-btn, .delete-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
        }
        
        .edit-btn {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .edit-btn:hover {
            background: rgba(40, 167, 69, 0.3);
            border-color: rgba(40, 167, 69, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.25);
            color: #1e7e34;
        }

        .edit-btn:active {
            transform: translateY(0);
        }
        
        .delete-btn {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .delete-btn:hover {
            background: rgba(220, 53, 69, 0.3);
            border-color: rgba(220, 53, 69, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.25);
            color: #bd2130;
        }

        .delete-btn:active {
            transform: translateY(0);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(31, 38, 135, 0.2);
            width: 90%;
            max-width: 500px;
            animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 15px;
        }
        
        .modal-header h2 {
            font-size: 22px;
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #ffffff;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(102, 126, 234, 0.6);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 12px 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.35);
            border-color: rgba(255, 255, 255, 0.3);
            background: linear-gradient(135deg, rgba(102, 126, 234, 1) 0%, rgba(118, 75, 162, 1) 100%);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }
        
        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #ffffff;
            padding: 12px 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .btn-cancel:active {
            transform: translateY(0);
        }
        
        .confirm-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        .confirm-modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .confirm-modal-content {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(31, 38, 135, 0.2);
            width: 90%;
            max-width: 400px;
            animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .confirm-modal-header h2 {
            font-size: 20px;
            color: #ffffff;
            margin-bottom: 15px;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .confirm-modal-body {
            color: #ffffff;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.6;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            font-weight: 500;
        }
        
        .confirm-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-confirm {
            background: rgba(220, 53, 69, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            box-shadow: 0 8px 24px rgba(220, 53, 69, 0.2);
        }
        
        .btn-confirm:hover {
            background: rgba(220, 53, 69, 1);
            box-shadow: 0 12px 32px rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
        }

        .btn-confirm:active {
            transform: translateY(0);
        }
        
        .btn-cancel-confirm {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: rgba(0, 0, 0, 0.7);
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
        }
        
        .btn-cancel-confirm:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: rgba(0, 0, 0, 0.8);
        }

        .btn-cancel-confirm:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px 18px;
            margin-bottom: 20px;
            border-radius: 12px;
            font-size: 14px;
            display: none;
            font-weight: 600;
            border: 1px solid;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: slideInDown 0.4s ease-out;
        }
        
        .alert.show {
            display: block;
        }
        
        .alert-success {
            background: rgba(52, 211, 153, 0.15);
            color: #059669;
            border-color: rgba(52, 211, 153, 0.3);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .expanded-row {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .expand-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: rgba(102, 126, 234, 0.7);
            padding: 0 10px;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
        }

        .expand-btn:hover {
            color: rgba(102, 126, 234, 1);
        }
        
        .expand-btn.open {
            transform: rotate(180deg);
        }
        
        .member-row-cell {
            padding: 12px 15px !important;
            border-bottom: 1px solid #ddd;
        }
        
        .member-row-cell:first-child {
            padding-left: 50px !important;
        }
        
        .member-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .member-details {
            flex: 1;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>KIR - Karya Ilmiah Remaja</h1>
            <div class="header-info">
                <div class="user-info">
                    Selamat datang, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong> (<?php echo htmlspecialchars($_SESSION["role"]); ?>)
                </div>
                <a href="logout.php" class="logout-btn">Keluar</a>
            </div>
        </div>
        
        <div class="nav-bar">
            <button class="nav-item <?php echo ($current_page == 'anggota') ? 'active' : ''; ?>" data-page="anggota">
                Anggota
            </button>
            <button class="nav-item <?php echo ($current_page == 'proyek') ? 'active' : ''; ?>" data-page="proyek">
                Proyek
            </button>
        </div>
        
        <div class="main-content">
            <div id="successAlert" class="alert alert-success"></div>
            <div id="errorAlert" class="alert alert-error"></div>
            
            <div id="anggota-section" class="content-section <?php echo ($current_page == 'anggota') ? 'active' : ''; ?>">
                <div class="section-header">
                    <h2 class="section-title">Manajemen Anggota</h2>
                    <button class="add-btn" onclick="openAddMemberModal()">+ Tambah Anggota</button>
                </div>
                
                <div class="table-wrapper">
                    <?php if (empty($members)): ?>
                        <div class="empty-state">
                            <p>Belum ada data anggota</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Tahun Masuk</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['class']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($member['enrollment_date'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="edit-btn" onclick="openEditMemberModal(<?php echo htmlspecialchars(json_encode($member)); ?>)">Edit</button>
                                                <button class="delete-btn" onclick="openDeleteConfirm('member', <?php echo $member['member_id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>')">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="proyek-section" class="content-section <?php echo ($current_page == 'proyek') ? 'active' : ''; ?>">
                <div class="section-header">
                    <h2 class="section-title">Manajemen Proyek</h2>
                    <button class="add-btn" onclick="openAddProjectModal()">+ Tambah Proyek</button>
                </div>
                
                <div class="table-wrapper">
                    <?php if (empty($projects)): ?>
                        <div class="empty-state">
                            <p>Belum ada data proyek</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Tanggal</th>
                                    <th>Nama Proyek</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): 
                                    $projectMembers = getMembersByProject($conn, $project['project_id']);
                                ?>
                                    <tr class="project-row" data-project-id="<?php echo $project['project_id']; ?>">
                                        <td style="text-align: center;">
                                            <button class="expand-btn" onclick="toggleExpandProject(<?php echo $project['project_id']; ?>)">▼</button>
                                        </td>
                                        <td><?php echo date('d-m-Y', strtotime($project['project_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; <?php echo ($project['status'] == 'done') ? 'background-color: #d4edda; color: #155724;' : 'background-color: #fff3cd; color: #856404;'; ?>">
                                                <?php echo ucfirst($project['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="edit-btn" onclick="openEditProjectModal(<?php echo htmlspecialchars(json_encode($project)); ?>)">Edit</button>
                                                <button class="delete-btn" onclick="openDeleteConfirm('project', <?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['project_name']); ?>')">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <?php foreach ($projectMembers as $member): ?>
                                        <tr class="member-expanded-row expanded-row" data-project-id="<?php echo $project['project_id']; ?>" style="display: none;">
                                            <td colspan="5">
                                                <div style="padding: 15px; background-color: #f8f9fa; margin: 0 -15px; border-left: 4px solid #667eea;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                                        <div style="flex: 1;">
                                                            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                                                <?php echo htmlspecialchars($member['name']); ?>
                                                            </div>
                                                            <div style="color: #666; font-size: 13px;">
                                                                Kelas: <?php echo htmlspecialchars($member['class']); ?> | Tahun Masuk: <?php echo date('d-m-Y', strtotime($member['enrollment_date'])); ?>
                                                            </div>
                                                        </div>
                                                        <div class="action-buttons">
                                                            <button class="delete-btn" onclick="openDeleteConfirm('member_from_project', <?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>', <?php echo $member['member_id']; ?>)">Hapus dari Proyek</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="memberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="memberModalTitle">Tambah Anggota</h2>
                <button class="close-btn" onclick="closeMemberModal()">&times;</button>
            </div>
            <form id="memberForm" onsubmit="submitMemberForm(event)">
                <input type="hidden" id="memberId" name="member_id">
                <input type="hidden" name="action" id="memberAction" value="add_member">
                
                <div class="form-group">
                    <label for="memberName">Nama Anggota</label>
                    <input type="text" id="memberName" name="name" placeholder="Masukkan nama anggota" required>
                </div>
                
                <div class="form-group">
                    <label for="memberClass">Kelas</label>
                    <input type="text" id="memberClass" name="class" placeholder="Contoh: 10A, 11B" required>
                </div>
                
                <div class="form-group">
                    <label for="memberDate">Tahun Masuk</label>
                    <input type="date" id="memberDate" name="enrollment_date" required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeMemberModal()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="projectModalTitle">Tambah Proyek</h2>
                <button class="close-btn" onclick="closeProjectModal()">&times;</button>
            </div>
            <form id="projectForm" onsubmit="submitProjectForm(event)">
                <input type="hidden" id="projectId" name="project_id">
                <input type="hidden" name="action" id="projectAction" value="add_project">
                
                <div class="form-group">
                    <label for="projectDate">Tanggal Proyek</label>
                    <input type="date" id="projectDate" name="project_date" required>
                </div>
                
                <div class="form-group">
                    <label for="projectName">Nama Proyek</label>
                    <input type="text" id="projectName" name="project_name" placeholder="Masukkan nama proyek" required>
                </div>
                
                <div class="form-group">
                    <label for="projectMember">Anggota</label>
                    <select id="projectMember" name="member_id" required>
                        <option value="">-- Pilih Anggota --</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['member_id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="projectStatus">Status</label>
                    <select id="projectStatus" name="status" required>
                        <option value="undone">Belum Selesai</option>
                        <option value="done">Selesai</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeProjectModal()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-header">
                <h2>Konfirmasi Penghapusan</h2>
            </div>
            <div class="confirm-modal-body">
                <p>Apakah Anda yakin ingin menghapus <strong id="deleteItemName"></strong>?</p>
                <p style="color: #ffffff; font-size: 12px; margin-top: 10px;">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="confirm-modal-footer">
                <button class="btn-cancel-confirm" onclick="closeDeleteConfirm()">Batal</button>
                <button class="btn-confirm" onclick="confirmDelete()">Hapus</button>
            </div>
        </div>
    </div>
    
    <script>
        let deleteType = '';
        let deleteId = '';
        let deleteItemId = '';
        let members = <?php echo json_encode($members); ?>;
        
        function toggleExpandProject(projectId) {
            const rows = document.querySelectorAll(`tr[data-project-id="${projectId}"].member-expanded-row`);
            const expandBtn = document.querySelector(`tr[data-project-id="${projectId}"].project-row .expand-btn`);
            
            rows.forEach(row => {
                if (row.style.display === 'none' || !row.style.display) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
            
            expandBtn.classList.toggle('open');
        }
        
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                const page = this.getAttribute('data-page');
                
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(page + '-section').classList.add('active');
                
                window.history.pushState({}, '', '?page=' + page);
            });
        });
        
        function openAddMemberModal() {
            document.getElementById('memberModalTitle').textContent = 'Tambah Anggota';
            document.getElementById('memberAction').value = 'add_member';
            document.getElementById('memberForm').reset();
            document.getElementById('memberId').value = '';
            document.getElementById('memberModal').classList.add('show');
        }
        
        function openEditMemberModal(member) {
            document.getElementById('memberModalTitle').textContent = 'Edit Anggota';
            document.getElementById('memberAction').value = 'edit_member';
            document.getElementById('memberId').value = member.member_id;
            document.getElementById('memberName').value = member.name;
            document.getElementById('memberClass').value = member.class;
            document.getElementById('memberDate').value = member.enrollment_date;
            document.getElementById('memberModal').classList.add('show');
        }
        
        function closeMemberModal() {
            document.getElementById('memberModal').classList.remove('show');
        }
        
        function submitMemberForm(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('memberForm'));
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('successAlert', data.message);
                    closeMemberModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('errorAlert', data.message);
                }
            })
            .catch(error => {
                showAlert('errorAlert', 'Terjadi kesalahan');
                console.error('Error:', error);
            });
        }
        
        function openAddProjectModal() {
            document.getElementById('projectModalTitle').textContent = 'Tambah Proyek';
            document.getElementById('projectAction').value = 'add_project';
            document.getElementById('projectForm').reset();
            document.getElementById('projectId').value = '';
            document.getElementById('projectModal').classList.add('show');
        }
        
        function openEditProjectModal(project) {
            document.getElementById('projectModalTitle').textContent = 'Edit Proyek';
            document.getElementById('projectAction').value = 'edit_project';
            document.getElementById('projectId').value = project.project_id;
            document.getElementById('projectDate').value = project.project_date;
            document.getElementById('projectName').value = project.project_name;
            document.getElementById('projectStatus').value = project.status;
            document.getElementById('projectModal').classList.add('show');
        }
        
        function closeProjectModal() {
            document.getElementById('projectModal').classList.remove('show');
        }
        
        function submitProjectForm(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('projectForm'));
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('successAlert', data.message);
                    closeProjectModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('errorAlert', data.message);
                }
            })
            .catch(error => {
                showAlert('errorAlert', 'Terjadi kesalahan');
                console.error('Error:', error);
            });
        }
        
        function openDeleteConfirm(type, id, name, itemId = '') {
            deleteType = type;
            deleteId = id;
            deleteItemId = itemId;
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('confirmModal').classList.add('show');
        }
        
        function closeDeleteConfirm() {
            document.getElementById('confirmModal').classList.remove('show');
        }
        
        function confirmDelete() {
            let action = '';
            
            if (deleteType === 'member') {
                action = 'delete_member';
            } else if (deleteType === 'project') {
                action = 'delete_project';
            } else if (deleteType === 'member_from_project') {
                action = 'delete_member_from_project';
            }
            
            const formData = new FormData();
            formData.append('action', action);
            
            if (deleteType === 'member') {
                formData.append('member_id', deleteId);
            } else if (deleteType === 'project') {
                formData.append('project_id', deleteId);
            } else if (deleteType === 'member_from_project') {
                formData.append('project_id', deleteId);
                formData.append('member_id', deleteItemId);
            }
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('successAlert', data.message);
                    closeDeleteConfirm();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('errorAlert', data.message);
                }
            })
            .catch(error => {
                showAlert('errorAlert', 'Terjadi kesalahan');
                console.error('Error:', error);
            });
        }
        
        function showAlert(alertId, message) {
            const alert = document.getElementById(alertId);
            alert.textContent = message;
            alert.classList.add('show');
            
            setTimeout(() => {
                alert.classList.remove('show');
            }, 3000);
        }
        
        window.onclick = function(event) {
            const memberModal = document.getElementById('memberModal');
            const projectModal = document.getElementById('projectModal');
            const confirmModal = document.getElementById('confirmModal');
            
            if (event.target === memberModal) {
                memberModal.classList.remove('show');
            }
            if (event.target === projectModal) {
                projectModal.classList.remove('show');
            }
            if (event.target === confirmModal) {
                confirmModal.classList.remove('show');
            }
        };
    </script>
</body>
</html>
