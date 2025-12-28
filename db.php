<?php
// Veritabanı Ayarları
$host = 'sql112.infinityfree.com';
$db   = 'if0_40557751_forum';
$user = 'if0_40557751';
$pass = '3eJqBCTM5TmZMe';
$charset = 'utf8mb4';

// DSN (Data Source Name) Tanımlaması
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO Seçenekleri
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hataları istisna olarak fırlat
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Verileri ilişkisel dizi olarak getir
    PDO::ATTR_EMULATE_PREPARES   => false,                // Gerçek prepare statement kullan
];

try {
    // Bağlantıyı oluştur
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Bağlantı hatası durumunda işlemi durdur ve hatayı göster
    // Not: Canlı ortamda detaylı hata mesajı güvenlik riski oluşturabilir, loglanmalıdır.
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>