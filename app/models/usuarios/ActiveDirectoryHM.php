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

use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\ActiveDirectory\User;
use Ocrend\Kernel\Helpers as Helper;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo ActiveDirectoryHM
 */

class ActiveDirectoryHM extends Models implements IModels
{

    /**
     * loginUsuario LDAP
     */

    public function loginUsuario($user, $pass)
    {

        global $config, $http;

        # Verificar que no están vacíos
        if (Helper\Functions::e($user, $pass)) {
            return array('status' => false, 'message' => 'Credenciales incompletas.');
        }

        // Create a new connection:
        $connection = new Connection([
            'hosts' => ['172.16.2.20'],
            'port' => 389,
            'base_dn' => 'dc=hm,dc=med,dc=ec',
            'username' => 'mchang@hm.med.ec',
            'password' => 'Mmach90000',

            // Optional Configuration Options
            'use_ssl' => false,
            'use_tls' => true,
            'version' => 3,
            'follow_referrals' => false,

            // Custom LDAP Options
            'options' => [
                // See: http://php.net/ldap_set_option
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,

            ],
        ]);

        // Add the connection into the container:
        Container::addConnection($connection);

        if ($connection->auth()->attempt($user . '@hm.med.ec', $pass, $stayAuthenticated = true)) {

            // Successfully authenticated user.
            return array(
                'status' => true,
                'message' => 'Acceso exitoso.',
                'userId' => $user,
            );

        } else {

            // Username or password is incorrect.
            throw new ModelsException('Credenciales incorrectas.');

        }

    }

    /**
     * verifyAccesoMetrovirtual
     */
    public function verifyAccesoMetrovirtual($_user)
    {
        global $http;

        // Create a new connection:
        $connection = new Connection([
            'hosts' => ['172.16.2.20'],
            'port' => 389,
            'base_dn' => 'dc=hm,dc=med,dc=ec',
            'username' => 'mchang@hm.med.ec',
            'password' => 'Mmach90000',

            // Optional Configuration Options
            'use_ssl' => false,
            'use_tls' => true,
            'version' => 3,
            'follow_referrals' => false,

            // Custom LDAP Options
            'options' => [
                // See: http://php.net/ldap_set_option
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,

            ],
        ]);

        // Add the connection into the container:

        $query = $connection->query()->whereEquals('mail', $_user . '@hmetro.med.ec')->select(['memberof'])->get();

        $parseQuery = mb_convert_encoding($query, 'UTF-8', 'UTF-8');

        // Permisos Metrovirtual
        $tienePermiso = false;

        $nameGrupo = 'Grp-MetroVirtual';

        $grupos = $parseQuery[0]['memberof'];

        $refGrupos = array();

        foreach ($grupos as $k => $v) {
            $coincidencia = strpos($v, $nameGrupo);
            if (!$coincidencia) {
                $tienePermiso = true;
            }
        }

        if ($tienePermiso) {
            return true;
        }

        return false;

    }

    /**
     * verifyAccesoMetrovirtual v2
     */
    public function verifyAccesoMetrovirtual_v2($_user)
    {
        global $http;

        // Create a new connection:
        $connection = new Connection([
            'hosts' => ['172.16.2.20'],
            'port' => 389,
            'base_dn' => 'dc=hm,dc=med,dc=ec',
            'username' => 'mchang@hm.med.ec',
            'password' => 'Mmch!!@@11',

            // Optional Configuration Options
            'use_ssl' => false,
            'use_tls' => true,
            'version' => 3,
            'follow_referrals' => false,

            // Custom LDAP Options
            'options' => [
                // See: http://php.net/ldap_set_option
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,

            ],
        ]);

        // Add the connection into the container:

        $query = $connection->query()->whereEquals('mail', $_user . '@hmetro.med.ec')->select(['memberof'])->get();

        $parseQuery = mb_convert_encoding($query, 'UTF-8', 'UTF-8');

        // Permisos Metrovirtual
        $tienePermiso = false;

        $nameGrupo = 'Grp-MetroVirtual';

        $grupos = $parseQuery[0]['memberof'];

        $refGrupos = array();

        foreach ($grupos as $k => $v) {
            $coincidencia = strpos($v, $nameGrupo);
            if (!$coincidencia) {
                $tienePermiso = true;
            }
        }

        if ($tienePermiso) {
            return array(
                'status' => true,
                'data' => $parseQuery,
            );
        }

        return array(
            'status' => false,
            'data' => $parseQuery,
        );

    }

