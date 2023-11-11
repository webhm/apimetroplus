<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\models\usuarios;

use app\models\usuarios as Model;
use DateTime;
use Doctrine\DBAL\DriverManager;
use Ocrend\Kernel\Helpers as Helper;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo Pacientes
 */

class Pacientes extends Models implements IModels
{
    # Variables de clase
    private $pte = null;
    private $tipoBusqueda = null;
    private $sortField = 'ROWNUM_';
    private $sortType = 'desc'; # desc
    private $start = 1;
    private $length = 25;
    private $searchField = null;
    private $startDate = null;
    private $endDate = null;
    private $_conexion = null;
    private $user = null;

    private function conectar_Oracle()
    {
        global $config;

        $_config = new \Doctrine\DBAL\Configuration();

        # SETEAR LA CONNEXION A LA BASE DE DATOS DE ORACLE GEMA
        $this->_conexion = \Doctrine\DBAL\DriverManager::getConnection($config['database']['drivers']['oracle'], $_config);
    }

    private function getAuthorization()
    {

        try {

            global $http;

            $token = $http->headers->get("Authorization");

            $auth = new Model\Auth;
            $key = $auth->GetData($token);

            $this->user = $key;
        } catch (ModelsException $e) {

            return array('status' => false, 'message' => $e->getMessage());
        }
    }

