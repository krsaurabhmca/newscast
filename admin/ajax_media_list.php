<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 24;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? clean($_GET['search']) : '';

try {
    $where_sql = "1=1";
    $params = [];
    
    if (!empty($search)) {
        $where_sql .= " AND original_name LIKE ?";
        $params[] = "%$search%";
    }
    
    // Count total
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM media_library WHERE $where_sql");
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // Fetch items
    $sql = "SELECT * FROM media_library WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters carefully because of LIMIT/OFFSET
    $param_idx = 1;
    if (!empty($search)) {
        $stmt->bindValue($param_idx++, "%$search%", PDO::PARAM_STR);
    }
    $stmt->bindValue($param_idx++, $per_page, PDO::PARAM_INT);
    $stmt->bindValue($param_idx, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $items,
        'pagination' => [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
