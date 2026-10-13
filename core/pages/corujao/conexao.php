<?php

$servidor   = 'localhost';
$usuario    = 'root';
$senha      = 'fantomas';
$bancoDados = 'quiz';

try {
  $db = new PDO(
    "mysql:host=$servidor;dbname=$bancoDados;charset=utf8mb4",
    $usuario,
    $senha
  );
} catch (PDOException $erro) {
  error_log('[conexao.php] Erro: ' . $erro->getMessage());
  http_response_code(500);
  exit;
}
