<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Form\Renderer\FormRenderer;
use Glpi\Http\Firewall;
use Glpi\Http\Response;

/** @var array $CFG_GLPI */

// Real security stategy will be handled a bit later as we need the framework and
// the target form to be loaded.
$SECURITY_STRATEGY = 'no_check';

include('../../inc/includes.php');

/**
 * Endpoint used to display or preview a form.
 */

// Mandatory parameter: id of the form to render
$id = $_GET['id'] ?? 0;
if (!$id) {
    Response::sendError(400, __("Missing or invalid form's id"));
}

// Fetch form
$form = Form::getById($id);
if (!$form) {
    Response::sendError(404, __("Form not found"));
}

$manager = FormAccessControlManager::getInstance();

// Manual session checks depending on the configurated access
// This is a separate step from the `canAnswerForm` step to force each
// access controls to be explicit on this point and avoid opening
// too much access to unauthenticated users by mistake.
$firewall = new Firewall($CFG_GLPI['root_doc']);
if ($manager->canUnauthenticatedUsersAccessForm($form)) {
    $firewall->applyStrategy($_SERVER['PHP_SELF'], Firewall::STRATEGY_NO_CHECK);
} else {
    $firewall->applyStrategy($_SERVER['PHP_SELF'], Firewall::STRATEGY_AUTHENTICATED);
}

// Validate form access
$parameters = new FormAccessParameters(
    session_info: Session::getSessionInfo(),
    url_parameters: $_GET
);
if (!$manager->canAnswerForm($form, $parameters)) {
    Response::sendError(403, __("You are not allowed to answer this form."));
}

// Render the requested form
Html::header(
    $form->fields['name'],
    '',
    'admin',
    Form::getType()
);

$form_renderer = FormRenderer::getInstance();
echo $form_renderer->render($form);

Html::footer();
