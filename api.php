<?php
require_once __DIR__ . '/config.php';
setCorsHeaders();

$db     = getDB();
$action = trim($_GET['action'] ?? 'list');

if ($action === 'stats') {
    $total = (int) $db->query("SELECT COUNT(*) AS n FROM gereja")->fetch_assoc()['n'];
    $rKel  = $db->query("SELECT kelurahan, COUNT(*) AS total FROM gereja GROUP BY kelurahan ORDER BY kelurahan");
    $rDen  = $db->query("SELECT denominasi, COUNT(*) AS total FROM gereja GROUP BY denominasi ORDER BY total DESC");
    echo json_encode([
        'status'         => 'ok',
        'total'          => $total,
        'per_kelurahan'  => $rKel->fetch_all(MYSQLI_ASSOC),
        'per_denominasi' => $rDen->fetch_all(MYSQLI_ASSOC),
    ]);
    $db->close(); exit;
}

if ($action === 'kelurahan_list') {
    $r = $db->query("SELECT DISTINCT kelurahan FROM gereja ORDER BY kelurahan");
    echo json_encode([
        'status' => 'ok',
        'data'   => array_column($r->fetch_all(MYSQLI_ASSOC), 'kelurahan'),
    ]);
    $db->close(); exit;
}

$where = []; $params = []; $types = '';

if (!empty($_GET['kelurahan'])) { $where[] = 'kelurahan = ?';    $params[] = trim($_GET['kelurahan']); $types .= 's'; }
if (!empty($_GET['denominasi'])) { $where[] = 'denominasi = ?'; $params[] = trim($_GET['denominasi']); $types .= 's'; }
if (!empty($_GET['q']))          { $where[] = 'nama_gereja LIKE ?'; $params[] = '%'.trim($_GET['q']).'%'; $types .= 's'; }

$sql = "SELECT kode,nama_gereja,denominasi,kelurahan,kecamatan,kota,provinsi,latitude,longitude,alamat,telepon FROM gereja";
if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
$sql .= ' ORDER BY kelurahan, nama_gereja';

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($rows as &$r) {
    $r['kode']      = (int)   $r['kode'];
    $r['latitude']  = (float) $r['latitude'];
    $r['longitude'] = (float) $r['longitude'];
}

echo json_encode(['status'=>'ok','total'=>count($rows),'data'=>$rows]);
$stmt->close();
$db->close();
