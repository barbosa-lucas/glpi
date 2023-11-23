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

/**
 * @var \Migration $migration
 * @var \DBmysql $DB
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->fieldExists('glpi_racks', 'users_id')) {
    $migration->addField(
        'glpi_racks',
        'users_id',
        'int',
        [
            'default' => 0,
            'null' => false,
            'after' => 'states_id'
        ]
    );
    $migration->addKey('glpi_racks', 'users_id');
}

if (!$DB->fieldExists('glpi_racks', 'groups_id')) {
    $migration->addField(
        'glpi_racks',
        'groups_id',
        'int',
        [
            'default' => 0,
            'null' => false,
            'after' => 'users_id'
        ]
    );
    $migration->addKey('glpi_racks', 'groups_id');
}
