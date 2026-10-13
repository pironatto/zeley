<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronometro</title>
    <script>
    "use strict"

var hh = 0;
var mm = 0;
var ss = 0;

var tempo = 1000;//Quantos milésimos valem 1 segundo?
var cron;

//Inicia o temporizador
function start() {
    cron = setInterval(() => { timer(); }, tempo);
}

//Para o temporizador mas não limpa as variáveis
function pause() {
    clearInterval(cron);
}

//Para o temporizador e limpa as variáveis
function stop() {
    clearInterval(cron);
    hh = 0;
    mm = 0;
    ss = 0;

    document.getElementById('counter').innerText = '00:00:00';
}

//Faz a contagem do tempo e exibição
function timer() {
    ss++; //Incrementa +1 na variável ss

    if (ss == 60) { //Verifica se deu 59 segundos
        ss = 0; //Volta os segundos para 0
        mm++; //Adiciona +1 na variável mm

        if (mm == 60) { //Verifica se deu 59 minutos
            mm = 0;//Volta os minutos para 0
            hh++;//Adiciona +1 na variável hora
        }
    }

    //Cria uma variável com o valor tratado HH:MM:SS
    var format = (hh < 10 ? '0' + hh : hh) + ':' + (mm < 10 ? '0' + mm : mm) + ':' + (ss < 10 ? '0' + ss : ss);
    
    //Insere o valor tratado no elemento counter
    document.getElementById('counter').innerText = format;

    //Retorna o valor tratado
    return format;
}
        
    </script>
    
</head>

<style>
    * {
        margin: 0 auto;
        padding: 0;
    }

    body {
        background-color: #111;
    }

    .max-width {
        max-width: 500px;
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
        margin-top: 10%;
        background-color: #FFF;
        box-shadow: 0 0 5px #FFF;
        text-align: center;
    }

    h1 {
        font-family: 'Courier New', Courier, monospace;
        margin-top: 20px;
    }

    button {
        padding: 10px;
        border: none;
        outline: none;
        font-family: 'Segoe UI';
        font-size: 1.1em;
        transition: all .5s;
        cursor: pointer;
        border-radius: 25px;
    }

    button:hover {
        background-color: #a1c0d1;
    }

    button:active {
        background-color: #111;
    }

    .bg-green {
        background-color: #b2d1a1;
    }

    .bg-yellow {
        background-color: #d1cba1;
    }

    .bg-red {
        background-color: #d1a1a1;
    }
</style>

<body>

    <div class="max-width">
        <button onclick="start();" class="bg-green">Iniciar</button>
        <button onclick="pause();" class="bg-yellow">Pausar</button>
        <button onclick="stop();" class="bg-red">Parar</button>
        <h1 id="counter">00:00:00</h1>
    </div>
<script src="cron.js"></script>
</body>

</html>