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
use Ocrend\Kernel\Models\ModelsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Convertir esta api en RESTFULL para recibir JSON
 */
$app->before(function () use ($app) {
    try {
        global $config, $http;

        # Verificar si la api no está activa
        if (!$config['api']['active']) {
            throw new ModelsException('Servicio inactivo', 4070);
        }

        if ($http->getMethod() == 'OPTIONS') {
            throw new ModelsException('OPTIONS', 4090);
        }

        # Recibir JSON
        if (0 === strpos($http->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($http->getContent(), true);
            $http->request->replace(is_array($data) ? $data : array());
        }

    } catch (ModelsException $e) {

        if ($e->getMessage() == 'OPTIONS') {

            return new Response('', 200);

        } else {

            # Capturar error de token caducado
            if ($e->getCode() == 4031) {

                return $app->json(array(
                    'status' => false,
                    'message' => $e->getMessage(),
                    'errorCode' => $e->getCode(),
                    # 'data'    => explode('/', $http->getPathInfo())[2],
                ), 401);

            } else {

                return $app->json(array(
                    'status' => false,
                    'message' => $e->getMessage(),
                    'errorCode' => $e->getCode(),
                    # 'data'    => explode('/', $http->getPathInfo())[2],
                ));

            }

        }

    }

});

/**
 * Servidores autorizados para consumir la api.
 */
$app->after(function (Request $request, Response $response) {

    global $http, $config;

    # Setear respuesta para responses 500
    $getPathInfo = explode('/', $http->getPathInfo())[1];

    if ($getPathInfo === 'auth' or $getPathInfo === 'login' or $getPathInfo === 'register') {
        $response->setStatusCode(200);
    }

    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    # $response->headers->clearCookie('PHPSESSID');

});

$app->options("{anything}", function () {
    return new \Symfony\Component\HttpFoundation\JsonResponse(null, 204);
})->assert("anything", ".*");

$app->error(function (\Exception $e, $code) use ($app) {
    # Capturar errores de la api
    return $app->json(array(
        'status' => false,
        'message' => $e->getMessage(),
        'errorCode' => $e->getCode(),
    ));
});

# solo enviar token no url para activar cuenta : listo
# total en obj:data : listo
# key unico para generar mas tokens : listo
# eliminar message manjear codes de error : listo end poitn auth
