<?php
declare(strict_types=1);
// Configuración local de XAMPP. Las claves nunca se envían al navegador.
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'bag';
const DB_USER = 'root';
const DB_PASSWORD = '';
$RAWG_API_KEY = getenv('RAWG_API_KEY') ?: '';
$OPENAI_API_KEY = getenv('OPENAI_API_KEY') ?: '';
$OPENAI_MODEL = getenv('OPENAI_MODEL') ?: 'gpt-5.6-terra';
$OPENAI_WEB_SEARCH = true;
if (is_file(__DIR__.'/config_local.php')) require __DIR__.'/config_local.php';
date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', '0');
