<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\Inventory;

use GuzzleHttp;

class Request extends \GLPITestCase
{
    private $http_client;
    private $base_uri;

    public function beforeTestMethod($method)
    {
        global $CFG_GLPI;

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim($CFG_GLPI['url_base'], "/") . "/";

        parent::beforeTestMethod($method);
    }

   /**
    * Check a XML response
    *
    * @param Response $res   Request response
    * @param string   $reply Reply tag contents
    * @param integer  $reply Reply HTTP code
    *
    * @return void
    */
    private function checkXmlResponse(GuzzleHttp\Psr7\Response $res, $reply, $code)
    {
        $this->integer($res->getStatusCode())->isIdenticalTo($code);
        $this->string($res->getHeader('content-type')[0])->isIdenticalTo('application/xml');
        $this->string((string)$res->getBody())
         ->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY>$reply</REPLY>\n");
    }

    public function testUnsupportedHttpMethod()
    {
        $res = $this->http_client->request(
            'GET',
            $this->base_uri . 'front/inventory.php',
            ]
        );
        $this->integer($res->getStatusCode())->isIdenticalTo(405);
        $this->integer($res->getHeader('content-length'))->isIdenticalTo(0);
    }

    public function testUnsupportedLegacyRequest()
    {
        $res = $this->http_client->request(
            'GET',
            $this->base_uri . 'front/inventory.php?action=getConfig',
            ]
        );
        $this->integer($res->getStatusCode())->isIdenticalTo(400);
        $this->string((string)$res->getBody())
         ->isIdenticalTo("{\"status\":\"error\",\"message\":\"Protocol not supported\",\"expiration\":24}");
    }

    public function testRequestInvalidContent()
    {
        $res = $this->http_client->request(
            'POST',
            $this->base_uri . 'front/inventory.php',
            [
            'headers' => [
               'Content-Type' => 'application/xml'
            ]
            ]
        );
        $this->checkXmlResponse($res, '<ERROR>XML not well formed!</ERROR>', 400);
    }

    public function testPrologRequest()
    {
        $res = $this->http_client->request(
            'POST',
            $this->base_uri . 'front/inventory.php',
            [
            'headers' => [
               'Content-Type' => 'application/xml'
            ],
            'body'   => '<?xml version="1.0" encoding="UTF-8" ?>' .
               '<REQUEST>' .
                  '<DEVICEID>mydeviceuniqueid</DEVICEID>' .
                  '<QUERY>PROLOG</QUERY>' .
               '</REQUEST>'
            ]
        );
        $this->checkXmlResponse($res, '<PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE>', 200);
    }
}
