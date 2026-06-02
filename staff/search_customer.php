<?php
header('Content-Type: application/json');
include 'conn.php';

$term = $_GET['term'] ?? '';
$customers = [];

if (strlen($term) > 0) {
    $sql = "SELECT id, nama FROM customer WHERE nama LIKE ? AND deleted_at IS NULL LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $term . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $customers[] = [
            'id' => $row['id'],
            'nama' => $row['nama']
        ];
    }
    $stmt->close();
}

echo json_encode($customers);
?>