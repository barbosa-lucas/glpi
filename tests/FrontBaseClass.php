<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Toolbox\Sanitizer;
use Symfony\Component\BrowserKit\HttpBrowser;

class FrontBaseClass extends \GLPITestCase
{
    protected HttpBrowser $http_client;
    protected string $base_uri;

    public function beforeTestMethod($method)
    {
        global $CFG_GLPI, $DB;

        $this->http_client = new HttpBrowser();
        $this->base_uri    = trim($CFG_GLPI['url_base'], "/") . "/";

        $DB->delete(\Computer::getTable(), ['uuid' => 'thetestuuidtoremove']);
        $DB->delete(\Ticket::getTable(), ['name' => ['LIKE', '%thetestuuidtoremove']]);

        parent::beforeTestMethod($method);
    }

    public function afterTestMethod($method)
    {
        global $DB;
        $DB->delete(\Computer::getTable(), ['uuid' => 'thetestuuidtoremove']);
        $DB->delete(\Ticket::getTable(), ['name' => ['LIKE', '%thetestuuidtoremove']]);

        parent::afterTestMethod($method);
    }

    protected function logIn()
    {
        $crawler = $this->http_client->request('GET', $this->base_uri . 'index.php');
        $login_name = $crawler->filter('#login_name')->attr('name');
        $pass_name = $crawler->filter('input[type=password]')->attr('name');
        $form = $crawler->selectButton('submit')->form();
        $form[$login_name] = TU_USER;
        $form[$pass_name] = TU_PASS;
        //proceed form submission
        $crawler = $this->http_client->submit($form);

        //once logged in, we reach standard interface
        $page_title = $crawler->filter('title')->text();
        $this->string($page_title)->isIdenticalTo('Standard interface - GLPI');
    }
}