    /**
     * Retorna lista de usuarios LDAP
     */
    public function getGruposAD()
    {
        global $http;

        $idFiltro = $http->query->get('idFiltro');

        // Create a new connection:
        $connection = new Connection([
            'hosts' => ['172.16.2.20'],
            'port' => 389,
            'base_dn' => 'dc=hm,dc=med,dc=ec',
            'username' => 'mchang@hm.med.ec',
            'password' => 'Mmch!!@@11',

            // Optional Configuration Options
            'use_ssl' => false,
            'use_tls' => true,
            'version' => 3,
            'follow_referrals' => false,

            // Custom LDAP Options
            'options' => [
                // See: http://php.net/ldap_set_option
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,

            ],
        ]);

        // Add the connection into the container:

        Container::addConnection($connection);

        if ($idFiltro == 1) {
            $grupo = 'Grp-radius-Medicos';
            $group = Group::find('CN=Grp-radius-Medicos,OU=GruposRadius,OU=Hospital Metropolitano,DC=hm,DC=med,DC=ec');
        }

        if ($idFiltro == 2) {
            $grupo = 'Grp-radius-Residentes';

            $group = Group::find('CN=Grp-radius-Residentes,OU=GruposRadius,OU=Hospital Metropolitano,DC=hm,DC=med,DC=ec');
        }

        if ($idFiltro == 3) {
            $grupo = 'Grp-Metroplus';

            $group = Group::find('CN=Grp-Metroplus,OU=GruposMetroplus,OU=Hospital Metropolitano,DC=hm,DC=med,DC=ec');
        }

        $members = $group->members()->get();

        $usuarios = array();

        foreach ($members as $key => $value) {

            $user = json_decode(json_encode($value), true);

            $usuarios[] = array(
                'samaccountname' => (array_key_exists('samaccountname', $user) ? $user['samaccountname'][0] : null),
                'sn' => (array_key_exists('sn', $user) ? $user['sn'][0] : null),
                'cn' => (array_key_exists('cn', $user) ? $user['cn'][0] : null),
                'name' => (array_key_exists('name', $user) ? $user['name'][0] : null),
                'mail' => (array_key_exists('mail', $user) ? $user['mail'][0] : null),
                'grupo' => $grupo,
                'whenchanged' => (array_key_exists('whenchanged', $user) ? date('d-m-Y H:i:s', strtotime($user['whenchanged'][0])) : null),
                'whencreated' => (array_key_exists('whencreated', $user) ? date('d-m-Y H:i:s', strtotime($user['whencreated'][0])) : null),
                'pwdlastset' => (array_key_exists('pwdlastset', $user) ? date('d-m-Y H:i:s', strtotime($user['pwdlastset'][0])) : null),
                'lastlogontimestamp' => (array_key_exists('lastlogontimestamp', $user) ? date('d-m-Y H:i:s', strtotime($user['lastlogontimestamp'][0])) : null),
                'data' => array(),
            );
        }

        return array(
            'status' => true,
            'grupo' => 'Grp-radius-Medicos',
            'data' => $usuarios,
            'total' => count($usuarios),
        );

    }

