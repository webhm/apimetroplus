<?php


/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 * @author Brayan Narváez <prinick@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

# Cargadores iniciales
require ___ROOT___ . 'Ocrend/vendor/autoload.php';
require ___ROOT___ . 'Ocrend/autoload.php';

# Manejador de excepciones
ErrorHandler::register();
ExceptionHandler::register();

# Mínima versión, alerta
if (version_compare(phpversion(), '7.0.0', '<')) {
    throw new \RuntimeException('La versión actual de PHP es ' . phpversion() . ' y como mínimo se require la versión 7.0.0');
}


# Obtener la data informativa
$config = Yaml::parse(file_get_contents(___ROOT___ . 'Ocrend/Kernel/Config/Ocrend.ini.yml'));


# Peticiones HTTP
$http = Request::createFromGlobals();

# Define el timezone actual
date_default_timezone_set($config['build']['timezone']);