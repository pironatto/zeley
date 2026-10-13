<?php

/**
 * consulta.php
 *
 * Verifica se um usuário existe no banco.
 *
 * Recebe (POST):
 *   - id (string)  ID do usuário (UUID do Unity Auth)
 *
 * Retorna:
 *   - Se existe: o próprio ID (string crua, sem JSON)
 *   - Se não existe: string vazia
 *   - Se erro de input: string vazia
 *   - Se erro de banco: string vazia + HTTP 500
 *
 * Observação: o cliente Unity (Login.cs) compara o retorno
 * com o ID local. Se bater, o usuário já está cadastrado.
 */

include_once('conexao.php');

header('Content-Type: text/plain; charset=utf-8');

/* ------------------------------------------------------------
 * 1) Valida o input
 * ------------------------------------------------------------ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '';
    exit;
}

$id = isset($_POST['id']) ? trim($_POST['id']) : '';

if ($id === '') {
    // Sem ID → não existe. Cliente trata como "precisa cadastrar"
    echo '';
    exit;
}

/* ------------------------------------------------------------
 * 2) Consulta com bind real (protege contra SQL injection)
 * ------------------------------------------------------------ */

try {
    $sql = $db->prepare(
        "SELECT id FROM usuarios WHERE id = :id LIMIT 1"
    );
    $sql->bindParam(':id', $id, PDO::PARAM_STR);
    $sql->execute();

    $dados = $sql->fetch(PDO::FETCH_ASSOC);

    if ($dados && isset($dados['id'])) {
        echo $dados['id'];
    } else {
        echo '';
    }
} catch (PDOException $e) {
    // Loga o erro real no servidor, mas NÃO vaza pro cliente
    error_log(
        '[consulta.php] PDOException: ' . $e->getMessage()
    );

    http_response_code(500);
    echo '';
    exit;
}
