<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

namespace Glpi\Console\Marketplace;

use Glpi\Console\AbstractCommand;
use Glpi\Marketplace\Api\Plugins;
use Glpi\Marketplace\Controller;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:marketplace:search');
        $this->setAliases(['marketplace:search']);
        $this->setDescription('Search GLPI marketplace');

        $this->addArgument('term', InputArgument::OPTIONAL, 'The search term');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $CFG_GLPI;

        $term = $input->getArgument('term');
        $term = strtolower($term);

        $controller = new Controller();
        $plugins = $controller::getAPI()->getAllPlugins();

        if (!empty($term)) {
            $result = array_filter($plugins, static function ($plugin) use ($term) {
                if (stripos($plugin['key'], $term)) {
                    return true;
                }
                if (stripos($plugin['name'], $term)) {
                    return true;
                }

                foreach ($plugin['descriptions'] as $description) {
                    if (isset($description['short_description']) && stripos($description['short_description'], $term)) {
                        return true;
                    }
                    if (isset($description['long_description']) && stripos($description['long_description'], $term)) {
                        return true;
                    }
                }
                return false;
            });
        } else {
            $result = $plugins;
        }

        // Output table of results with the key, name, and short description (user's language, default en, or which ever description is first
        $rows = [];
        $lang = strtolower($_SESSION['glpilanguage']);
        $main_lang = explode('_', $lang)[0];
        foreach ($result as $plugin) {
            $short_description = $plugin['descriptions'][0]['short_description'] ?? __('No description');
            foreach ($plugin['descriptions'] as $description) {
                $desc_main_lang = explode('_', strtolower($description['lang']))[0];
                if ($desc_main_lang === $main_lang) {
                    $short_description = $description['short_description'];
                    // Do not break here because this description doesn't match the full language
                }
                if (stripos($description['lang'], $lang) === 0) {
                    $short_description = $description['short_description'];
                    break;
                }
            }
            $rows[] = [
                'key' => $plugin['key'],
                'name' => $plugin['name'],
                'description' => $short_description,
            ];
        }
        $table = new Table($output);
        $table->setHeaders([__('Key'), __('Name'), __('Description')]);
        $table->setRows($rows);
        $table->render();

        return 0; // Success
    }
}
