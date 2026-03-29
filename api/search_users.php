<?php
session_start();
require_once(__DIR__ . "/../assets/database/connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

if (strlen($keyword) < 2 || !$club_id) {
    echo json_encode([]);
    exit;
}

$keyword_like = '%' . $keyword . '%';

$sql = "SELECT id, full_name, email 
        FROM users 
        WHERE (full_name LIKE ? OR email LIKE ?)
        AND id NOT IN (
            SELECT user_id FROM members WHERE club_id = ?
        )
        LIMIT 20";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ssi", $keyword_like, $keyword_like, $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode($users);
    $stmt->close();
} else {
    echo json_encode(['error' => 'Database error']);
}

// Don't close connection - it's managed globally
?>