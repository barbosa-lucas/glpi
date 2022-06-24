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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\Sanitizer;

/**
 * Process Class
 **/
class Item_Process extends CommonDBChild
{
   // From CommonDBChild
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public $dohistory       = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Process', 'Processes', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item::canView()) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    [
                        'items_id'     => $item->getID(),
                        'itemtype'     => $item->getType(),
                    ]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        self::showForItem($item, $withtemplate);
        return true;
    }


    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        $itemtype = $item->getType();
        $items_id = $item->getField('id');

        $start       = intval($_GET["start"] ?? 0);
        $sort        = $_GET["sort"] ?? "";
        $order       = strtoupper($_GET["order"] ?? "");
        $filters     = $_GET['filters'] ?? [];
        $is_filtered = count($filters) > 0;
        $sql_filters = self::convertFiltersValuesToSqlCriteria($filters);

        if (strlen($sort) == 0) {
            $sort = "id";
        }
        if (strlen($order) == 0) {
            $order = "ASC";
        }

        $all_data = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => [
                'items_id' => $items_id,
                'itemtype' => $itemtype
            ]
        ]);
        $all_data = iterator_to_array($all_data);
        $filtered_data = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => [
                'items_id' => $items_id,
                'itemtype' => $itemtype
            ] + $sql_filters,
            'START' => $start,
            'ORDER' => "$sort $order",
        ]);

        $total_number = count($all_data);
        $filtered_number = count($filtered_data);

        $processes = [];
        $i = 0;
        foreach ($filtered_data as $process) {
            $process['virtualmemory'] = $process['virtualmemory'] * 1024;
            $processes[$process['id']] = $process;

            $i++;

            if ($i >= $_SESSION['glpilist_limit']) {
                break;
            }
        }

        $users = array_unique(array_column($all_data, 'user'));
        $users = array_combine($users, $users);

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'sort' => $sort,
            'order' => $order,
            'href' => $item::getFormURLWithID($items_id),
            'additional_params' => $is_filtered ? http_build_query(['filters' => $filters]) : "",
            'is_tab' => true,
            'items_id' => $items_id,
            'filters' => Sanitizer::dbEscapeRecursive($filters),
            'columns' => [
                'id'            => __("ID"),
                'cmd'           => __("Command"),
                'cpuusage'      => __("CPU Usage"),
                'memusage'      => __("Memory Usage"),
                'started'       => __("Started at"),
                'tty'           => __("TTY"),
                'user'          => __("User"),
                'virtualmemory' => __("Virtual memory"),
            ],
            'columns_values' => [
                'user' => $users,
            ],
            'formatters' => [
                'cmd'           => 'longtext',
                'cpuusage'      => 'progress',
                'memusage'      => 'progress',
                'started'       => 'datetime',
                'user'          => 'array',
                'virtualmemory' => 'bytesize',
            ],
            'entries' => $processes,
            'total_number' => $total_number,
            'filtered_number' => $filtered_number,
        ]);
    }


    public static function convertFiltersValuesToSqlCriteria(array $filters = []): array
    {
        $sql_filters = [];

        $basic_filters = [
            'id',
            'cmd',
            'cpuusage',
            'memusage',
            'tty',
            'virtualmemory',
        ];

        foreach ($basic_filters as $filter_key) {
            if (strlen(($filters[$filter_key] ?? ""))) {
                $sql_filters[$filter_key] = ['LIKE', '%' . $filters[$filter_key] . '%'];
            }
        }

        if (isset($filters['user']) && !empty($filters['user'])) {
            $sql_filters['user'] = $filters['user'];
        }

        if (isset($filters['started']) && !empty($filters['started'])) {
            $sql_filters[] = [
                ['started' => ['>=', "{$filters['started']} 00:00:00"]],
                ['started' => ['<=', "{$filters['started']} 23:59:59"]],
            ];
        }

        return $sql_filters;
    }


    public static function getIcon()
    {
        return "ti ti-bolt";
    }
}
