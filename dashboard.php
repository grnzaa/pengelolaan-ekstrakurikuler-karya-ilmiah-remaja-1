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
    // status is stored per member, not per project; including it here caused
    // duplicate rows when different members had different statuses.  We only
    // need the basic project info for the main list.
    $sql = "SELECT DISTINCT 
            p.project_id, 
            p.project_date, 
            p.project_name
            FROM project p
            ORDER BY p.project_id";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPembina($conn) {
    $sql = "SELECT * FROM coach";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPembinaWithPeriod($conn) {
    $sql = "SELECT 
            c.coach_id, 
            c.coach_name, 
            p.period_id, 
            p.enrollment_year
            FROM coach c
            LEFT JOIN period p ON c.coach_id = p.coach_id
            ORDER BY c.coach_id, p.enrollment_year DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPengurus($conn) {
    $sql = "SELECT 
            s.supervisor_id,
            s.position_id,
            s.period_id,
            s.member_id,
            m.name,
            p.serving_as,
            per.enrollment_year
            FROM supervisor s
            JOIN position p ON s.position_id = p.position_id
            JOIN member m ON s.member_id = m.member_id
            LEFT JOIN period per ON s.period_id = per.period_id
            ORDER BY per.enrollment_year DESC, s.period_id DESC, p.serving_as";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPositions($conn) {
    $sql = "SELECT * FROM position ORDER BY position_id";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function mapServingAs($key) {
    $map = [
        'ceo' => 'Ketua',
        'co-ceo' => 'Wakil Ketua',
        'secretary 1' => 'Sekretaris 1',
        'secretary 2' => 'Sekretaris 2',
        'treasurer 1' => 'Bendahara 1',
        'treasurer 2' => 'Bendahara 2',
    ];

    return isset($map[$key]) ? $map[$key] : ucfirst($key);
}

function getMembersByProject($conn, $project_id) {
    $sql = "SELECT 
            m.member_id, 
            m.name, 
            m.class, 
            m.enrollment_date,
            p.status as member_status
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
    ob_clean();
    $response = ["status" => "error", "message" => "Terjadi kesalahan"];
    
    try {
        $project_date = $_POST["project_date"] ?? null;
        $project_name = $_POST["project_name"] ?? null;
        
        if (!$project_date || !$project_name) {
            throw new Exception("Data proyek tidak lengkap");
        }
        
        // Get all members
        $members_sql = "SELECT member_id FROM member ORDER BY member_id";
        $members_result = $conn->query($members_sql);
        
        if (!$members_result) {
            throw new Exception("Gagal mengambil data anggota");
        }
        
        $members_array = [];
        while ($member_row = $members_result->fetch_assoc()) {
            $members_array[] = $member_row['member_id'];
        }
        
        if (empty($members_array)) {
            throw new Exception("Belum ada data anggota. Tambahkan anggota terlebih dahulu.");
        }
        
        // Insert first member to get the project_id
        $insert_member_sql = "INSERT INTO project (project_date, project_name, member_id, status) VALUES (?, ?, ?, 0)";
        $insert_member_stmt = $conn->prepare($insert_member_sql);
        
        if (!$insert_member_stmt) {
            throw new Exception("Prepare error di insert member");
        }
        
        $insert_member_stmt->bind_param("ssi", $project_date, $project_name, $members_array[0]);
        
        if (!$insert_member_stmt->execute()) {
            throw new Exception("Gagal membuat proyek");
        }
        
        $project_id = $insert_member_stmt->insert_id;
        $insert_member_stmt->close();
        
        // Insert remaining members
        for ($i = 1; $i < count($members_array); $i++) {
            $member_id = $members_array[$i];
            $insert_rest_sql = "INSERT INTO project (project_id, project_date, project_name, member_id, status) VALUES (?, ?, ?, ?, 0)";
            $insert_rest_stmt = $conn->prepare($insert_rest_sql);
            
            if (!$insert_rest_stmt) {
                throw new Exception("Prepare error di insert member ke-" . ($i + 1));
            }
            
            $insert_rest_stmt->bind_param("issi", $project_id, $project_date, $project_name, $member_id);
            
            if (!$insert_rest_stmt->execute()) {
                throw new Exception("Gagal insert member ke-" . ($i + 1));
            }
            $insert_rest_stmt->close();
        }
        
        $response = ["status" => "success", "message" => "Proyek berhasil ditambahkan"];
        
    } catch (Exception $e) {
        $response = ["status" => "error", "message" => $e->getMessage()];
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "edit_project") {
    $project_id = $_POST["project_id"];
    $project_date = $_POST["project_date"];
    $project_name = $_POST["project_name"];

    // Update all rows with this project_id to keep data in sync across all members
    $sql = "UPDATE project SET project_date = ?, project_name = ? WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $project_date, $project_name, $project_id);
    
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "set_member_status") {
    $project_id = $_POST["project_id"];
    $member_id = $_POST["member_id"];
    $status = isset($_POST["status"]) ? (int)$_POST["status"] : 0;

    $sql = "UPDATE project SET status = ? WHERE project_id = ? AND member_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $status, $project_id, $member_id);

    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Status anggota diperbarui"];
    } else {
        $response = ["status" => "error", "message" => "Gagal memperbarui status anggota"];
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "add_pembina") {
    $coach_name = $_POST["coach_name"];
    $enrollment_year = $_POST["enrollment_year"];
    
    $sql = "INSERT INTO coach (coach_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $coach_name);
    
    if ($stmt->execute()) {
        $coach_id = $stmt->insert_id;
        $stmt->close();
        
        if (!empty($enrollment_year)) {
            $sql_period = "INSERT INTO period (enrollment_year, coach_id) VALUES (?, ?)";
            $stmt_period = $conn->prepare($sql_period);
            $stmt_period->bind_param("ii", $enrollment_year, $coach_id);
            $stmt_period->execute();
            $stmt_period->close();
        }
        
        $response = ["status" => "success", "message" => "Pembina berhasil ditambahkan"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menambah pembina"];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "edit_pembina") {
    $coach_id = $_POST["coach_id"];
    $coach_name = $_POST["coach_name"];
    $enrollment_year = $_POST["enrollment_year"];
    $period_id = $_POST["period_id"];
    
    $sql = "UPDATE coach SET coach_name = ? WHERE coach_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $coach_name, $coach_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        if (!empty($enrollment_year)) {
            if (!empty($period_id)) {
                $sql_period = "UPDATE period SET enrollment_year = ? WHERE period_id = ?";
                $stmt_period = $conn->prepare($sql_period);
                $stmt_period->bind_param("ii", $enrollment_year, $period_id);
                $stmt_period->execute();
                $stmt_period->close();
            } else {
                $sql_period = "INSERT INTO period (enrollment_year, coach_id) VALUES (?, ?)";
                $stmt_period = $conn->prepare($sql_period);
                $stmt_period->bind_param("ii", $enrollment_year, $coach_id);
                $stmt_period->execute();
                $stmt_period->close();
            }
        }
        
        $response = ["status" => "success", "message" => "Pembina berhasil diperbarui"];
    } else {
        $response = ["status" => "error", "message" => "Gagal memperbarui pembina"];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_pembina") {
    $coach_id = $_POST["coach_id"];
    
    $sql = "DELETE FROM coach WHERE coach_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $coach_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Pembina berhasil dihapus"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menghapus pembina"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "add_pengurus") {
    $member_id = $_POST["member_id"];
    $position_id = $_POST["position_id"];
    $period_id = $_POST["period_id"];
    
    $sql = "INSERT INTO supervisor (member_id, position_id, period_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $member_id, $position_id, $period_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Pengurus berhasil ditambahkan"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menambah pengurus"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "edit_pengurus") {
    $supervisor_id = $_POST["supervisor_id"];
    $member_id = $_POST["member_id"];
    $position_id = $_POST["position_id"];
    $period_id = $_POST["period_id"];
    
    $sql = "UPDATE supervisor SET member_id = ?, position_id = ?, period_id = ? WHERE supervisor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $member_id, $position_id, $period_id, $supervisor_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Pengurus berhasil diperbarui"];
    } else {
        $response = ["status" => "error", "message" => "Gagal memperbarui pengurus"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_pengurus") {
    $supervisor_id = $_POST["supervisor_id"];
    
    $sql = "DELETE FROM supervisor WHERE supervisor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $supervisor_id);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "Pengurus berhasil dihapus"];
    } else {
        $response = ["status" => "error", "message" => "Gagal menghapus pengurus"];
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$members = getAnggota($conn);
$projects = getUniqueProyek($conn);
$pembina = getPembinaWithPeriod($conn);
$pengurus = getPengurus($conn);
$positions = getPositions($conn);
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

        /* Override select text color to ensure dropdown text is visible */
        .form-group select {
            color: #000000;
        }

        .form-group select option {
            color: #000000;
            background: #ffffff;
        }

        .form-group select:focus {
            color: #000000;
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
                    Selamat datang, <strong> <?php echo htmlspecialchars($_SESSION["username"] ?? 'Guest'); ?> </strong> (<?php echo htmlspecialchars($_SESSION["role"] ?? 'No Role'); ?>)
                </div>
                <a href="logout.php" class="logout-btn">Keluar</a>
            </div>
        </div>
        
        <div class="nav-bar">
            <button class="nav-item <?php echo ($current_page == 'anggota') ? 'active' : ''; ?>" data-page="anggota">
                Anggota
            </button>
            <button class="nav-item <?php echo ($current_page == 'pembina') ? 'active' : ''; ?>" data-page="pembina">
                Pembina
            </button>
            <button class="nav-item <?php echo ($current_page == 'pengurus') ? 'active' : ''; ?>" data-page="pengurus">
                Pengurus
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
            
            <div id="pembina-section" class="content-section <?php echo ($current_page == 'pembina') ? 'active' : ''; ?>">
                <div class="section-header">
                    <h2 class="section-title">Manajemen Pembina</h2>
                    <button class="add-btn" onclick="openAddPembinaModal()">+ Tambah Pembina</button>
                </div>
                
                <?php
                    // Get the latest period year
                    $latest_year = null;
                    $latest_coach = null;
                    foreach ($pembina as $coach) {
                        if ($coach['enrollment_year'] !== null) {
                            if ($latest_year === null || $coach['enrollment_year'] > $latest_year) {
                                $latest_year = $coach['enrollment_year'];
                                $latest_coach = $coach['coach_name'];
                            }
                        }
                    }
                ?>
                
                <?php if ($latest_coach && $latest_year): ?>
                    <h3 style="margin-bottom: 20px; color: #333; font-size: 18px;">Pembina Tahun <?php echo htmlspecialchars($latest_year); ?>: <strong><?php echo htmlspecialchars($latest_coach); ?></strong></h3>
                <?php endif; ?>
                
                <div class="table-wrapper">
                    <?php if (empty($pembina)): ?>
                        <div class="empty-state">
                            <p>Belum ada data pembina</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pembina</th>
                                    <th>Tahun Periode</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $displayed_coaches = [];
                                foreach ($pembina as $coach): 
                                    if (!in_array($coach['coach_id'], $displayed_coaches)):
                                        $displayed_coaches[] = $coach['coach_id'];
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($coach['coach_name']); ?></td>
                                        <td><?php echo !empty($coach['enrollment_year']) ? htmlspecialchars($coach['enrollment_year']) : '-'; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="edit-btn" onclick="openEditPembinaModal(<?php echo htmlspecialchars(json_encode($coach)); ?>)">Edit</button>
                                                <button class="delete-btn" onclick="openDeleteConfirm('pembina', <?php echo $coach['coach_id']; ?>, '<?php echo htmlspecialchars($coach['coach_name']); ?>')">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="pengurus-section" class="content-section <?php echo ($current_page == 'pengurus') ? 'active' : ''; ?>">
                <div class="section-header">
                    <h2 class="section-title">Manajemen Pengurus</h2>
                    <button class="add-btn" onclick="openAddPengurusModal()">+ Tambah Pengurus</button>
                </div>
                
                <?php
                    // Get the latest period year and pengurus for that year
                    $latest_pengurus_year = null;
                    $pengurus_by_year = [];
                    
                    foreach ($pengurus as $p) {
                        if ($p['enrollment_year'] !== null) {
                            if (!isset($pengurus_by_year[$p['enrollment_year']])) {
                                $pengurus_by_year[$p['enrollment_year']] = [];
                            }
                            $pengurus_by_year[$p['enrollment_year']][] = $p;
                            
                            if ($latest_pengurus_year === null || $p['enrollment_year'] > $latest_pengurus_year) {
                                $latest_pengurus_year = $p['enrollment_year'];
                            }
                        }
                    }
                ?>
                
                <?php if ($latest_pengurus_year && isset($pengurus_by_year[$latest_pengurus_year])): ?>
                    <h4 style="margin-bottom: 15px; color: #333; font-size: 16px;">Pengurus Tahun <?php echo htmlspecialchars($latest_pengurus_year); ?>:</h4>
                    <div style="margin-bottom: 25px; padding: 10px 0;">
                        <?php foreach ($pengurus_by_year[$latest_pengurus_year] as $p): ?>
                            <div style="padding: 8px 0; color: #555;">
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong> - <?php echo htmlspecialchars(mapServingAs($p['serving_as'])); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="table-wrapper">
                    <?php if (empty($pengurus)): ?>
                        <div class="empty-state">
                            <p>Belum ada data pengurus</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pengurus</th>
                                    <th>Jabatan</th>
                                    <th>Tahun Periode</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($pengurus as $p): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><?php echo htmlspecialchars(mapServingAs($p['serving_as'])); ?></td>
                                        <td><?php echo !empty($p['enrollment_year']) ? htmlspecialchars($p['enrollment_year']) : '-'; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="edit-btn" onclick="openEditPengurusModal(<?php echo htmlspecialchars(json_encode($p)); ?>)">Edit</button>
                                                <button class="delete-btn" onclick="openDeleteConfirm('pengurus', <?php echo $p['supervisor_id']; ?>, '<?php echo htmlspecialchars($p['name']); ?>')">Hapus</button>
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
                                    <!-- status column removed; status is now shown per-member inside expand -->
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
                                        <!-- status indicator removed from main row; status is shown per-member inside the expanded details -->
                                        <td>
                                            <div class="action-buttons">
                                                <button class="edit-btn" onclick="openEditProjectModal(<?php echo htmlspecialchars(json_encode($project)); ?>)">Edit</button>
                                                <button class="delete-btn" onclick="openDeleteConfirm('project', <?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['project_name']); ?>')">Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <tr class="member-expanded-row expanded-row" data-project-id="<?php echo $project['project_id']; ?>" style="display: none;">
                                        <td colspan="5">
                                            <div style="padding: 10px; background-color: #f8f9fa; margin: 0 -15px; border-left: 4px solid #667eea;">
                                                <table style="width:100%; border-collapse: collapse;">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:50%; text-align:left; padding:8px;">Selesai</th>
                                                            <th style="width:50%; text-align:left; padding:8px;">Belum Selesai</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td style="vertical-align:top; padding:8px;">
                                                                <?php foreach ($projectMembers as $m): if ($m['member_status'] == 1): ?>
                                                                    <div style="padding:6px 0; display:flex; justify-content:space-between; align-items:center;">
                                                                        <div>
                                                                            <div style="font-weight:600; color:#333"><?php echo htmlspecialchars($m['name']); ?></div>
                                                                            <div style="color:#666; font-size:13px;">Kelas: <?php echo htmlspecialchars($m['class']); ?></div>
                                                                        </div>
                                                                        <div style="display:flex; gap:8px;">
                                                                            <button class="btn-toggle" onclick="setMemberStatus(<?php echo $project['project_id']; ?>, <?php echo $m['member_id']; ?>, 0)" style="background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500;">Belum Selesai</button>
                                                                            <button class="delete-btn" onclick="openDeleteConfirm('member_from_project', <?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($m['name']); ?>', <?php echo $m['member_id']; ?>)">Hapus</button>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; endforeach; ?>
                                                            </td>
                                                            <td style="vertical-align:top; padding:8px;">
                                                                <?php foreach ($projectMembers as $m): if ($m['member_status'] != 1): ?>
                                                                    <div style="padding:6px 0; display:flex; justify-content:space-between; align-items:center;">
                                                                        <div>
                                                                            <div style="font-weight:600; color:#333"><?php echo htmlspecialchars($m['name']); ?></div>
                                                                            <div style="color:#666; font-size:13px;">Kelas: <?php echo htmlspecialchars($m['class']); ?></div>
                                                                        </div>
                                                                        <div style="display:flex; gap:8px;">
                                                                            <button class="btn-toggle" onclick="setMemberStatus(<?php echo $project['project_id']; ?>, <?php echo $m['member_id']; ?>, 1)" style="background-color: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500;">Selesai</button>
                                                                            <button class="delete-btn" onclick="openDeleteConfirm('member_from_project', <?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($m['name']); ?>', <?php echo $m['member_id']; ?>)">Hapus</button>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; endforeach; ?>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
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
    
    <div id="pembinaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="pembinaModalTitle">Tambah Pembina</h2>
                <button class="close-btn" onclick="closePembinaModal()">&times;</button>
            </div>
            <form id="pembinaForm" onsubmit="submitPembinaForm(event)">
                <input type="hidden" id="pembinaId" name="coach_id">
                <input type="hidden" id="periodeId" name="period_id">
                <input type="hidden" name="action" id="pembinaAction" value="add_pembina">
                
                <div class="form-group">
                    <label for="pembinaName">Nama Pembina</label>
                    <input type="text" id="pembinaName" name="coach_name" placeholder="Masukkan nama pembina" required>
                </div>
                
                <div class="form-group">
                    <label for="pembinaYear">Tahun Periode</label>
                    <input type="number" id="pembinaYear" name="enrollment_year" placeholder="Contoh: 2025" min="2000" max="2099">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closePembinaModal()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="pengurusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="pengurusModalTitle">Tambah Pengurus</h2>
                <button class="close-btn" onclick="closePengurusModal()">&times;</button>
            </div>
            <form id="pengurusForm" onsubmit="submitPengurusForm(event)">
                <input type="hidden" id="pengurusId" name="supervisor_id">
                <input type="hidden" name="action" id="pengurusAction" value="add_pengurus">
                
                <div class="form-group">
                    <label for="pengurusMember">Nama Pengurus</label>
                    <select id="pengurusMember" name="member_id" required>
                        <option value="">-- Pilih Anggota --</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['member_id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="pengurusPosition">Jabatan</label>
                    <select id="pengurusPosition" name="position_id" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <?php foreach ($positions as $pos): ?>
                            <option value="<?php echo $pos['position_id']; ?>"><?php echo htmlspecialchars(mapServingAs($pos['serving_as'])); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="pengurusPeriod">Tahun Periode</label>
                    <select id="pengurusPeriod" name="period_id" required>
                        <option value="">-- Pilih Periode --</option>
                        <?php 
                        $unique_periods = [];
                        foreach ($pembina as $coach) {
                            if (!empty($coach['period_id']) && !in_array($coach['period_id'], $unique_periods)) {
                                $unique_periods[] = $coach['period_id'];
                        ?>
                            <option value="<?php echo $coach['period_id']; ?>"><?php echo htmlspecialchars($coach['enrollment_year']); ?></option>
                        <?php 
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closePengurusModal()">Batal</button>
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
                
                <!-- Anggota dan status akan ditentukan setelah proyek dibuat -->
                
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
        
        function openAddPembinaModal() {
            document.getElementById('pembinaModalTitle').textContent = 'Tambah Pembina';
            document.getElementById('pembinaAction').value = 'add_pembina';
            document.getElementById('pembinaForm').reset();
            document.getElementById('pembinaId').value = '';
            document.getElementById('periodeId').value = '';
            document.getElementById('pembinaModal').classList.add('show');
        }
        
        function openEditPembinaModal(coach) {
            document.getElementById('pembinaModalTitle').textContent = 'Edit Pembina';
            document.getElementById('pembinaAction').value = 'edit_pembina';
            document.getElementById('pembinaId').value = coach.coach_id;
            document.getElementById('periodeId').value = coach.period_id || '';
            document.getElementById('pembinaName').value = coach.coach_name;
            document.getElementById('pembinaYear').value = coach.enrollment_year || '';
            document.getElementById('pembinaModal').classList.add('show');
        }
        
        function closePembinaModal() {
            document.getElementById('pembinaModal').classList.remove('show');
        }
        
        function submitPembinaForm(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('pembinaForm'));
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('successAlert', data.message);
                    closePembinaModal();
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
        
        function openAddPengurusModal() {
            document.getElementById('pengurusModalTitle').textContent = 'Tambah Pengurus';
            document.getElementById('pengurusAction').value = 'add_pengurus';
            document.getElementById('pengurusForm').reset();
            document.getElementById('pengurusId').value = '';
            document.getElementById('pengurusModal').classList.add('show');
        }
        
        function openEditPengurusModal(pengurus) {
            document.getElementById('pengurusModalTitle').textContent = 'Edit Pengurus';
            document.getElementById('pengurusAction').value = 'edit_pengurus';
            document.getElementById('pengurusId').value = pengurus.supervisor_id;
            document.getElementById('pengurusMember').value = pengurus.member_id;
            document.getElementById('pengurusPosition').value = pengurus.position_id;
            document.getElementById('pengurusPeriod').value = pengurus.period_id;
            document.getElementById('pengurusModal').classList.add('show');
        }
        
        function closePengurusModal() {
            document.getElementById('pengurusModal').classList.remove('show');
        }
        
        function submitPengurusForm(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('pengurusForm'));
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('successAlert', data.message);
                    closePengurusModal();
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
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success') {
                        showAlert('successAlert', data.message);
                        closeProjectModal();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('errorAlert', data.message);
                    }
                } catch (e) {
                    console.error('JSON Parse error:', e);
                    console.error('Response was:', text);
                    showAlert('errorAlert', 'Terjadi kesalahan pada server');
                }
            })
            .catch(error => {
                showAlert('errorAlert', 'Terjadi kesalahan jaringan');
                console.error('Fetch error:', error);
            });
        }

        function setMemberStatus(projectId, memberId, status) {
            const formData = new FormData();
            formData.append('action', 'set_member_status');
            formData.append('project_id', projectId);
            formData.append('member_id', memberId);
            formData.append('status', status);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('successAlert', data.message);
                    setTimeout(() => location.reload(), 800);
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
            } else if (deleteType === 'pembina') {
                action = 'delete_pembina';
            } else if (deleteType === 'pengurus') {
                action = 'delete_pengurus';
            } else if (deleteType === 'project') {
                action = 'delete_project';
            } else if (deleteType === 'member_from_project') {
                action = 'delete_member_from_project';
            }
            
            const formData = new FormData();
            formData.append('action', action);
            
            if (deleteType === 'member') {
                formData.append('member_id', deleteId);
            } else if (deleteType === 'pembina') {
                formData.append('coach_id', deleteId);
            } else if (deleteType === 'pengurus') {
                formData.append('supervisor_id', deleteId);
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
            const pembinaModal = document.getElementById('pembinaModal');
            const pengurusModal = document.getElementById('pengurusModal');
            const projectModal = document.getElementById('projectModal');
            const confirmModal = document.getElementById('confirmModal');
            
            if (event.target === memberModal) {
                memberModal.classList.remove('show');
            }
            if (event.target === pembinaModal) {
                pembinaModal.classList.remove('show');
            }
            if (event.target === pengurusModal) {
                pengurusModal.classList.remove('show');
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
