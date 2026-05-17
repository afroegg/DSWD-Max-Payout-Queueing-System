<?php
include('../config/db.php');

header('Content-Type: text/html; charset=utf-8');
set_time_limit(600);
ini_set('memory_limit', '512M');

echo '<pre style="font-family:Consolas,monospace;font-size:14px;line-height:1.45">';

function logLine($text) {
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "\n";
    @ob_flush();
    @flush();
}

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

function fetchJson($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 60,
            'header' => "User-Agent: DSWD-Queueing-System-PSGC-Seeder\r\n"
        ]
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function val($item, $keys, $default = '') {
    foreach ($keys as $key) {
        if (isset($item[$key]) && trim((string)$item[$key]) !== '') return trim((string)$item[$key]);
    }
    return $default;
}

function saveLocation($conn, $code, $name, $level, $parent, $region, $province, $city) {
    static $stmt = null;
    if ($stmt === null) {
        $stmt = $conn->prepare("INSERT INTO psgc_locations (code,name,level_type,parent_code,region_code,province_code,city_code) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), level_type=VALUES(level_type), parent_code=VALUES(parent_code), region_code=VALUES(region_code), province_code=VALUES(province_code), city_code=VALUES(city_code)");
    }
    $parent = $parent !== '' ? $parent : null;
    $region = $region !== '' ? $region : null;
    $province = $province !== '' ? $province : null;
    $city = $city !== '' ? $city : null;
    $stmt->bind_param('sssssss', $code, $name, $level, $parent, $region, $province, $city);
    $stmt->execute();
}

function codePrefixRegion($code) {
    return substr((string)$code, 0, 2) . '0000000';
}

function codePrefixProvince($code) {
    return substr((string)$code, 0, 5) . '0000';
}

$base = 'https://psgc.gitlab.io/api';

logLine('Starting full PSGC offline seeding...');
logLine('This downloads regions, provinces, cities/municipalities, and barangays into MySQL.');
logLine('');

$regions = fetchJson($base . '/regions/');
if (!$regions) die('Failed to fetch regions. Make sure you have internet while running this seeder.');
logLine('Regions fetched: ' . count($regions));
foreach ($regions as $r) {
    $code = val($r, ['code']);
    $name = val($r, ['name']);
    if ($code && $name) saveLocation($conn, $code, $name, 'region', '', $code, '', '');
}

$provinces = fetchJson($base . '/provinces/');
if (!$provinces) die('Failed to fetch provinces.');
logLine('Provinces fetched: ' . count($provinces));
foreach ($provinces as $p) {
    $code = val($p, ['code']);
    $name = val($p, ['name']);
    $region = val($p, ['regionCode', 'region_code'], codePrefixRegion($code));
    if ($code && $name) saveLocation($conn, $code, $name, 'province', $region, $region, $code, '');
}

$cities = fetchJson($base . '/cities-municipalities/');
if (!$cities) die('Failed to fetch cities/municipalities.');
logLine('Cities/Municipalities fetched: ' . count($cities));
foreach ($cities as $c) {
    $code = val($c, ['code']);
    $name = val($c, ['name']);
    $region = val($c, ['regionCode', 'region_code'], codePrefixRegion($code));
    $province = val($c, ['provinceCode', 'province_code'], '');
    $parent = $province !== '' ? $province : ('REGION_' . $region);
    if ($code && $name) saveLocation($conn, $code, $name, 'city', $parent, $region, $province, $code);
}

$barangays = fetchJson($base . '/barangays/');
if (!$barangays) die('Failed to fetch barangays.');
logLine('Barangays fetched: ' . count($barangays));
$count = 0;
foreach ($barangays as $b) {
    $code = val($b, ['code']);
    $name = val($b, ['name']);
    $region = val($b, ['regionCode', 'region_code'], codePrefixRegion($code));
    $province = val($b, ['provinceCode', 'province_code'], '');
    $city = val($b, ['cityCode', 'municipalityCode', 'city_code', 'municipality_code'], '');
    if ($code && $name && $city) {
        saveLocation($conn, $code, $name, 'barangay', $city, $region, $province, $city);
        $count++;
    }
}

logLine('Barangays saved: ' . $count);
logLine('');
logLine('DONE. Your psgc_locations table is now ready for offline dropdowns.');
logLine('For XAMPP/offline transfer: export only the psgc_locations table from phpMyAdmin, then import it into your local XAMPP database.');

echo '</pre>';
?>
