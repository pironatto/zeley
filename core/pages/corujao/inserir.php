<?php

/**
 * inserir.php
 *
 * Cria um novo usuário no banco.
 *
 * Recebe (POST):
 *   - id   (string)  ID do usuário (UUID do Unity Auth)
 *   - nome (string)  Nome escolhido (máx 50 caracteres)
 *
 * Retorna:
 *   - "00000"      → cadastro feito com sucesso
 *   - "duplicado"  → nome já existe
 *   - "erro"       → qualquer outro erro
 *
 * Observação: o cliente Unity (Cadastro.cs) considera
 * sucesso apenas quando a resposta contém "00000".
 */

include_once('conexao.php');

header('Content-Type: text/plain; charset=utf-8');

/* ------------------------------------------------------------
 * 1) Só aceita POST
 * ------------------------------------------------------------ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'erro';
    exit;
}

/* ------------------------------------------------------------
 * 2) Valida o input
 * ------------------------------------------------------------ */

$id   = isset($_POST['id'])   ? trim($_POST['id'])   : '';
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';

if ($id === '' || $nome === '') {
    echo 'erro';
    exit;
}

if (mb_strlen($nome) > 50) {
    echo 'erro';
    exit;
}

/* ------------------------------------------------------------
 * 3) INSERT com bind + tratamento de erro
 * ------------------------------------------------------------ */

try {
    $sql = $db->prepare(
        "INSERT INTO usuarios (id, nome, pontos)
         VALUES (:id, :nome, 0)"
    );
    $sql->bindParam(':id',   $id,   PDO::PARAM_STR);
    $sql->bindParam(':nome', $nome, PDO::PARAM_STR);
    $sql->execute();

    echo '00000';
} catch (PDOException $e) {
    // Código 23000 = violação de constraint única
    // (nesse caso, o nome já existe)
    if ($e->getCode() === '23000') {
        echo 'duplicado';
        exit;
    }

    // Qualquer outro erro: loga e devolve erro genérico
    error_log(
        '[inserir.php] PDOException: ' . $e->getMessage()
    );

    http_response_code(500);
    echo 'erro';
    exit;
}
