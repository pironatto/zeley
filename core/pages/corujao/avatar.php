<?php

/**
 * avatar.php
 *
 * Endpoint pra upload de foto de perfil do Corujão.
 *
 * Recebe:
 *   POST multipart/form-data
 *     - id    (string)  ID do usuário
 *     - foto  (file)    Arquivo da imagem
 *
 * Retorna JSON:
 *   { "sucesso": true, "avatarUrl": "https://..." }
 *   { "sucesso": false, "erro": "mensagem" }
 */

include_once('conexao.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit;
}

/* =============================================================
 * 1) Lê e valida os campos obrigatórios
 * ============================================================= */

$usuarioId = trim($_POST['id'] ?? '');

if (empty($usuarioId)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'ID do usuário não informado']);
    exit;
}

if (!isset($_FILES['foto'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Nenhuma foto enviada']);
    exit;
}

$foto = $_FILES['foto'];

/* =============================================================
 * 2) Valida o upload
 * ============================================================= */

if ($foto['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no upload: código ' . $foto['error']]);
    exit;
}

// Limite de 2 MB
$tamanhoMaximo = 2 * 1024 * 1024;
if ($foto['size'] > $tamanhoMaximo) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Foto muito grande (máx 2 MB)']);
    exit;
}

// Confere se é uma imagem real (não confia na extensão)
$infoImagem = @getimagesize($foto['tmp_name']);

if ($infoImagem === false) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Arquivo não é uma imagem válida']);
    exit;
}

$tipoMime = $infoImagem['mime'];

if (!in_array($tipoMime, ['image/jpeg', 'image/png', 'image/webp'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Formato não suportado (use JPG, PNG ou WebP)']);
    exit;
}

/* =============================================================
 * 3) Verifica se o usuário existe
 * ============================================================= */

try {
    $sql = $db->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
    $sql->bindParam(':id', $usuarioId, PDO::PARAM_STR);
    $sql->execute();

    if ($sql->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'erro' => 'Usuário não encontrado']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no banco: ' . $e->getMessage()]);
    exit;
}

/* =============================================================
 * 4) Redimensiona pra 256×256 e salva
 * ============================================================= */

$pastaAvatares = __DIR__ . '/avatars/';
$nomeArquivo   = $usuarioId . '.jpg';
$caminhoFinal  = $pastaAvatares . $nomeArquivo;

try {
    // Abre a imagem original
    switch ($tipoMime) {
        case 'image/jpeg':
            $imagemOriginal = imagecreatefromjpeg($foto['tmp_name']);
            break;
        case 'image/png':
            $imagemOriginal = imagecreatefrompng($foto['tmp_name']);
            break;
        case 'image/webp':
            $imagemOriginal = imagecreatefromwebp($foto['tmp_name']);
            break;
        default:
            $imagemOriginal = false;
    }

    if ($imagemOriginal === false) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'Falha ao processar imagem']);
        exit;
    }

    // Cria um quadrado 256×256
    $tamanho = 256;
    $imagemFinal = imagecreatetruecolor($tamanho, $tamanho);

    // Pega as dimensões da imagem original
    $larguraOriginal = imagesx($imagemOriginal);
    $alturaOriginal  = imagesy($imagemOriginal);

    // Faz crop centralizado (recorta o quadrado do meio)
    $lado = min($larguraOriginal, $alturaOriginal);
    $x = (int) (($larguraOriginal - $lado) / 2);
    $y = (int) (($alturaOriginal - $lado) / 2);

    // Redimensiona pra 256×256
    imagecopyresampled(
        $imagemFinal,
        $imagemOriginal,
        0, 0,           // dest
        $x, $y,         // src
        $tamanho, $tamanho,
        $lado, $lado
    );

    // Salva como JPG (qualidade 90)
    $salvou = imagejpeg($imagemFinal, $caminhoFinal, 90);

    // Libera memória
    imagedestroy($imagemOriginal);
    imagedestroy($imagemFinal);

    if (!$salvou) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar a imagem']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao processar: ' . $e->getMessage()]);
    exit;
}

/* =============================================================
 * 5) Atualiza o avatar_url no banco
 * ============================================================= */

$urlPublica = 'https://zeleystudios.online/corujao/avatars/' . $nomeArquivo;

try {
    $sql = $db->prepare("
        UPDATE usuarios
           SET avatar_url = :url
         WHERE id = :id
    ");
    $sql->bindParam(':url', $urlPublica, PDO::PARAM_STR);
    $sql->bindParam(':id', $usuarioId, PDO::PARAM_STR);
    $sql->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar banco: ' . $e->getMessage()]);
    exit;
}

/* =============================================================
 * 6) Retorna sucesso
 * ============================================================= */

echo json_encode([
    'sucesso'   => true,
    'avatarUrl' => $urlPublica
]);
