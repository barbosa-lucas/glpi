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

use Glpi\Asset\AssetMenu;

/**
 * @var array $ADDTODISPLAYPREF
 * @var DB $DB
 * @var Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_assets_assetdefinitions')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assetdefinitions` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `system_name` varchar(255) DEFAULT NULL,
            `icon` varchar(255) DEFAULT NULL,
            `comment` text,
            `is_active` tinyint NOT NULL DEFAULT '0',
            `capacities` JSON NOT NULL,
            `profiles` JSON NOT NULL,
            `translations` JSON NOT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            `assets_assetmenus_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE `system_name` (`system_name`),
            KEY `is_active` (`is_active`),
            KEY `date_creation` (`date_creation`),
            KEY `date_mod` (`date_mod`),
            KEY `assets_assetmenus_id` (`assets_assetmenus_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQueryOrDie($query);
} else {
    foreach (['profiles', 'translations'] as $field) {
        $migration->addField('glpi_assets_assetdefinitions', $field, 'JSON NOT NULL', ['update' => "'[]'"]);
    }

    $migration->addField('glpi_assets_assetdefinitions', 'assets_assetmenus_id', 'fkey');
}

$ADDTODISPLAYPREF['Glpi\\Asset\\AssetDefinition'] = [3, 4, 5, 6];

if (!$DB->tableExists('glpi_assets_assets')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assets` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `assets_assetdefinitions_id` int {$default_key_sign} NOT NULL,
            `assets_assetmodels_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `assets_assettypes_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) DEFAULT NULL,
            `comment` text,
            `serial` varchar(255) DEFAULT NULL,
            `otherserial` varchar(255) DEFAULT NULL,
            `contact` varchar(255) DEFAULT NULL,
            `contact_num` varchar(255) DEFAULT NULL,
            `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `groups_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `users_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
            `groups_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
            `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `manufacturers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `states_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `is_deleted` tinyint NOT NULL DEFAULT '0',
            `is_template` tinyint NOT NULL DEFAULT '0',
            `template_name` varchar(255) DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `assets_assetdefinitions_id` (`assets_assetdefinitions_id`),
            KEY `assets_assetmodels_id` (`assets_assetmodels_id`),
            KEY `assets_assettypes_id` (`assets_assettypes_id`),
            KEY `name` (`name`),
            KEY `users_id` (`users_id`),
            KEY `groups_id` (`groups_id`),
            KEY `users_id_tech` (`users_id_tech`),
            KEY `groups_id_tech` (`groups_id_tech`),
            KEY `locations_id` (`locations_id`),
            KEY `manufacturers_id` (`manufacturers_id`),
            KEY `states_id` (`states_id`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`),
            KEY `is_deleted` (`is_deleted`),
            KEY `is_template` (`is_template`),
            KEY `date_creation` (`date_creation`),
            KEY `date_mod` (`date_mod`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQueryOrDie($query);
} else {
    $migration->addField('glpi_assets_assets', 'assets_assetmodels_id', 'fkey');
    $migration->addKey('glpi_assets_assets', 'assets_assetmodels_id');
    $migration->addField('glpi_assets_assets', 'assets_assettypes_id', 'fkey');
    $migration->addKey('glpi_assets_assets', 'assets_assettypes_id');
    $migration->addField('glpi_assets_assets', 'is_template', 'bool');
    $migration->addKey('glpi_assets_assets', 'is_template');
    $migration->addField('glpi_assets_assets', 'template_name', 'string');
}

if (!$DB->tableExists('glpi_assets_assetmodels')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assetmodels` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `assets_assetdefinitions_id` int {$default_key_sign} NOT NULL,
          `name` varchar(255) DEFAULT NULL,
          `comment` text,
          `product_number` varchar(255) DEFAULT NULL,
          `weight` int NOT NULL DEFAULT '0',
          `required_units` int NOT NULL DEFAULT '1',
          `depth` float NOT NULL DEFAULT '1',
          `power_connections` int NOT NULL DEFAULT '0',
          `power_consumption` int NOT NULL DEFAULT '0',
          `is_half_rack` tinyint NOT NULL DEFAULT '0',
          `picture_front` text,
          `picture_rear` text,
          `pictures` text,
          `date_mod` timestamp NULL DEFAULT NULL,
          `date_creation` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `assets_assetdefinitions_id` (`assets_assetdefinitions_id`),
          KEY `name` (`name`),
          KEY `date_mod` (`date_mod`),
          KEY `date_creation` (`date_creation`),
          KEY `product_number` (`product_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQueryOrDie($query);
}

if (!$DB->tableExists('glpi_assets_assettypes')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assettypes` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `assets_assetdefinitions_id` int {$default_key_sign} NOT NULL,
          `name` varchar(255) DEFAULT NULL,
          `comment` text,
          `date_mod` timestamp NULL DEFAULT NULL,
          `date_creation` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `assets_assetdefinitions_id` (`assets_assetdefinitions_id`),
          KEY `name` (`name`),
          KEY `date_mod` (`date_mod`),
          KEY `date_creation` (`date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQueryOrDie($query);
}

if (!$DB->tableExists('glpi_assets_assetmenus')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assetmenus` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `assets_assetmenus_id` int unsigned NOT NULL DEFAULT '1',
            `name` varchar(255) DEFAULT NULL,
            `completename` text,
            `comment` text,
            `level` int NOT NULL DEFAULT '0',
            `ancestors_cache` longtext,
            `sons_cache` longtext,
            `date_mod` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`entities_id`,`assets_assetmenus_id`,`name`),
            KEY `name` (`name`),
            KEY `is_recursive` (`is_recursive`),
            KEY `date_mod` (`date_mod`),
            KEY `date_creation` (`date_creation`),
            KEY `assets_assetmenus_id` (`assets_assetmenus_id`),
            KEY `level` (`level`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQueryOrDie($query);
}

$reserved_menus = AssetMenu::reservedEntries();
foreach ($reserved_menus as $reserved_id => $reserved_name) {
    $query = "REPLACE INTO `glpi_assets_assetmenus`
                       (`id`, `name`) VALUES ($reserved_id, '$reserved_name')";
    $DB->doQueryOrDie($query);
}

// Dev migration
// Convert profile rights in glpi_assets_assetdefinitions from an array to OR'd integer like we use in regular glpi_profilerights table
// TODO Remove before releasing GLPI 11.0 beta.
$it = $DB->request([
    'SELECT' => ['id', 'profiles'],
    'FROM'   => 'glpi_assets_assetdefinitions'
]);
foreach ($it as $data) {
    $profiles = json_decode($data['profiles'], true);
    $changed = false;
    if (is_array($profiles)) {
        foreach ($profiles as $profile_id => $rights) {
            if (is_array($rights)) {
                $new_value = 0;
                foreach ($rights as $right => $is_enabled) {
                    if ($is_enabled) {
                        $new_value |= (int)$right;
                    }
                }
                $profiles[$profile_id] = $new_value;
                $changed = true;
            }
        }
    }
    if ($changed) {
        $DB->update('glpi_assets_assetdefinitions', [
            'id'       => $data['id'],
            'profiles' => json_encode($profiles)
        ], [
            'id' => $data['id']
        ]);
    }
}

$assignable_asset_rights = [
    'computer', 'monitor', 'software', 'networking', 'printer',
    'cartridge', 'consumable', 'phone', 'peripheral'
];
foreach ($assignable_asset_rights as $rightname) {
    $migration->addRight($rightname, READ_ASSIGNED, [$rightname => READ]);
    $migration->addRight($rightname, UPDATE_ASSIGNED, [$rightname => UPDATE]);
}