    /**
     * Retorna lista de usuarios LDAP
     */
    public function busquedaUsuario()
    {
        global $http;

        // Create a new connection:
        $connection = new Connection([
            'hosts' => ['172.16.2.20'],
            'port' => 389,
            'base_dn' => 'dc=hm,dc=med,dc=ec',
            'username' => 'mchang@hm.med.ec',
            'password' => 'Mmch!!@@11',

        ]);

        // Add the connection into the container:

        $query = $connection->query()->whereEquals('mail', 'mchang@hmetro.med.ec')->select(['memberof'])->get();

        $parseQuery = mb_convert_encoding($query, 'UTF-8', 'UTF-8');

        // Permisos Metrovirtual

        $nameGrupo = 'Grp-MetroVirtual';

        $grupos = $parseQuery[0]['memberof'];

        return (array) $parseQuery[0]['memberof'];

    }

    /**
     * Retorna lista de usuarios LDAP
     */
    public function resetPassword()
    {
        global $http;

        // Create a new connection:
        $connection = new Connection([
            'hosts' => ['172.16.2.20'],

            'port' => 636,
            'base_dn' => 'dc=hm,dc=med,dc=ec',
            'username' => 'webldap@hm.med.ec',
            // Certificado SSL
            'password' => 'Mwl21@20',

            // Optional Configuration Options
            'use_ssl' => true,
            'use_tls' => false,
            'version' => 3,
            'follow_referrals' => false,

            // Custom LDAP Options
            'options' => [
                // See: http://php.net/ldap_set_option
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
            ],
        ]);

        // Add the connection into the container:
        Container::addConnection($connection);

        $user = User::find('cn=Martin Chang,ou=Sistemas,dc=hm,dc=med,dc=ec');

        $user->unicodepwd = ['Mmach90000', 'M90mach100'];

        try {
            $user->save();
            // User password changed!
        } catch (\LdapRecord\Exceptions\InsufficientAccessException $ex) {
            // The currently bound LDAP user does not
            // have permission to change passwords.
            $error = $ex->getDetailedError();

            return array('status' => false, 'error' => array($error->getErrorCode(), $error->getErrorMessage(), $error->getDiagnosticMessage()));

        } catch (\LdapRecord\Exceptions\ConstraintException $ex) {
            // The users new password does not abide
            // by the domains password policy.

            $error = $ex->getDetailedError();

            return array('status' => false, 'error' => array($error->getErrorCode(), $error->getErrorMessage(), $error->getDiagnosticMessage()));

        } catch (\LdapRecord\LdapRecordException $ex) {
            // Failed changing password. Get the last LDAP
            // error to determine the cause of failure.
            $error = $ex->getDetailedError();

            return array('status' => false, 'error' => array($error->getErrorCode(), $error->getErrorMessage(), $error->getDiagnosticMessage()));

        }

        return $user;

    }

    /**
     * createUserAD
     */
    public function createUserAD()
    {
        global $http;

        // Create a new connection:
        $connection = new Connection([

            'hosts' => ['172.16.2.16'],

            'port' => 636,
            'base_dn' => 'dc=hm,dc=med,dc=ec',
            'username' => 'mchang@hm.med.ec',
            'password' => 'Mmach90000',

            // Optional Configuration Options
            'use_ssl' => true,
            'use_tls' => false,
            'version' => 3,
            'follow_referrals' => false,

            // Custom LDAP Options
            'options' => [
                // See: http://php.net/ldap_set_option
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_ALLOW,
                LDAP_OPT_X_TLS_CERTFILE => "/home/admin/conf/web/ssl.ac.hospitalmetropolitano.org.crt",
                LDAP_OPT_X_TLS_KEYFILE => "/home/admin/conf/web/ssl.ac.hospitalmetropolitano.org.key",
            ],

        ]);

        // Add the connection into the container:
        Container::addConnection($connection);

        $user = (new User)->inside('ou=Sistemas,dc=hm,dc=med,dc=ec');

        $user->cn = 'John Doe';
        $user->unicodePwd = 'Mmach90000';
        $user->samaccountname = 'jdoe';
        $user->userPrincipalName = 'jdoe@hm.med.ec';

        $user->save();

        // Enable the user.
        $user->userAccountControl = 512;

        try {
            $user->save();
        } catch (\LdapRecord\LdapRecordException $e) {
            // Failed saving user.
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
