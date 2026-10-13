<?php
error_log("Index.php executado com pag=" . ($_GET['pag'] ?? 'home'));

use core\classes\Database;
use core\classes\Functions;
use core\route;

session_start();

require_once('../vendor/autoload.php');

// Layout padrão
require_once '../includes/header.php';

$r = new route(); // aqui o roteador carrega a página certa

require_once '../includes/footer.php';
