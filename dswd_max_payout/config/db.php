<?php

$conn = new mysqli(
    "metro.proxy.rlwy.net",
    "root",
    "UMiFmmqBGdmWSduxuNrmXuEYMifHcyqu",
    "railway",
    19599
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
