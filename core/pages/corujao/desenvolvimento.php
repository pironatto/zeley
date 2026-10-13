<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="padding-top: 80px; background-color:black; color:white">

    <?php
    // Palavra secreta definida
    $senha_correta = "fantomas";

    // Verifica se o formulário foi enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $senha = $_POST["senha"];

        if ($senha === $senha_correta) {
            // Redireciona para a página desejada
            header("Location: /zeley/corujao/ler");
            exit;
        } else {
            // Mensagem de erro + botão de voltar
            echo '<p>Senha incorreta!</p>';
            echo '<button onclick="window.history.back()">Voltar</button>';
        }
    } else {
        // Formulário para digitar a senha
        echo '<form method="post">
            Digite a senha: 
            <input type="password" name="senha" size="10">
            <input type="submit" value="Entrar">
          </form>';
    }
    ?>

</body>

</html>