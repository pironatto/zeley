<?php

namespace core;

class route
{
    function __construct()
    {
        $this->Run();
    }

    function Run()
    {
        //VERIFICAR SE A URL ESTÁ PREENCHIDA
        if (!empty($_GET['pag'])) {
            $url = $_GET['pag'];
        }

        //SEPARAR A URL EM CONTROLLER, ACTION E ID
        if (!empty($url)) {
            $url = explode('/', $url);
            $controller = $url[0];
            if (!empty($url[1])) {
                $action = $url[1];
            } else {
                $action = 'index';
            }
            if (!empty($url[2])) {
                $id = $url[2];
            } else {
                $id = null;
            }
        }

        if (empty($controller)) {
            $controller = 'home';
        }
        if (empty($action)) {
            $action = 'index';
        }

        // Caminho absoluto baseado na raiz do projeto (/var/www/zeley)
        $rootPath = dirname(__DIR__);
        $pagina = "$rootPath/core/pages/$controller/$action.php";

        //INSTANCIA A PAGINA
        if (!is_file($pagina)) {
            include("$rootPath/core/pages/erro.php");
        } else {
            include($pagina);
        }
    }
}