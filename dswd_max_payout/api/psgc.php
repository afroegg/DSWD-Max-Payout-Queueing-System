<?php
include('../config/db.php');
header('Content-Type: application/json');

$conn->query("CREATE TABLE IF NOT EXISTS psgc_locations (
    code VARCHAR(20) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    level_type ENUM('region','province','city','barangay') NOT NULL,
    parent_code VARCHAR(20) DEFAULT NULL,
    region_code VARCHAR(20) DEFAULT NULL,
    province_code VARCHAR(20) DEFAULT NULL,
    city_code VARCHAR(20) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_level_parent (level_type, parent_code),
    INDEX idx_region (region_code),
    INDEX idx_province (province_code),
    INDEX idx_city (city_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function out($success, $items = [], $source = 'local', $message = '') {
    echo json_encode(['success'=>$success, 'items'=>$items, 'source'=>$source, 'message'=>$message]);
    exit;
}

function localRows($conn, $level, $parentCode = '') {
    if ($level === 'region') {
        $stmt = $conn->prepare("SELECT code,name FROM psgc_locations WHERE level_type='region' ORDER BY name ASC");
    } else {
        $stmt = $conn->prepare("SELECT code,name FROM psgc_locations WHERE level_type=? AND parent_code=? ORDER BY name ASC");
        $stmt->bind_param('ss', $level, $parentCode);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) $items[] = ['code'=>$r['code'], 'name'=>$r['name']];
    return $items;
}

function remoteFetch($url) {
    $ctx = stream_context_create(['http'=>['timeout'=>12]]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function saveRows($conn, $level, $items, $parentCode = '', $regionCode = '', $provinceCode = '', $cityCode = '') {
    if (!$items) return;
    $stmt = $conn->prepare("INSERT INTO psgc_locations (code,name,level_type,parent_code,region_code,province_code,city_code) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), level_type=VALUES(level_type), parent_code=VALUES(parent_code), region_code=VALUES(region_code), province_code=VALUES(province_code), city_code=VALUES(city_code)");
    foreach ($items as $item) {
        $code = $item['code'] ?? '';
        $name = $item['name'] ?? '';
        if ($code === '' || $name === '') continue;
        $pc = $parentCode ?: null;
        $rc = $regionCode ?: null;
        $prc = $provinceCode ?: null;
        $cc = $cityCode ?: null;
        $stmt->bind_param('sssssss', $code, $name, $level, $pc, $rc, $prc, $cc);
        $stmt->execute();
    }
}

$type = $_GET['type'] ?? 'regions';
$regionCode = trim($_GET['region_code'] ?? '');
$provinceCode = trim($_GET['province_code'] ?? '');
$cityCode = trim($_GET['city_code'] ?? '');

$base = 'https://psgc.gitlab.io/api';

if ($type === 'regions') {
    $local = localRows($conn, 'region');
    if (count($local) > 0) out(true, $local, 'local');
    $remote = remoteFetch($base . '/regions/');
    if ($remote) { saveRows($conn, 'region', $remote); out(true, localRows($conn, 'region'), 'remote_cached'); }
    out(false, [], 'none', 'Offline PSGC region data is not loaded yet.');
}

if ($type === 'provinces') {
    if ($regionCode === '') out(false, [], 'none', 'Missing region_code.');
    $local = localRows($conn, 'province', $regionCode);
    if (count($local) > 0) out(true, $local, 'local');
    $remote = remoteFetch($base . '/regions/' . rawurlencode($regionCode) . '/provinces/');
    if ($remote) { saveRows($conn, 'province', $remote, $regionCode, $regionCode); out(true, localRows($conn, 'province', $regionCode), 'remote_cached'); }
    out(false, [], 'none', 'Offline PSGC province data is not loaded yet.');
}

if ($type === 'cities') {
    if ($provinceCode !== '') {
        $local = localRows($conn, 'city', $provinceCode);
        if (count($local) > 0) out(true, $local, 'local');
        $remote = remoteFetch($base . '/provinces/' . rawurlencode($provinceCode) . '/cities-municipalities/');
        if ($remote) { saveRows($conn, 'city', $remote, $provinceCode, $regionCode, $provinceCode); out(true, localRows($conn, 'city', $provinceCode), 'remote_cached'); }
        out(false, [], 'none', 'Offline PSGC city data is not loaded yet.');
    }
    if ($regionCode !== '') {
        $parent = 'REGION_' . $regionCode;
        $local = localRows($conn, 'city', $parent);
        if (count($local) > 0) out(true, $local, 'local');
        $remote = remoteFetch($base . '/regions/' . rawurlencode($regionCode) . '/cities-municipalities/');
        if ($remote) { saveRows($conn, 'city', $remote, $parent, $regionCode); out(true, localRows($conn, 'city', $parent), 'remote_cached'); }
    }
    out(false, [], 'none', 'Missing province_code or region_code.');
}

if ($type === 'barangays') {
    if ($cityCode === '') out(false, [], 'none', 'Missing city_code.');
    $local = localRows($conn, 'barangay', $cityCode);
    if (count($local) > 0) out(true, $local, 'local');
    $remote = remoteFetch($base . '/cities-municipalities/' . rawurlencode($cityCode) . '/barangays/');
    if ($remote) { saveRows($conn, 'barangay', $remote, $cityCode, $regionCode, $provinceCode, $cityCode); out(true, localRows($conn, 'barangay', $cityCode), 'remote_cached'); }
    out(false, [], 'none', 'Offline PSGC barangay data is not loaded yet.');
}

out(false, [], 'none', 'Invalid PSGC request type.');
?>
