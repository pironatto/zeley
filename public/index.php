<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

// 1. Carrega o header (abre HTML, HEAD, BODY e NAVBAR)
require_once __DIR__ . '/../includes/header.php';

// 2. O roteador puxa o conteúdo da página solicitada (ex: home/index.php)
$r = new \core\route(); 

// 3. Carrega o footer (fecha MAIN, FOOTER, BODY e HTML)
require_once __DIR__ . '/../includes/footer.php';