<?php

include_once('conexao.php');

$materia = $_POST['materia'];


$cmd = $db->prepare("SELECT * from questoes WHERE materia = :materia ORDER BY RAND() limit 1");
$cmd->bindParam(":materia", $materia);
$cmd->execute();
$resultado = $cmd->fetch(PDO::FETCH_ASSOC);

foreach ($resultado as $key => $value) {

    if (isset($value)) {
        echo ($value . "-");
    }
}
