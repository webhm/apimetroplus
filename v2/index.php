<?php
error_reporting(0);

/*
 * (c) Hospital Metropolitano <webhm@hmetro.med.ec>

 */

# Definir el path
define('___ROOT___', '../');

# Cargadores principales
require ___ROOT___ . 'Ocrend/Kernel/Config/Config.php';

/**
 * Lanza un error público
 *
 * @return void
 */
function ___catchApi()
{
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    header('Content-Type: application/json');
    echo json_encode(
        array(
            'status' => false,
            'message' => 'Ha ocurrido un problema interno',
        )
    );
}

/**
 * Carga la api
 *
 * @return void
 */
function ___loadApi()
{
    # Preparar la API
    $app = new Silex\Application();
    unset($app['exception_handler']);

    # Verbos HTTP
    require 'controllers/ini.app.php';
    require 'controllers/get.controllers.php';
    require 'controllers/post.controllers.php';
    require 'controllers/put.controllers.php';
    require 'controllers/delete.controllers.php';

    # Api Date v2
    require 'controllers/date.post.controllers.php';

    $app->run();
}

# Arrancar
if ($config['build']['production']) {
    try {
        ___loadApi();
    } catch (\Throwable $e) {
        ___catchApi();
    } catch (\Exception $e) {
        ___catchApi();
    }
} else {
    ___loadApi();
}
