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

// Direct access to file
include('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");

Session::checkLoginUser();

// Tech only
if (Session::getCurrentInterface() !== "central") {
    http_response_code(403);
    die;
}

// Read parameter and load pending reason
$pending_reason = PendingReason::getById($_REQUEST['pendingreasons_id'] ?? null);
if (!$pending_reason) {
    http_response_code(400);
    die;
}

echo json_encode([
    'followup_frequency'          => $pending_reason->fields['followup_frequency'],
    'followups_before_resolution' => $pending_reason->fields['followups_before_resolution'],
]);
