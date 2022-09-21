<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_validatorsubstitutes')) {
    $query = "CREATE TABLE `glpi_validatorsubstitutes` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `users_id` int {$default_key_sign}  NOT NULL DEFAULT '0' COMMENT 'Delegator user',
        `users_id_substitute` int {$default_key_sign}  NOT NULL DEFAULT '0' COMMENT 'Substitute user',
        PRIMARY KEY (`id`),
        UNIQUE KEY `users_id_users_id_substitute` (`users_id`, `users_id_substitute`),
        KEY `users_id_substitute` (`users_id_substitute`)
    ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "add table glpi_validatorsubstitutes");
}

$validation_tables = ['glpi_ticketvalidations', 'glpi_changevalidations'];

foreach ($validation_tables as $validation_table) {
    $migration->addfield(
        $validation_table,
        'users_id_actual_validate',
        "int {$default_key_sign} NOT NULL DEFAULT '0'",
        [
            'after' => 'timeline_position',
            'comment' => 'User who did the validation',
        ]
    );
    $migration->addKey($validation_table, 'users_id_actual_validate');
}

$table = 'glpi_users';
$migration->addField($table, 'substitution_start_date', 'timestamp', ['after' => 'nickname']);
$migration->addField($table, 'substitution_end_date', 'timestamp', ['after' => 'substitution_start_date']);
$migration->addKey($table, 'substitution_end_date');
$migration->addKey($table, 'substitution_start_date');
