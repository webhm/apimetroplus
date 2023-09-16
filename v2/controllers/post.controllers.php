<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use app\models\pasaportes as ModelPasaportes;
use app\models\usuarios as ModelUsuarios;

# Autorización
$app->post('/auth', function () use ($app) {
    $u = new ModelUsuarios\Login;
    return $app->json($u->login());
});

# Pasaportes
$app->post('/pacientes/step-pasaporte', function () use ($app) {
    $m = new ModelPasaportes\Pasaportes;
    return $app->json($m->stepPasaporte());
});

$app->post('/pacientes/nuevo-pasaporte', function () use ($app) {
    $m = new ModelPasaportes\Pasaportes;
    return $app->json($m->nuevoPasaporte());
});

$app->post('/pacientes/nueva-asignacion', function () use ($app) {
    $m = new ModelPasaportes\Pasaportes;
    return $app->json($m->nuevaAsignacion());
});