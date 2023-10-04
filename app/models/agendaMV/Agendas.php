<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\models\agendaMV;

use app\models\agendaMV as Model;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo AgendasMV
 */
class AgendasMV extends Models implements IModels
{

    use DBModel;

    public function registerAgenda()
    {
        try {

            global $config, $http;

            # Obtener los datos $_POST
            $req = $http->request->all();

            $query = $this->db->select('*', 'agendaCentralMV', null, " status='1' ", 1);

            $data = $req;

            $id_data = $this->db->insert(
                'agendaCentralMV',
                array(
                    'calendario' => $data['calendario'],
                    'status' => $data['status'],
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                )
            );

            return array(
                'status' => true,
                'data' => $data,
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

    public function getAllAgendas()
    {
        try {

            global $config, $http;

            $idFiltro = $http->query->get('idFiltro');
            $query = $this->db->select("*", 'agendaCentralMV', null, "status='$idFiltro' ORDER BY id DESC");

            foreach ($query as $key) {
                $query[$key]['data'] = json_decode($query[$key]['data'], true);
            }

            return array(
                'status' => true,
                'data' => $query,
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

    public function getAgenda()
    {
        try {

            global $config, $http;

            $id = $http->query->get('id');
            $query = $this->db->select("*", 'agendaCentralMV', null, "id='$id' ORDER BY id DESC");

            foreach ($query as $key) {
                $query[$key]['data'] = json_decode($query[$key]['data'], true);
            }

            return array(
                'status' => true,
                'data' => $query[0],
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

    public function updateAgenda()
    {
        try {

            global $config, $http;

            $req = $http->request->all();
            $id = $http->query->get('id');
            $data = $req;

            $update = $this->db->update(
                'agendaCentralMV',
                array(
                    'calendario' => $data['calendario'],
                    'status' => $data['status'],
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                ),
                "id='" . $id . "'", 1);

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

    public function deleteAgenda()
    {
        try {

            global $config, $http;

            $req = $http->request->all();
            $id = $http->query->get('id');
            $data = $req;

            $this->db->delete(
                'agendaCentralMV',
                " id='" . $id . "' ",
                1
            );

            return array(
                'status' => true,
            );

        } catch (ModelsException $e) {

            $error = array(
                'status' => false,
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
