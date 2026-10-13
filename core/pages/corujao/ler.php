<?php
// Configurações e diretório
$caminhoFicheiro = 'perguntas';
$mensagem = '';
$tipoMensagem = ''; // 'success', 'danger', 'warning', 'info'

// Matérias pré-definidas no sistema
$materiasPadrao = ['historia', 'ciencias', 'geografia', 'matematica', 'fisica', 'biologia', 'medicina'];

// Função auxiliar para ler e estruturar o ficheiro de perguntas
function lerEEstruturarArquivo($caminho)
{
    if (!file_exists($caminho)) return [];

    // FILE_SKIP_EMPTY_LINES garante ignorar linhas em branco entre questões
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linhas = array_map('trim', $linhas);
    $linhas = array_values(array_filter($linhas)); // Reindexa o array

    $totalLinhas = count($linhas);
    $questoes = [];

    for ($i = 0; $i < $totalLinhas; $i += 6) {
        if (isset($linhas[$i + 5])) {
            // Remove numeração inicial ("1. ", "10. ", "100. ")
            $pergunta = trim(preg_replace('/^\d+\.\s*/', '', $linhas[$i]));

            // Remove prefixos das alternativas ("a) ", "b) ", "c) ", "d) ")
            $r1 = trim(preg_replace('/^[a-dA-D]\)\s*/', '', $linhas[$i + 1]));
            $r2 = trim(preg_replace('/^[a-dA-D]\)\s*/', '', $linhas[$i + 2]));
            $r3 = trim(preg_replace('/^[a-dA-D]\)\s*/', '', $linhas[$i + 3]));
            $r4 = trim(preg_replace('/^[a-dA-D]\)\s*/', '', $linhas[$i + 4]));

            // Extrai a letra da resposta após "Resposta: "
            $correta = strtoupper(trim(substr($linhas[$i + 5], 10, 1)));

            $questoes[] = [
                'pergunta' => $pergunta,
                'r1'       => $r1,
                'r2'       => $r2,
                'r3'       => $r3,
                'r4'       => $r4,
                'correta'  => $correta
            ];
        }
    }
    return $questoes;
}

// 1. CARREGAR FICHEIRO NO SERVIDOR
if (isset($_POST['selecionar'])) {
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

        if (in_array($extensao, ['txt', 'sql'])) {
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoFicheiro)) {
                $mensagem = "Ficheiro carregado com sucesso!";
                $tipoMensagem = "success";
            } else {
                $mensagem = "Erro ao guardar o ficheiro no servidor.";
                $tipoMensagem = "danger";
            }
        } else {
            $mensagem = "Por favor, selecione um ficheiro de texto válido (.txt).";
            $tipoMensagem = "warning";
        }
    } else {
        $mensagem = "Por favor, selecione um ficheiro para enviar.";
        $tipoMensagem = "warning";
    }
}

// 2. ELIMINAR FICHEIRO
if (isset($_POST['deletar'])) {
    if (file_exists($caminhoFicheiro) && unlink($caminhoFicheiro)) {
        $mensagem = "Ficheiro de perguntas eliminado com sucesso.";
        $tipoMensagem = "info";
    } else {
        $mensagem = "Não foi possível eliminar o ficheiro ou ele não existe.";
        $tipoMensagem = "warning";
    }
}

// 3. VOLTAR
if (isset($_POST['voltar'])) {
    header('Location: /corujao');
    exit();
}

