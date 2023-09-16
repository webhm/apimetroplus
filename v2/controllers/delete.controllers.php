<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use app\models\medicos as TModel;

// Proceso Terapia Respiratoria
$app->delete('/notificaciones/register', function () use ($app) {
    $u = new TModel\Notificaciones;
    return $app->json($u->deleteTokenNotificacion());
});
