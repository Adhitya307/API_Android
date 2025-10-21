<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('rembesan/check-connection', 'Rembesan\CheckConnection::index');

$routes->post('rembesan/input', 'Rembesan\InputRembesan::index');

$routes->get('rembesan/get_pengukuran', 'Rembesan\GetPengukuran::index');

$routes->get('rembesan/cek-data', 'Rembesan\CekDataController::index');

// Routes untuk hitung Thomson
$routes->get('rembesan/hitungthomson/hitungSemua', 'Rembesan\HitungThomson::hitungSemua');
$routes->get('rembesan/hitungthomson/cekStatus', 'Rembesan\HitungThomson::cekStatus');

$routes->post('rembesan/Rumus-Rembesan', 'Rembesan\RumusRembesan::hitungSemua');

// === History API (lama, hanya id + tanggal) ===
$routes->group('api/rembesan', function($routes) {
    $routes->get('pengukuran', 'Rembesan\HistoryApi::pengukuran');
    $routes->get('detail/(:num)', 'Rembesan\HistoryApi::detail/$1');
});

// === Backup API (baru, field lengkap) ===
$routes->group('api/rembesan/backup', function($routes) {
    // Data Input
    $routes->get('pengukuran', 'Rembesan\BackupApi::pengukuran');
    $routes->get('thomson', 'Rembesan\BackupApi::thomson');
    $routes->get('sr', 'Rembesan\BackupApi::sr');
    $routes->get('bocoran', 'Rembesan\BackupApi::bocoran');
    
    // Data Hasil Perhitungan (P_)
    $routes->get('p_batasmaksimal', 'Rembesan\BackupApi::p_batasmaksimal');
    $routes->get('p_bocoran_baru', 'Rembesan\BackupApi::p_bocoran_baru');
    $routes->get('p_intigalery', 'Rembesan\BackupApi::p_intigalery');
    $routes->get('p_spillway', 'Rembesan\BackupApi::p_spillway');
    $routes->get('p_sr', 'Rembesan\BackupApi::p_sr');
    $routes->get('p_tebingkanan', 'Rembesan\BackupApi::p_tebingkanan');
    $routes->get('p_thomson_weir', 'Rembesan\BackupApi::p_thomson_weir');
    $routes->get('p_totalbocoran', 'Rembesan\BackupApi::p_totalbocoran');
});

$routes->group('rembesan/lookburt', ['namespace' => 'App\Controllers\Rembesan'], function($routes) {
    $routes->get('hitung/(:num)', 'AnalisaLookBurt::hitung/$1');
    $routes->get('hitung-semua', 'AnalisaLookBurt::hitungSemua');
    $routes->get('/', 'AnalisaLookBurt::index');
});

$routes->get('api/rembesan/analisa_look_burt', 'Rembesan\BackupApi::analisa_look_burt');

$routes->get('rembesan/hitung-thomson-semua', 'Rembesan\RumusRembesan::hitungThomsonSemua');