// 4. INSERIR NA BASE DE DADOS (Processa a lista enviada da prévia/formulário)
if (isset($_POST['enviar_bd'])) {
    include_once('conexao.php');

    $materia = trim($_POST['materia_select'] === 'outra' ? $_POST['materia_nova'] : $_POST['materia_select']);
    $questoesPOST = $_POST['questoes'] ?? [];

    if (empty($materia)) {
        $mensagem = "Por favor, selecione ou introduza o nome da matéria.";
        $tipoMensagem = "warning";
    } elseif (empty($questoesPOST)) {
        $mensagem = "Nenhuma pergunta disponível para inserir.";
        $tipoMensagem = "warning";
    } else {
        try {
            $db->beginTransaction();

            // Prepara a consulta para verificação de duplicados
            $sqlCheck = $db->prepare("SELECT COUNT(*) FROM questoes WHERE materia = ? AND pergunta = ?");

            // Prepara a inserção
            $sqlInsert = $db->prepare("INSERT INTO questoes (materia, pergunta, r1, r2, r3, r4, correta) VALUES (?, ?, ?, ?, ?, ?, ?)");

            $inseridos = 0;
            $duplicados = 0;

            foreach ($questoesPOST as $q) {
                $pergunta = trim($q['pergunta']);
                $r1       = trim($q['r1']);
                $r2       = trim($q['r2']);
                $r3       = trim($q['r3']);
                $r4       = trim($q['r4']);
                $correta  = strtoupper(trim($q['correta']));

                // Validações básicas
                if (empty($pergunta) || !in_array($correta, ['A', 'B', 'C', 'D'])) {
                    continue; // Pula registos inválidos
                }

                // Verifica se já existe na base de dados
                $sqlCheck->execute([$materia, $pergunta]);
                if ($sqlCheck->fetchColumn() > 0) {
                    $duplicados++;
                    continue; // Evita inserir duplicado
                }

                // Insere no MySQL
                $sqlInsert->execute([$materia, $pergunta, $r1, $r2, $r3, $r4, $correta]);
                $inseridos++;
            }

            $db->commit();

            $mensagem = "Processamento concluído: <b>{$inseridos}</b> perguntas inseridas com sucesso na matéria <i>" . htmlspecialchars($materia) . "</i>.";
            if ($duplicados > 0) {
                $mensagem .= " (<b>{$duplicados}</b> perguntas ignoradas por já existirem no banco).";
            }
            $tipoMensagem = $inseridos > 0 ? "success" : "warning";
        } catch (Exception $e) {
            $db->rollBack();
            $mensagem = "Erro na base de dados: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }
}

// Carrega as questões caso o ficheiro exista
$questoesCarregadas = lerEEstruturarArquivo($caminhoFicheiro);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Perguntas - Corujão</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-4">
        <h2 class="mb-4">Importador de Questões (Quiz)</h2>

        <!-- Mensagens de Alerta -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário 1: Enviar/Eliminar Ficheiro -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header font-weight-bold">1. Ficheiro de Origem</div>
            <div class="card-body">
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-8">
                        <input class="form-control" type="file" name="arquivo" accept=".txt">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" name="selecionar" class="btn btn-primary">Carregar</button>
                        <?php if (file_exists($caminhoFicheiro)): ?>
                            <button type="submit" name="deletar" class="btn btn-outline-danger" onclick="return confirm('Tem certeza que deseja eliminar o ficheiro do servidor?');">Eliminar Ficheiro</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Formulário 2: Configuração e Edição/Prévia das Perguntas -->
        <?php if (!empty($questoesCarregadas)): ?>
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header font-weight-bold">2. Seleção de Matéria & Opções</div>
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Matéria:</label>
                            <select name="materia_select" id="materia_select" class="form-select" onchange="toggleMateriaNova(this.value)" required>
                                <option value="">-- Selecione uma Matéria --</option>
                                <?php foreach ($materiasPadrao as $m): ?>
                                    <option value="<?= $m ?>"><?= ucfirst($m) ?></option>
                                <?php endforeach; ?>
                                <option value="outra">+ Outra (Especificar)</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="div_materia_nova" style="display: none;">
                            <label class="form-label font-weight-bold">Nova Matéria:</label>
                            <input type="text" name="materia_nova" class="form-control" placeholder="Digite o nome da matéria">
                        </div>
                    </div>
                </div>

                <!-- Prévia Editável -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><b>3. Prévia e Edição das Perguntas Encontradas</b> (Total: <?= count($questoesCarregadas) ?>)</span>
                        <button type="submit" name="enviar_bd" class="btn btn-success fw-bold">Confirmar e Guardar no Banco</button>
                    </div>
                    <div class="card-body">
                        <?php foreach ($questoesCarregadas as $index => $q): ?>
                            <div class="border rounded p-3 mb-3 bg-white">
                                <div class="mb-2">
                                    <label class="form-label fw-bold">Pergunta <?= $index + 1 ?>:</label>
                                    <input type="text" name="questoes[<?= $index ?>][pergunta]" class="form-control" value="<?= htmlspecialchars($q['pergunta']) ?>" required>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <small class="text-muted">A)</small>
                                        <input type="text" name="questoes[<?= $index ?>][r1]" class="form-control form-control-sm" value="<?= htmlspecialchars($q['r1']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">B)</small>
                                        <input type="text" name="questoes[<?= $index ?>][r2]" class="form-control form-control-sm" value="<?= htmlspecialchars($q['r2']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">C)</small>
                                        <input type="text" name="questoes[<?= $index ?>][r3]" class="form-control form-control-sm" value="<?= htmlspecialchars($q['r3']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">D)</small>
                                        <input type="text" name="questoes[<?= $index ?>][r4]" class="form-control form-control-sm" value="<?= htmlspecialchars($q['r4']) ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label"><small class="fw-bold">Resposta Correta:</small></label>
                                        <select name="questoes[<?= $index ?>][correta]" class="form-select form-select-sm" required>
                                            <option value="A" <?= $q['correta'] === 'A' ? 'selected' : '' ?>>A</option>
                                            <option value="B" <?= $q['correta'] === 'B' ? 'selected' : '' ?>>B</option>
                                            <option value="C" <?= $q['correta'] === 'C' ? 'selected' : '' ?>>C</option>
                                            <option value="D" <?= $q['correta'] === 'D' ? 'selected' : '' ?>>D</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="voltar" class="btn btn-secondary">Voltar</button>
                            <button type="submit" name="enviar_bd" class="btn btn-success fw-bold">Confirmar e Guardar no Banco</button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Impede o reenvio de formulário ao atualizar a página
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Exibe o campo de texto se o utilizador selecionar "Outra" na matéria
        function toggleMateriaNova(val) {
            const divNova = document.getElementById('div_materia_nova');
            if (val === 'outra') {
                divNova.style.display = 'block';
                divNova.querySelector('input').setAttribute('required', 'required');
            } else {
                divNova.style.display = 'none';
                divNova.querySelector('input').removeAttribute('required');
            }
        }
    </script>

</body>

</html>