    private function setParameters()
    {

        global $http;

        foreach ($http->request->all() as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getPaciente($nhc)
    {

        try {

            # Conectar base de datos
            $this->conectar_Oracle();

            $this->setSpanishOracle();

            # Devolver todos los resultados
            $sql = " SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, a.pk_numero_admision nro_admision, a.pk_fk_paciente hc, e.fk_persona COD_PERSONA,

            fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

            f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

            b.pk_fk_medico cod_medico, fun_busca_nombre_medico(b.pk_fk_medico) nombre_medico, d.descripcion especialidad,

            fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

            fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

            FROM cad_admisiones a, cad_medicos_admision b, edm_medicos_especialidad c, aas_especialidades d, cad_pacientes e, bab_personas f

            WHERE a.alta_clinica         IS NULL            AND

                  a.pre_admision         = 'N'              AND

                  a.anulado              = 'N'              AND

                  a.discriminante        IN ('HPN','EMA')   AND

                  a.pk_fk_paciente       = b.pk_fk_paciente AND

                  a.pk_numero_admision   = b.pk_fk_admision AND

                  b.clasificacion_medico = 'TRA'            AND

                  b.pk_fk_medico         = c.pk_fk_medico   AND

                  c.principal            = 'S'              AND

                  c.pk_fk_especialidad   = d.pk_codigo      AND

                  a.pk_fk_paciente       = e.pk_nhcl        AND

                  e.fk_persona           = f.pk_codigo      AND

                  e.pk_nhcl =  '$nhc' ";

            # Execute
            $stmt = $this->_conexion->query($sql);

            $this->_conexion->close();

            $data = $stmt->fetch();

            return array(
                'status' => true,
                'data' => $data,
            );
        } catch (ModelsException $e) {
            return array('status' => false, 'data' => [], 'message' => $e->getMessage());
        }
    }

    public function getReportesPEP()
    {

        try {

            global $http;

            $idReport = $http->request->get('idReport');
            $numAtencion = $http->request->get('numAtencion');

            if (!empty($idReport) && !empty($numAtencion)) {

                if ($idReport == 'FC') {
                    $url = "http://172.16.253.18/report-executor/mvreport?name=USR_CURVA_FREQCARDIACA_SP&cdMultiEmpresa=1&cdAtendimento=" . $numAtencion;
                } else if ($idReport == 'PSD') {
                    $url = "http://172.16.253.18/report-executor/mvreport?name=USR_CURVA_PRESION_SP&cdMultiEmpresa=1&cdAtendimento=" . $numAtencion;
                } else if ($idReport == 'TEMP') {
                    $url = "http://172.16.253.18/report-executor/mvreport?name=USR_CURVA_TERMICA_SP&cdMultiEmpresa=1&cdAtendimento=" . $numAtencion;
                } else if ($idReport == 'GLUC') {
                    $url = "http://172.16.253.18/report-executor/mvreport?name=USR_CONTROL_HEMOGLUCOTEST&cdMultiEmpresa=1&cdAtendimento=" . $numAtencion;
                } else {
                    throw new Exception('Id de Reporte no válido.');
                }
            } else {
                $url = $http->request->get('url');
            }

            $data = base64_encode(file_get_contents($url));

            return array(
                'status' => true,
                'data' => $data,
                'message' => 'Proceso realizado con éxito.',
            );
        } catch (\Exception $e) {

            return array(
                'status' => false,
                'data' => [],
                'message' => $e->getMessage()
            );
        }
    }

    public function getPacientesResidentes($codMedico, $params = '', $area = '')
    {

        if ($codMedico == '0' && $params !== '') {

            $sql = " SELECT *
            FROM (
            SELECT b.*, ROWNUM AS NUM
            FROM (

            SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

                TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

                a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                b.pk_fk_medico cod_medico, fun_busca_nombre_medico(b.pk_fk_medico) nombre_medico, d.descripcion especialidad,

                fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                FROM cad_admisiones a, cad_medicos_admision b, edm_medicos_especialidad c, aas_especialidades d, cad_pacientes e, bab_personas f

                WHERE a.alta_clinica         IS NULL            AND

                    a.pre_admision         = 'N'              AND

                    a.anulado              = 'N'              AND

                    a.discriminante        IN ('HPN','EMA')   AND

                    a.pk_fk_paciente       = b.pk_fk_paciente AND

                    a.pk_numero_admision   = b.pk_fk_admision AND

                    b.clasificacion_medico = 'TRA'            AND

                    b.pk_fk_medico         = c.pk_fk_medico   AND

                    c.principal            = 'S'              AND

                    c.pk_fk_especialidad   = d.pk_codigo      AND

                    a.pk_fk_paciente       = e.pk_nhcl        AND

                    e.fk_persona           = f.pk_codigo  AND

                    (f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre LIKE '%$params%' )

                    ORDER BY 4 DESC


            ) b
            WHERE ROWNUM <= " . $this->length . "
            )
            WHERE NUM > " . $this->start . "
            ";
        } else if ($codMedico == '0' && $params == '') {

            $sql = " SELECT *
            FROM (
            SELECT b.*, ROWNUM AS NUM
            FROM (

            SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

                TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

                a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                b.pk_fk_medico cod_medico, fun_busca_nombre_medico(b.pk_fk_medico) nombre_medico, d.descripcion especialidad,

                fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                FROM cad_admisiones a, cad_medicos_admision b, edm_medicos_especialidad c, aas_especialidades d, cad_pacientes e, bab_personas f

                WHERE a.alta_clinica         IS NULL            AND

                    a.pre_admision         = 'N'              AND

                    a.anulado              = 'N'              AND

                    a.discriminante        IN ('HPN','EMA')   AND

                    a.pk_fk_paciente       = b.pk_fk_paciente AND

                    a.pk_numero_admision   = b.pk_fk_admision AND

                    b.clasificacion_medico = 'TRA'            AND

                    b.pk_fk_medico         = c.pk_fk_medico   AND

                    c.principal            = 'S'              AND

                    c.pk_fk_especialidad   = d.pk_codigo      AND

                    a.pk_fk_paciente       = e.pk_nhcl        AND

                    e.fk_persona           = f.pk_codigo

                    ORDER BY 4 DESC


            ) b
            WHERE ROWNUM <= " . $this->length . "
            )
            WHERE NUM > " . $this->start . "
            ";
        } else if ($codMedico !== '0' && $params !== '') {

            $sql = " SELECT *
            FROM (
            SELECT b.*, ROWNUM AS NUM
            FROM (

            SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

                TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

                a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                b.pk_fk_medico cod_medico, fun_busca_nombre_medico(b.pk_fk_medico) nombre_medico, d.descripcion especialidad,

                fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                FROM cad_admisiones a, cad_medicos_admision b, edm_medicos_especialidad c, aas_especialidades d, cad_pacientes e, bab_personas f

                WHERE a.alta_clinica         IS NULL            AND

                    a.pre_admision         = 'N'              AND

                    a.anulado              = 'N'              AND

                    a.discriminante        IN ('HPN','EMA')   AND

                    a.pk_fk_paciente       = b.pk_fk_paciente AND

                    a.pk_numero_admision   = b.pk_fk_admision AND

                    b.clasificacion_medico = 'TRA'            AND

                    b.pk_fk_medico         = c.pk_fk_medico   AND

                    c.principal            = 'S'              AND

                    c.pk_fk_especialidad   = d.pk_codigo      AND

                    a.pk_fk_paciente       = e.pk_nhcl        AND

                    e.fk_persona           = f.pk_codigo  AND

                    b.pk_fk_medico  LIKE '%$codMedico'  AND

                    (f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre LIKE '%$params%' )

                    ORDER BY 4 DESC


            ) b
            WHERE ROWNUM <= " . $this->length . "
            )
            WHERE NUM > " . $this->start . "
            ";
        } else if ($codMedico !== '0' && $params == '') {

            $sql = " SELECT *
            FROM (
            SELECT b.*, ROWNUM AS NUM
            FROM (

            SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

                TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

                a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                b.pk_fk_medico cod_medico, fun_busca_nombre_medico(b.pk_fk_medico) nombre_medico, d.descripcion especialidad,

                fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                FROM cad_admisiones a, cad_medicos_admision b, edm_medicos_especialidad c, aas_especialidades d, cad_pacientes e, bab_personas f

                WHERE a.alta_clinica         IS NULL            AND

                    a.pre_admision         = 'N'              AND

                    a.anulado              = 'N'              AND

                    a.discriminante        IN ('HPN','EMA')   AND

                    a.pk_fk_paciente       = b.pk_fk_paciente AND

                    a.pk_numero_admision   = b.pk_fk_admision AND

                    b.clasificacion_medico = 'TRA'            AND

                    b.pk_fk_medico         = c.pk_fk_medico   AND

                    c.principal            = 'S'              AND

                    c.pk_fk_especialidad   = d.pk_codigo      AND

                    a.pk_fk_paciente       = e.pk_nhcl        AND

                    e.fk_persona           = f.pk_codigo  AND

                    b.pk_fk_medico  LIKE '%$codMedico'

                    ORDER BY 4 DESC


            ) b
            WHERE ROWNUM <= " . $this->length . "
            )
            WHERE NUM > " . $this->start . "
            ";
        }

        # Conectar base de datos
        $this->conectar_Oracle();

        $this->setSpanishOracle();

        # Execute
        $stmt = $this->_conexion->query($sql);

        $this->_conexion->close();

        $data = $stmt->fetchAll();

        #return $data;

        $time = new DateTime();
        $nuevaHora = strtotime('-2 days', $time->getTimestamp());
        $menosHora = date('Y-m-d', $nuevaHora);
        $tiempoControl = strtotime($menosHora);

        $nuevaHoraHosp = strtotime('-300 days', $time->getTimestamp());
        $menosHoraHosp = date('Y-m-d', $nuevaHoraHosp);
        $tiempoControlHosp = strtotime($menosHoraHosp);

        $resultados = array();

        foreach ($data as $key) {

            $fechaFormat = strtotime($key['FECHA_ADMISION']);

            if ($area == '') {
                if ($fechaFormat > $tiempoControlHosp) {
                    $resultados[] = $key;
                }
            } else {

                if ($area == 'HPN') {
                    if ($fechaFormat > $tiempoControlHosp && $key['DISCRIMINANTE'] == $area) {
                        $resultados[] = $key;
                    }
                } else {
                    if ($fechaFormat > $tiempoControl && $key['DISCRIMINANTE'] == $area) {
                        $resultados[] = $key;
                    }
                }
            }
        }

        return $resultados;
    }

    public function getPacientesInterconsulta($codMedico, $params = '')
    {

        if ($params !== '') {

            $sql = " SELECT *
            FROM (
            SELECT b.*, ROWNUM AS NUM
            FROM (

                SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

            TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

            a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

            fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

            f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

            m.pk_codigo cod_medico, l.cd_usuario usuario_medico, j.nm_prestador nombre_medico, k.ds_especialid especialidad,

            fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

            fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

            FROM   cad_admisiones a, cad_pacientes e, bab_personas f, mv_itg_admision g, par_med@db_mv h, pw_documento_clinico@db_mv i,

            prestador@db_mv j, especialid@db_mv k, usuarios@db_mv l, edm_medicos m

            WHERE  a.alta_clinica             IS NULL                  AND

            a.pre_admision             = 'N'                    AND

            a.anulado                  = 'N'                    AND

            a.discriminante            IN ('HPN','EMA')         AND

            a.pk_fk_paciente           = e.pk_nhcl              AND

            e.fk_persona               = f.pk_codigo            AND

            a.pk_fk_paciente           = g.hc_mv || '01'        AND

            a.pk_numero_admision       = g.adm_gema             AND

            g.desc_error               IS NULL                  AND

            g.aten_mv                  = h.cd_atendimento       AND

            h.cd_documento_clinico     = i.cd_documento_clinico AND

            i.tp_status                <> 'CANCELADO'           AND

            h.cd_prestador_requisitado = j.cd_prestador         AND

            h.cd_especialid            = k.cd_especialid        AND

            j.cd_prestador             = l.cd_prestador (+)     AND

            h.cd_prestador_requisitado = m.homologado (+)  AND

            m.pk_codigo LIKE '%$codMedico'  AND

            (f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre LIKE '%$params%' )

            UNION ALL

            SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

            TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

            a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

            fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

            f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

            m.pk_codigo cod_medico, l.cd_usuario usuario_medico, j.nm_prestador nombre_medico, k.ds_especialid especialidad,

            fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

            fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

            FROM   cad_admisiones a, cad_pacientes e, bab_personas f, mv_itg_admision g, mv_itg_admision g1, par_med@db_mv h, pw_documento_clinico@db_mv i,

            prestador@db_mv j, especialid@db_mv k, usuarios@db_mv l, edm_medicos m

            WHERE  a.alta_clinica             IS NULL                  AND

            a.pre_admision             = 'N'                    AND

            a.anulado                  = 'N'                    AND

            a.discriminante            = 'HPN'                  AND

            a.pk_fk_paciente           = e.pk_nhcl              AND

            e.fk_persona               = f.pk_codigo            AND

            a.pk_fk_paciente           = g.hc_mv || '01'        AND

            a.pk_numero_admision       = g.adm_gema             AND

            g.desc_error               IS NULL                  AND

            g.aten_mv_refer            = g1.aten_mv             AND

            g1.origen                  = 2                      AND

            g1.desc_error              IS NULL                  AND

            g1.aten_mv                 = h.cd_atendimento       AND

            h.cd_documento_clinico     = i.cd_documento_clinico AND

            i.tp_status                <> 'CANCELADO'           AND

            h.cd_prestador_requisitado = j.cd_prestador         AND

            h.cd_especialid            = k.cd_especialid        AND

            j.cd_prestador             = l.cd_prestador (+)     AND

            h.cd_prestador_requisitado = m.homologado (+)  AND

            m.pk_codigo LIKE '%$codMedico'  AND

            (f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre LIKE '%$params%' )

                ORDER BY 8 ASC

            ) b
            WHERE ROWNUM <= " . $this->length . "
            )
            WHERE NUM > " . $this->start . "
            ";
        } else {

            $sql = " SELECT *
            FROM (
            SELECT b.*, ROWNUM AS NUM
            FROM (

                                SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

                TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

                a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                m.pk_codigo cod_medico, l.cd_usuario usuario_medico, j.nm_prestador nombre_medico, k.ds_especialid especialidad,

                fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                FROM   cad_admisiones a, cad_pacientes e, bab_personas f, mv_itg_admision g, par_med@db_mv h, pw_documento_clinico@db_mv i,

                prestador@db_mv j, especialid@db_mv k, usuarios@db_mv l, edm_medicos m

                WHERE  a.alta_clinica             IS NULL                  AND

                a.pre_admision             = 'N'                    AND

                a.anulado                  = 'N'                    AND

                a.discriminante            IN ('HPN','EMA')         AND

                a.pk_fk_paciente           = e.pk_nhcl              AND

                e.fk_persona               = f.pk_codigo            AND

                a.pk_fk_paciente           = g.hc_mv || '01'        AND

                a.pk_numero_admision       = g.adm_gema             AND

                g.desc_error               IS NULL                  AND

                g.aten_mv                  = h.cd_atendimento       AND

                h.cd_documento_clinico     = i.cd_documento_clinico AND

                i.tp_status                <> 'CANCELADO'           AND

                h.cd_prestador_requisitado = j.cd_prestador         AND

                h.cd_especialid            = k.cd_especialid        AND

                j.cd_prestador             = l.cd_prestador (+)     AND

                h.cd_prestador_requisitado = m.homologado (+)  AND

                m.pk_codigo LIKE '%$codMedico'

                UNION ALL

                SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, TO_CHAR(a.hora_admision, 'HH24:MI') AS hora_admision,

                TO_DATE(TO_CHAR(a.fecha_admision, 'DD/MM/YYYY') || ' ' || TO_CHAR(a.hora_admision, 'HH24:MI'),'DD/MM/YYYY HH24:MI') fecha_hora_adm,

                a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                m.pk_codigo cod_medico, l.cd_usuario usuario_medico, j.nm_prestador nombre_medico, k.ds_especialid especialidad,

                fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                FROM   cad_admisiones a, cad_pacientes e, bab_personas f, mv_itg_admision g, mv_itg_admision g1, par_med@db_mv h, pw_documento_clinico@db_mv i,

                prestador@db_mv j, especialid@db_mv k, usuarios@db_mv l, edm_medicos m

                WHERE  a.alta_clinica             IS NULL                  AND

                a.pre_admision             = 'N'                    AND

                a.anulado                  = 'N'                    AND

                a.discriminante            = 'HPN'                  AND

                a.pk_fk_paciente           = e.pk_nhcl              AND

                e.fk_persona               = f.pk_codigo            AND

                a.pk_fk_paciente           = g.hc_mv || '01'        AND

                a.pk_numero_admision       = g.adm_gema             AND

                g.desc_error               IS NULL                  AND

                g.aten_mv_refer            = g1.aten_mv             AND

                g1.origen                  = 2                      AND

                g1.desc_error              IS NULL                  AND

                g1.aten_mv                 = h.cd_atendimento       AND

                h.cd_documento_clinico     = i.cd_documento_clinico AND

                i.tp_status                <> 'CANCELADO'           AND

                h.cd_prestador_requisitado = j.cd_prestador         AND

                h.cd_especialid            = k.cd_especialid        AND

                j.cd_prestador             = l.cd_prestador (+)     AND

                h.cd_prestador_requisitado = m.homologado (+)  AND

                m.pk_codigo LIKE '%$codMedico'

                ORDER BY 8 ASC

            ) b
            WHERE ROWNUM <= " . $this->length . "
            )
            WHERE NUM > " . $this->start . "
            ";
        }

        # Conectar base de datos
        $this->conectar_Oracle();

        $this->setSpanishOracle();

        # Execute
        $stmt = $this->_conexion->query($sql);

        $this->_conexion->close();

        $data = $stmt->fetchAll();

        return $data;
    }

    public function getMisPacientes()
    {

        try {

            global  $http;

            # $this->getAuthorization();
            $codMedico = (int) "0";

            $resultados_ema = $this->getPacientesResidentes($codMedico, '', 'EMA');
            $resultados_hpn = $this->getPacientesResidentes($codMedico, '', 'HPN');

            # Devolver Información
            return array(
                'status' => true,
                'dataTra' => $resultados_ema,
                'totalTra' => count($resultados_ema),
                'dataInter' => $resultados_hpn,
                'totalInter' => count($resultados_hpn),
                'codMedico' => $codMedico,
            );
        } catch (ModelsException $e) {

            return array(
                'status' => false,
                'dataTra' => [],
                'dataInter' => [],
                'message' => $e->getMessage(),
                'codMedico' => $codMedico
            );
        }
    }

    public function getResultadosPacientes()
    {

        try {

            global $config, $http;

            $this->getAuthorization();

            $codMedico = $this->user->codMedico;

            # seteo de valores para paginacion
            $fecha = date('d-m-Y');

            $this->start = (int) $http->query->get('start');

            $this->length = (int) $http->query->get('length');

            if ($this->start >= 10) {
                $this->length = $this->start + $this->length;
            }

            $_searchField = (bool) $http->query->get('searchField');

            if ($_searchField != false) {

                $this->searchField = $this->quitar_tildes(mb_strtoupper($this->sanear_string($http->query->get('searchField')), 'UTF-8'));

                $this->searchField = str_replace(" ", "%", $this->searchField);

                $sql = " SELECT *
                FROM (
                SELECT b.*, ROWNUM AS NUM
                FROM (


                SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                    fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                    f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                    b.pk_fk_medico cod_medico, fun_busca_nombre_medico(b.pk_fk_medico) nombre_medico, d.descripcion especialidad,

                    fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                    fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                    FROM cad_admisiones a, cad_medicos_admision b, edm_medicos_especialidad c, aas_especialidades d, cad_pacientes e, bab_personas f

                    WHERE a.alta_clinica         IS NULL            AND

                        a.pre_admision         = 'N'              AND

                        a.anulado              = 'N'              AND

                        a.discriminante        IN ('HPN','EMA')   AND

                        a.pk_fk_paciente       = b.pk_fk_paciente AND

                        a.pk_numero_admision   = b.pk_fk_admision AND

                        b.clasificacion_medico = 'TRA'            AND

                        b.pk_fk_medico         = c.pk_fk_medico   AND

                        c.principal            = 'S'              AND

                        c.pk_fk_especialidad   = d.pk_codigo      AND

                        a.pk_fk_paciente       = e.pk_nhcl        AND

                        e.fk_persona           = f.pk_codigo  AND

                            (f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre LIKE '%$this->searchField%' )

                            ORDER BY a.fecha_admision DESC

                ) b
                WHERE ROWNUM <= " . $this->length . "
                )
                WHERE NUM > " . $this->start . "
                ";
            } elseif ($_searchField != false && $this->isRange($http->query->get('search')['value'])) {

                $this->searchField = explode('-', $http->query->get('search')['value']);

                $desde = $this->searchField[1]; // Valores para busqueda de desde rango de fechas
                $hasta = $this->searchField[2]; // valor de busqueda para hasta rango de fechas

                $sql = " SELECT *
                FROM (
                SELECT b.*, ROWNUM AS NUM
                FROM (
                    SELECT *
                    FROM WEB3_RESULTADOS_LAB NOLOCK WHERE TOT_SC != TOD_DC
                    AND FECHA >= '$desde'
                    AND FECHA <= '$hasta'
                    ORDER BY SC DESC
                ) b
                WHERE ROWNUM <= " . $this->length . "
                )
                WHERE NUM > " . $this->start . "
                ";
            } else {

                $sql = " SELECT *
                FROM (
                SELECT b.*, ROWNUM AS NUM
                FROM (


                    SELECT a.discriminante, TRUNC (a.fecha_admision) fecha_admision, a.pk_numero_admision nro_admision, a.pk_fk_paciente hc,

                        fun_calcula_anios_a_fecha(f.fecha_nacimiento,TRUNC(a.fecha_admision)) edad,

                        f.primer_apellido || ' ' || f.segundo_apellido || ' ' || f.primer_nombre || ' ' || f.segundo_nombre nombre_paciente,

                        b.pk_fk_medico cod_medico, fun_busca_nombre_medico(b.pk_fk_medico) nombre_medico, d.descripcion especialidad,

                        fun_busca_ubicacion_corta(1,a.pk_fk_paciente,a.pk_numero_admision) nro_habitacion,

                        fun_busca_diagnostico(1,a.pk_fk_paciente, a.pk_numero_admision) dg_principal

                        FROM cad_admisiones a, cad_medicos_admision b, edm_medicos_especialidad c, aas_especialidades d, cad_pacientes e, bab_personas f

                        WHERE a.alta_clinica         IS NULL            AND

                            a.pre_admision         = 'N'              AND

                            a.anulado              = 'N'              AND

                            a.discriminante        IN ('HPN','EMA')   AND

                            a.pk_fk_paciente       = b.pk_fk_paciente AND

                            a.pk_numero_admision   = b.pk_fk_admision AND

                            b.clasificacion_medico = 'TRA'            AND

                            b.pk_fk_medico         = c.pk_fk_medico   AND

                            c.principal            = 'S'              AND

                            c.pk_fk_especialidad   = d.pk_codigo      AND

                            a.pk_fk_paciente       = e.pk_nhcl        AND

                            e.fk_persona           = f.pk_codigo   ORDER BY a.fecha_admision DESC

                ) b
                WHERE ROWNUM <= " . $this->length . "
                )
                WHERE NUM > " . $this->start . "
                ";
            }

            # Conectar base de datos
            $this->conectar_Oracle();

            $this->setSpanishOracle();

            # Execute
            $stmt = $this->_conexion->query($sql);

            $this->_conexion->close();

            $data = $stmt->fetchAll();

            # NO EXITEN RESULTADOS
            $this->notResults($data);

            # Datos de usuario cuenta activa
            $resultados_tra = array();
            $resultados_inter = array();
            $resultados_ema = array();
            $resultados_hpn = array();

            foreach ($data as $key) {

                if ($codMedico == "0") {

                    if ($key['DISCRIMINANTE'] == 'EMA') {
                        $resultados_ema[] = $key;
                    } else {
                        $resultados_hpn[] = $key;
                    }
                } else {

                    if ($key['CLASIFICACION_MEDICO'] == 'TRA') {
                        $resultados_tra[] = $key;
                    } else {
                        $resultados_inter[] = $key;
                    }
                }
            }

            return array(
                'status' => true,
                'dataTra' => $resultados_ema,
                'totalTra' => count($resultados_ema),
                'dataInter' => $resultados_hpn,
                'totalInter' => count($resultados_hpn),
                'codMedico' => $codMedico,

            );
        } catch (ModelsException $e) {

            return array(
                'status' => false,
                'dataTra' => [],
                'dataInter' => [],
                'message' => $e->getMessage(),
                'codMedico' => $codMedico
            );
        }
    }

    public function verLogs()
    {

        try {

            # Set Parametrs
            $this->setParameters();

            # Verificar que no están vacíos
            if (Helper\Functions::e($this->pte, $this->tipoBusqueda)) {
                throw new ModelsException('Ingrese un valor de búsqueda.');
            }

            # Conectar base de datos
            $this->conectar_Oracle();

            if ($this->tipoBusqueda == 'cc') {

                if (!is_numeric($this->pte)) {
                    throw new ModelsException('Valor de búsqueda debe ser númerico.');
                }

                # Devolver todos los resultados
                $sql = "SELECT * FROM  WEB2_VW_LOGIN NOLOCK
                WHERE COD_PTE IS NOT NULL AND CC = '" . $this->pte . "'  OR PASAPORTE = '" . $this->pte . "' ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetch();

                if (false === $data) {
                    throw new ModelsException('No existe Información.');
                }

                $fk_persona = $data['COD_PTE'];

                # Conectar base de datos
                $this->conectar_Oracle();

                # Devolver todos los resultados
                $sql = " SELECT * FROM  GEMA.WEB_VW_PERSONAS NOLOCK  WHERE FK_PERSONA = '" . $fk_persona . "'   ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetch();

                if (false === $data) {
                    throw new ModelsException('No existe Información.');
                }

                $data = array($data);
            } else if ($this->tipoBusqueda == 'nhc') {

                if (!is_numeric($this->pte)) {
                    throw new ModelsException('Valor de búsqueda debe ser númerico.');
                }

                # Devolver todos los resultados
                $sql = " SELECT * FROM  GEMA.WEB_VW_PERSONAS NOLOCK  WHERE PK_NHCL ='" . $this->pte . "'   ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetch();

                if (false === $data) {
                    throw new ModelsException('No existe Información.');
                }

                $data = array($data);
            } else {

                if (!Helper\Strings::only_letters($this->pte)) {
                    throw new ModelsException('Valor de búsqueda debe ser texto.');
                }

                # Devolver todos los resultados
                $sql = " SELECT * FROM  gema.vw_web_pacientes NOLOCK  WHERE APELLIDOS || ' ' || NOMBRES LIKE '%" . $this->pte . "%'   ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetchAll();

                if (count($data) === 0) {
                    throw new ModelsException('No existe Información.');
                }
            }

            return array(
                'status' => true,
                'message' => 'Paciente encontrado.',
                'tipoBusqueda' => $this->tipoBusqueda,
                'data' => $data,
            );
        } catch (ModelsException $e) {
            return array(
                'status' => false,
                'data' => [],
                'tipoBusqueda' => $this->tipoBusqueda,
                'message' => $e->getMessage()
            );
        }
    }

    public function buscarPaciente()
    {

        try {

            # Set Parametrs
            $this->setParameters();

            $this->getAuthorization();

            # Verificar que no están vacíos
            if (Helper\Functions::e($this->pte, $this->tipoBusqueda)) {
                throw new ModelsException('Ingrese un valor de búsqueda.');
            }

            # Conectar base de datos
            $this->conectar_Oracle();

            if ($this->tipoBusqueda == 'cc') {

                if (!is_numeric($this->pte)) {
                    throw new ModelsException('Valor de búsqueda debe ser númerico.');
                }

                # Devolver todos los resultados
                $sql = "SELECT * FROM  WEB2_VW_LOGIN NOLOCK
                WHERE COD_PTE IS NOT NULL AND CC = '" . $this->pte . "'  OR PASAPORTE = '" . $this->pte . "' ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetch();

                if (false === $data) {
                    throw new ModelsException('No existe Información.');
                }

                $fk_persona = $data['COD_PTE'];

                # Conectar base de datos
                $this->conectar_Oracle();

                # Devolver todos los resultados
                $sql = " SELECT * FROM  GEMA.WEB_VW_PERSONAS NOLOCK  WHERE FK_PERSONA = '" . $fk_persona . "'   ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetch();

                if (false === $data) {
                    throw new ModelsException('No existe Información.');
                }

                $data = array($data);
            } else if ($this->tipoBusqueda == 'nhc') {

                if (!is_numeric($this->pte)) {
                    throw new ModelsException('Valor de búsqueda debe ser númerico.');
                }

                # Devolver todos los resultados
                $sql = " SELECT * FROM  GEMA.WEB_VW_PERSONAS NOLOCK  WHERE PK_NHCL ='" . $this->pte . "'   ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetch();

                if (false === $data) {
                    throw new ModelsException('No existe Información.');
                }

                $data = array($data);
            } else {

                $this->pte = str_replace(" ", "%", $this->pte);

                # Devolver todos los resultados
                $sql = " SELECT * FROM  gema.vw_web_pacientes NOLOCK  WHERE APELLIDOS || ' ' || NOMBRES LIKE '%" . $this->pte . "%'   ";

                # Execute
                $stmt = $this->_conexion->query($sql);

                $this->_conexion->close();

                $data = $stmt->fetchAll();

                if (count($data) === 0) {
                    throw new ModelsException('No existe Información.');
                }
            }

            return array(
                'status' => true,
                'message' => 'Paciente encontrado.',
                'tipoBusqueda' => $this->tipoBusqueda . " - " . $this->pte,
                'data' => $data,
                'user' => $this->user,
            );
        } catch (ModelsException $e) {
            return array(
                'status' => false,
                'data' => [],
                'tipoBusqueda' => $this->tipoBusqueda . " - " . $this->pte,
                'message' => $e->getMessage()
            );
        }
    }

    private function setSpanishOracle()
    {

        $sql = "alter session set NLS_LANGUAGE = 'SPANISH'";
        # Execute
        $stmt = $this->_conexion->query($sql);

        $sql = "alter session set NLS_TERRITORY = 'SPAIN'";
        # Execute
        $stmt = $this->_conexion->query($sql);

        $sql = " alter session set NLS_DATE_FORMAT = 'DD-MM-YYYY' ";
        # Execute
        $stmt = $this->_conexion->query($sql);
    }

    private function isRange($value)
    {

        $pos = strpos($value, 'fechas');

        if ($pos !== false) {
            return true;
        } else {
            return false;
        }
    }

    private function get_Order_Pagination(array $arr_input)
    {
        # SI ES DESCENDENTE

        $arr = array();
        $NUM = 1;

        if ($this->sortType == 'desc') {

            $NUM = count($arr_input);
            foreach ($arr_input as $key) {
                $key['NUM'] = $NUM;
                $arr[] = $key;
                $NUM--;
            }

            return $arr;
        }

        # SI ES ASCENDENTE

        foreach ($arr_input as $key) {
            $key['NUM'] = $NUM;
            $arr[] = $key;
            $NUM++;
        }

        return $arr;
    }

    private function quitar_tildes($cadena)
    {
        $no_permitidas = array("%", "é", "í", "ó", "ú", "É", "Í", "Ó", "Ú", "ñ", "À", "Ã", "Ì", "Ò", "Ù", "Ã™", "Ã ", "Ã¨", "Ã¬", "Ã²", "Ã¹", "ç", "Ç", "Ã¢", "ê", "Ã®", "Ã´", "Ã»", "Ã‚", "ÃŠ", "ÃŽ", "Ã”", "Ã›", "ü", "Ã¶", "Ã–", "Ã¯", "Ã¤", "«", "Ò", "Ã", "Ã„", "Ã‹");
        $permitidas = array("", "e", "i", "o", "u", "E", "I", "O", "U", "n", "N", "A", "E", "I", "O", "U", "a", "e", "i", "o", "u", "c", "C", "a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "u", "o", "O", "i", "a", "e", "U", "I", "A", "E");
        $texto = str_replace($no_permitidas, $permitidas, $cadena);
        return $texto;
    }

    private function sanear_string($string)
    {

        $string = trim($string);

        //Esta parte se encarga de eliminar cualquier caracter extraño
        $string = str_replace(
            array(">", "< ", ";", ",", ":", "%", "|", "-", "/"),
            ' ',
            $string
        );

        return trim($string);
    }

    private function get_page(array $input, $pageNum, $perPage)
    {
        $start = ($pageNum - 1) * $perPage;
        $end = $start + $perPage;
        $count = count($input);

        // Conditionally return results
        if ($start < 0 || $count <= $start) {
            // Page is out of range
            return array();
        } else if ($count <= $end) {
            // Partially-filled page
            return array_slice($input, $start);
        } else {
            // Full page
            return array_slice($input, $start, $end - $start);
        }
    }

    private function notResults(array $data)
    {
        if (count($data) == 0) {
            throw new ModelsException('No existe más resultados.', 4080);
        }
    }

    /**
     * __construct()
     */

    public function __construct(IRouter $router = null)
    {
        parent::__construct($router);
    }
}
