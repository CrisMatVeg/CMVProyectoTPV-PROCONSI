<?php
// Carga del Autoloader de Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}


/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */
define("HOST", "localhost");
define("DBNAME", "dbelectrobazar-tpv");
define("USERNAME", "root");
define("PASSWORD", "");
define("DSN", "mysql:host=" . HOST . ";dbname=" . DBNAME . ";charset=utf8mb4");
