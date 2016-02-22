<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("config", UPDATE);

$authSAML = new AuthSAML();

//Update SAML configuration
if (isset($_POST["update"])) {
   $_POST['id'] = 1;
   $authSAML->update($_POST);
   Html::back();
}

if (isset($_GET['metadata'])) {
   $authSAML->generate_metadata();
   exit;
}

Html::header(__('SAML authentication'), $_SERVER['PHP_SELF'], "config", "auth", "saml");

$authSAML->display(array('id' => 1));

Html::footer();
?>