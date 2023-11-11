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

$app->get('/', function () use ($app) {
    return $app->json();
});

# Usuarios
$app->get('/mis-pacientes', function () use ($app) {
    $m = new ModelUsuarios\Pacientes;
    return $app->json($m->getMisPacientes());
});

# Usuarios
$app->get('/usuarios/metroplus', function () use ($app) {
    $m = new ModelUsuarios\ActiveDirectoryHM;
    return $app->json($m->getGruposAD());
});

# Pasaportes
$app->get('/pacientes/pasaportes', function () use ($app) {
    $m = new ModelPasaportes\Pasaportes;
    return $app->json($m->verPasaporte());
});

$app->get('/pacientes/historial-asignaciones', function () use ($app) {
    $m = new ModelPasaportes\Pasaportes;
    return $app->json($m->historialAsignaciones());
});
