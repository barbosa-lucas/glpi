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

/**
 * Is the script launch in Command line?
 *
 * @return boolean
 */
function isCommandLine()
{
    return (PHP_SAPI == 'cli');
}

/**
 * Is the script launched From API?
 *
 * @return boolean
 */
function isAPI()
{
    $script = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
    if (str_contains($script, 'api.php')) {
        return true;
    }
    if (str_contains($script, 'apirest.php')) {
        return true;
    }

    return false;
}

/**
 * Determine if an class name is a plugin one
 *
 * @param string $classname Class name to analyze
 *
 * @return boolean|array False or an array containing plugin name and class name
 */
function isPluginItemType($classname)
{

    /** @var array $matches */
    if (preg_match("/^Plugin([A-Z][a-z0-9]+)([A-Z]\w+)$/", $classname, $matches)) {
        $plug           = [];
        $plug['plugin'] = $matches[1];
        $plug['class']  = $matches[2];
        return $plug;
    } else if (substr($classname, 0, \strlen(NS_PLUG)) === NS_PLUG) {
        $tab = explode('\\', $classname, 3);
        $plug           = [];
        $plug['plugin'] = $tab[1];
        $plug['class']  = $tab[2];
        return $plug;
    }
   // Standard case
    return false;
}

function extractOptionsFromArgv(): array
{
    if (!isset($_SERVER['argv'])) {
        return [];
    }

    // Extract command line arguments
    $options = [];

    $c = count($_SERVER['argv']);
    for ($i = 1; $i < $c; $i++) {
        $chunks = explode('=', $_SERVER['argv'][$i], 2);
        $chunks[0] = preg_replace('/^--/', '', $chunks[0]);
        $options[$chunks[0]] = $chunks[1] ?? true;
    }

    return $options;
}
