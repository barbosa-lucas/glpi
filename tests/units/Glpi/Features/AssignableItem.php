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

namespace tests\units\Glpi\Features;

use Group_Item;

class AssignableItem extends \DbTestCase
{
    protected function itemtypeProvider(): iterable
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        foreach ($CFG_GLPI['assignable_types'] as $itemtype) {
            yield[
                'class' => $itemtype,
            ];
        }
    }

    /**
     * @dataProvider itemtypeProvider
     */
    public function testClassUsesTrait(string $class): void
    {
        $this->boolean(in_array(\Glpi\Features\AssignableItem::class, class_uses($class, true)));
    }

    protected function groupAssignableItemtypeProvider(): iterable
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        foreach ($CFG_GLPI['assignable_types'] as $itemtype) {
            yield[
                'class' => $itemtype,
                'type'  => Group_Item::GROUP_TYPE_NORMAL,
            ];

            yield[
                'class' => $itemtype,
                'type'  => Group_Item::GROUP_TYPE_TECH,
            ];
        }
    }

    /**
     * Test adding an item with the groups_id/groups_id_tech field as an array and null.
     * Test updating an item with the groups_id/groups_id_tech field as an array and null.
     *
     * @dataProvider groupAssignableItemtypeProvider
     */
    public function testAddAndUpdateMultipleGroups(string $class, int $type): void
    {
        $this->login(); // login to bypass some rights checks (e.g. on domain records)

        $input = $this->getMinimalCreationInput($class);

        $field = match ($type) {
            Group_Item::GROUP_TYPE_NORMAL => 'groups_id',
            Group_Item::GROUP_TYPE_TECH   => 'groups_id_tech',
        };

        $item_1 = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__ . ' 1',
                $field                 => [1, 2],
            ]
        );
        $this->array($item_1->fields[$field])->isEqualTo([1, 2]);

        $item_2 = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__ . ' 2',
                $field                 => null,
            ]
        );
        $this->array($item_2->fields[$field])->isEmpty();

        // Update both items. Asset 1 will have the groups set to null and item 2 will have the groups set to an array.
        $this->boolean($item_1->update(['id' => $item_1->getID(), $field => null]))->isTrue();
        $this->array($item_1->fields[$field])->isEmpty();

        $this->boolean($item_2->update(['id' => $item_2->getID(),$field => [5, 6]]))->isTrue();
        $this->array($item_2->fields[$field])->isEqualTo([5, 6]);

        // Test updating array to array
        $this->boolean($item_2->update(['id' => $item_2->getID(), $field => [1, 2]]))->isTrue();
        $this->array($item_2->fields[$field])->isEqualTo([1, 2]);
    }

    /**
     * Test the loading item which still have integer values for groups_id/groups_id_tech (0 for no group).
     * The value should be automatically normalized to an array. If the group was '0', the array should be empty.
     *
     * @dataProvider groupAssignableItemtypeProvider
     */
    public function testLoadGroupsFromDb(string $class, int $type): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $input = $this->getMinimalCreationInput($class);

        $field = match ($type) {
            Group_Item::GROUP_TYPE_NORMAL => 'groups_id',
            Group_Item::GROUP_TYPE_TECH   => 'groups_id_tech',
        };

        $item = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__,
            ]
        );
        $this->array($item->fields[$field])->isEmpty();

        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 2,
                'type'      => $type,
            ],
        );

        $this->boolean($item->getFromDB($item->getID()))->isTrue();
        $this->array($item->fields[$field])->isEqualTo([2]);

        $DB->insert(
            'glpi_groups_items',
            [
                'itemtype'  => $class,
                'items_id'  => $item->getID(),
                'groups_id' => 3,
                'type'      => $type,
            ],
        );
        $this->boolean($item->getFromDB($item->getID()))->isTrue();
        $this->array($item->fields[$field])->isEqualTo([2, 3]);
    }

    /**
     * An empty item should have the groups_id/groups_id_tech fields initialized as an empty array.
     *
     * @dataProvider groupAssignableItemtypeProvider
     */
    public function testGetEmpty(string $class, int $type): void
    {
        $field = match ($type) {
            Group_Item::GROUP_TYPE_NORMAL => 'groups_id',
            Group_Item::GROUP_TYPE_TECH   => 'groups_id_tech',
        };

        $item = new $class();
        $this->boolean($item->getEmpty())->isTrue();
        $this->array($item->fields[$field])->isEmpty();
    }

    /**
     * Check that adding and updating an item with groups_id/groups_id_tech as an integer still works (minor BC, mainly for API scripts).
     *
     * @dataProvider groupAssignableItemtypeProvider
     */
    public function testAddUpdateWithIntGroups(string $class, int $type): void
    {
        $this->login(); // login to bypass some rights checks (e.g. on domain records)

        $input = $this->getMinimalCreationInput($class);

        $field = match ($type) {
            Group_Item::GROUP_TYPE_NORMAL => 'groups_id',
            Group_Item::GROUP_TYPE_TECH   => 'groups_id_tech',
        };

        $item = $this->createItem(
            $class,
            $input + [
                $class::getNameField() => __FUNCTION__,
                $field                 => 1,
            ],
            [$field] // ignore the field as it will be transformed to an array
        );
        $this->array($item->fields[$field])->isEqualTo([1]);

        $this->boolean($item->update(['id' => $item->getID(), $field => 2]))->isTrue();
        $this->array($item->fields[$field])->isEqualTo([2]);
    }
}
