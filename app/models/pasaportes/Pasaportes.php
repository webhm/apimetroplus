<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\models\pasaportes;

use app\models\pasaportes as Model;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo Pasaportes
 */
class Pasaportes extends Models implements IModels
{

    use DBModel;

    public function stepPasaporte()
    {
        try {

            global $config, $http;

            # Obtener los datos $_POST
            $req = $http->request->all();

            $nhc = $req['NHC'];
            $query = $this->db->select('*', 'hm_pasaportes', null, "nhc='$nhc'", 1);

            if ($query == false) {

                $data = array(
                    'pte' => $req,
                    'preferencias' => null,
                );

                $id_data = $this->db->insert(
                    'hm_pasaportes',
                    array(
                        'status' => 0,
                        'nhc' => $nhc,
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    )
                );

                return array(
                    'status' => true,
                    'data' => $data,
                    'message' => 'Proceso realizado con éxito.',
                );

            } else {

                $user_data = json_decode($query['data'], true);

                $data = array(
                    'pte' => $req,
                    'preferencias' => $user_data['preferencias'],
                );

                $update = $this->db->update('hm_pasaportes', array(
                    'status' => 0,
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                ), "nhc='" . $nhc . "'", 1);

                return array(
                    'status' => true,
                    'data' => $req,
                    'message' => 'Proceso realizado con éxito.',
                );
            }

        } catch (ModelsException $e) {

            $error = array(
                'status' => false,
                'data' => [],
                'message' => $e->getMessage(),
            );

            return $error;

        }
    }

