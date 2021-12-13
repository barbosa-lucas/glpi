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

/// Class DevicePowerSupply
class DevicePowerSupply extends CommonDevice
{

    protected static $forward_entity_to = ['Item_DevicePowerSupply', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Power supply', 'Power supplies', $nb);
    }


    public function getAdditionalFields()
    {

        return array_merge(
            parent::getAdditionalFields(),
            [['name'  => 'is_atx',
                                     'label' => __('ATX'),
                                     'type'  => 'bool'],
                               ['name'  => 'power',
                                     'label' => __('Power'),
                                     'type'  => 'text'],
                               ['name'  => 'devicepowersupplymodels_id',
                                     'label' => _n('Model', 'Models', 1),
            'type'  => 'dropdownValue']]
        );
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'is_atx',
         'name'               => __('ATX'),
         'datatype'           => 'bool'
        ];

        $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'power',
         'name'               => __('Power'),
         'datatype'           => 'string',
        ];

        $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicepowersupplymodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        HTMLTableSuperHeader $super = null,
        HTMLTableHeader $father = null,
        array $options = []
    ) {

        $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

        if ($column == $father) {
            return $father;
        }

        switch ($itemtype) {
            case 'Computer':
                Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
                break;
        }
    }


    public function getHTMLTableCellForItem(
        HTMLTableRow $row = null,
        CommonDBTM $item = null,
        HTMLTableCell $father = null,
        array $options = []
    ) {

        $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

        if ($column == $father) {
            return $father;
        }

        switch ($item->getType()) {
            case 'Computer':
                Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
        }
    }

    public static function rawSearchOptionsToAdd($itemtype, $main_joinparams)
    {
        $tab = [];

        $tab[] = [
         'id'                 => '39',
         'table'              => 'glpi_devicepowersupplies',
         'field'              => 'designation',
         'name'               => static::getTypeName(1),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicepowersupplies',
               'joinparams'         => $main_joinparams
            ]
         ]
        ];

        return $tab;
    }


    public static function getIcon()
    {
        return "ti ti-bolt";
    }
}
