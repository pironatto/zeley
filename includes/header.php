<?php
$uri = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Zeley Studios</title>
    <link rel="stylesheet" href="/zeley/public/css/site.css">
</head>

<?php
// Página principal
if ($uri === '/zeley/' || $uri === '/zeley/index.php') {
?>
    <header>
        <div class="navbar">
            <a href="/zeley"><button type="button" class="btn btn-home">HOME</button></a>
            <a href="/zeley/corujao"><button type="button" class="btn btn-blue">CORUJÃO</button></a>
            <a href="/zeley/rpg"><button type="button" class="btn btn-yellow">RPG</button></a>
        </div>
    </header>
<?php
} else {
?>
    <header>
        <!-- Apenas botão HOME fixo, mas igual ao da navbar -->
        <a href="/zeley"><button type="button" class="btn btn-home btn-home-fixed">HOME</button></a>
    </header>
<?php
}
?>