    public function generarPasaporte(array $data)
    {

        $_datos = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://prod-103.westus.logic.azure.com:443/workflows/f1682f3b3c9140b5b6e33fccd316ecc6/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=G4AHmFvBgJgWHhU9irnWi6244-1FnVN-vDMdB_PLKEE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_datos);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-Type: application/json',
            )
        );

        $response = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code == 200) {
            # return $response;
            // return true;
        } else {

            //   return false;
        }

        # return $response;

        curl_close($ch);

    }

    public function nuevoPasaporte()
    {
        try {

            global $config, $http;

            # Obtener los datos $_POST
            $req = $http->request->all();
            $encuesta = $req['encuesta'];
            $pte = $req['pte'];
            $nhc = $pte['NHC'];


            $_encuesta['rq1'] = $req['encuesta'][0][1];
            $_encuesta['rq2'] = $req['encuesta'][1][1];
            $_encuesta['rq3'] = $req['encuesta'][2][1];
            $_encuesta['rq4'] = $req['encuesta'][3][1];
            $_encuesta['rq5'] = $req['encuesta'][4][1];
            $_encuesta['rq6'] = $req['encuesta'][5][1];
            $_encuesta['rq7'] = $req['encuesta'][6][1];
            $_encuesta['rq8'] = $req['encuesta'][7][1];
            $_encuesta['rq9'] = $req['encuesta'][8][1];
            $_encuesta['rq10'] = $req['encuesta'][9][1];
            $_encuesta['rq11'] = $req['encuesta'][10][1];
            $_encuesta['rq12'] = $req['encuesta'][11][1];


            $query = $this->db->select("*", 'hm_pasaportes_asignaciones', null, "nhc='$nhc' ORDER BY id DESC", 1);
            $user_data = json_decode($query[0]['data'], true);

            $data = array(
                'pte' => $pte,
                'preferencias' => $_encuesta,
            );

            $this->db->update('hm_pasaportes', array(
                'status' => 2,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ), "nhc='" . $nhc . "'", 1);

            $_encuesta['NHC'] = $nhc;
            $_encuesta['PTE'] = $pte['PTE'];
            $_encuesta['groupName'] = $user_data['groupName'];

            $this->generarPasaporte($_encuesta);

            return array(
                'status' => true,
                'data' => $encuesta,
                'message' => 'Proceso realizado con éxito.',
            );

        } catch (ModelsException $e) {

            $error = array(
                'status' => false,
                'data' => [],
                'message' => $e->getMessage(),
            );

            return $error;

        }
    }

    public function nuevoPasaporte_2()
    {
        try {

            global $config, $http;

            # Obtener los datos $_POST
            $req = $http->request->all();
            $encuesta = $req['encuesta'];
            $pte = $req['pte'];
            $nhc = $pte['NHC'];

            foreach ($encuesta as $key => $value) {

                if ($value[0] == 'rq1') {
                    $_respuestas['rq1'] = $value[1];
                }

                if ($value[0] == 'rq1_1') {
                    $_respuestas['rq1'] = $_respuestas['rq1'] . ' ' . $value[1];
                }

                if ($value[0] == 'rq2') {
                    $_respuestas['rq2'] = $value[1];
                }

                if ($value[0] == 'rq2_1') {
                    $_respuestas['rq2'] = $_respuestas['rq2'] . ', Hora: ' . $value[1];
                }

                if ($value[0] == 'rq3') {
                    $_respuestas['rq3'] = $value[1];
                }

                if ($value[0] == 'rq3_1') {
                    $_respuestas['rq3'] = $_respuestas['rq3'] . ', Hora: ' . $value[1];
                }

                if ($value[0] == 'rq4') {
                    $_respuestas['rq4'] = $value[1];
                }

                if ($value[0] == 'rq5') {
                    $_respuestas['rq5'] = $value[1];
                }

                if ($value[0] == 'rq5_1') {
                    $_respuestas['rq5'] = $value[1];
                }

                if ($value[0] == 'rq6') {
                    $_respuestas['rq6'] = $value[1];
                }

                if ($value[0] == 'rq6_1') {
                    $_respuestas['rq6'] = $_respuestas['rq6'] . ', Comida Favorita: ' . $value[1];
                }

                if ($value[0] == 'rq6_2') {
                    $_respuestas['rq6'] = $_respuestas['rq6'] . ' Preferencias Comida: ' . $value[1];
                }

                if ($value[0] == 'rq6_3') {
                    $_respuestas['rq6'] = $_respuestas['rq6'] . ' Excluir: ' . $value[1];
                }

                if ($value[0] == 'rq7') {
                    $_respuestas['rq7'] = $value[1];
                }

                if ($value[0] == 'rq8') {
                    $_respuestas['rq8'] = $value[1];
                }

                if ($value[0] == 'rq8_1') {
                    $_respuestas['rq8'] = $_respuestas['rq8'] . ', Conocer procedimiento: ' . $value[1];
                }

                if ($value[0] == 'rq9') {
                    $_respuestas['rq9'] = $value[1];
                }

                if ($value[0] == 'rq10') {
                    $_respuestas['rq10'] = $value[1];
                }

                if ($value[0] == 'rq10_1') {
                    $_respuestas['rq10'] = $_respuestas['rq10'] . ', Turno: ' . $value[1];
                }

                if ($value[0] == 'rq11') {
                    $_respuestas['rq11'] = $value[1];
                }

                if ($value[0] == 'rq12') {
                    $_respuestas['rq12'] = $value[1];
                }

                if ($value[0] == 'rq12_1') {
                    $_respuestas['rq12'] = $_respuestas['rq12'] . ', Nombre: ' . $value[1];
                }

            }

            if (count($_respuestas) < 12) {
                throw new ModelsException('Todas las preguntas son obligatorias.');
            }


            return array(
                'status' => true,
                'data' => $encuesta,
                'message' => 'Proceso realizado con éxito.',
                'logs' => $_respuestas
            );

        } catch (ModelsException $e) {

            $error = array(
                'status' => false,
                'data' => [],
                'message' => $e->getMessage(),
            );

            return $error;

        }
    }

    public function nuevaAsignacion()
    {

        global $config, $http;

        # Obtener los datos $_POST
        $req = $http->request->all();
        $usr = $req['usr'];
        $pte = $req['pte'];
        unset($usr['nhc']);

        $nhc = $pte['NHC'];

        $this->db->update('hm_pasaportes', array(
            'status' => 1,
        ), "nhc='" . $nhc . "'", 1);

        $this->db->insert(
            'hm_pasaportes_asignaciones',
            array(
                'nhc' => $nhc,
                'data' => json_encode($usr, JSON_UNESCAPED_UNICODE),
            )
        );

        $pte['idUsr'] = $usr['idUsr'];
        $pte['grupoId'] = $usr['grupoId'];
        $pte['groupName'] = $usr['groupName'];


        $this->mencionarUsuario($pte);

        return array(
            'status' => true,
            'data' => $pte,
            'message' => 'Proceso realizado con éxito.',
        );
    }

    public function mencionarUsuario(array $data)
    {

        $_datos = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://prod-172.westus.logic.azure.com:443/workflows/0e3209334740439485670a2b0f75a8fd/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=7a01-hPrCVTszjua0GA40S9KKKTLJ89CuuK1sZjtGDo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_datos);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-Type: application/json',
            )
        );

        $response = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code == 200) {
            # return $response;
            // return true;
        } else {

            //   return false;
        }

        # return $response;

        curl_close($ch);

    }

    public function historialAsignaciones()
    {
        try {

            global $config, $http;

            $nhc = $http->query->get('nhc');

            $query = $this->db->select("*", 'hm_pasaportes_asignaciones', null, "nhc='$nhc' ORDER BY id DESC", 1);

            $data = json_decode($query[0]['data'], true);
            $data['nhc'] = $query[0]['nhc'];

            return array(
                'status' => true,
                'data' => $data,
            );

        } catch (ModelsException $e) {

            $error = array(
                'status' => false,
                'data' => [],
                'message' => $e->getMessage(),
            );

            return $error;

        }
    }

    public function verPasaporte()
    {
        try {

            global $config, $http;

            # Obtener los datos $_POST
            $nhc = $http->query->get('nhc');
            $idFiltro = $http->query->get('idFiltro');

            if ($nhc !== null && $idFiltro == null) {
                $query = $this->db->select('*', 'hm_pasaportes', null, "nhc='$nhc'", 1);
            } else {
                if ($idFiltro !== null && $idFiltro == 1) {
                    $query = $this->db->select('*', 'hm_pasaportes', null, "status <= '$idFiltro'");
                } else {
                    $query = $this->db->select('*', 'hm_pasaportes', null, "status = '$idFiltro'");
                }
            }


            $data = array();

            if ($query !== false) {

                foreach ($query as $k) {
                    $_data = json_decode($k['data'], true);
                    $jsonData = $_data['pte'];
                    $jsonData['STATUS'] = $k['status'];
                    $jsonData['ENCUESTA'] = $_data['preferencias'];
                    $data[] = $jsonData;
                }

            }

            # Si no esta registrada imprimir valores para proceder a registro de cuenta electronica
            return array(
                'status' => true,
                'data' => $data,
            );

        } catch (ModelsException $e) {

            $error = array(
                'status' => false,
                'data' => [],
                'message' => $e->getMessage(),
            );

            return $error;

        }
    }

    /**
     * __construct()
     */
    public function __construct(IRouter $router = null)
    {
        parent::__construct($router);
        $this->startDBConexion();

    }
}