<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocrend\Kernel\Helpers;

/**
 * Helper con funciones útiles para trabajar con evío de correos mediante mailer.
 *
 * @author Brayan Narváez <prinick@ocrend.com>
 */

class Views
{

    /**
     * Ruta en la qeu están guardados los templates.
     *
     * @var string
     */
    const TEMPLATES_ROUTE = ___ROOT___ . 'assets/views/api/';

    /**
     * Lista de plantillas.
     *
     * @var array
     */
    const TEMPLATES = [
        'modules.html',
        'login.html',
    ];

    /**
     * Carga una plantilla y sistituye su contenido.
     *
     * @param array $content: Contenido de cada elemento
     * @param int $template: Plantilla seleccionada
     *
     * @return string plantilla llena
     */
    public static function loadTemplate(array $content, int $template): string
    {
        # Verificar que existe la plantilla
        if (!array_key_exists($template, self::TEMPLATES)) {
            throw new \RuntimeException('La plantilla seleccionada no se encuentra.');
        }

        # Cargar contenido
        $tpl = Files::read_file(self::TEMPLATES_ROUTE . self::TEMPLATES[$template]);

        # Reempalzar contenido
        foreach ($content as $index => $html) {
            $tpl = str_replace($index, $html, $tpl);
        }

        return $tpl;
    }

}
