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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Item_RemoteManagement extends CommonDBChild {

   static public $itemtype        = 'itemtype';
   static public $items_id        = 'items_id';
   public $dohistory              = true;

   public const TEAMVIEWER = 'teamviewer';
   public const LITEMANAGER = 'litemanager';
   public const ANYDESK = 'anydesk';


   static function getTypeName($nb = 0) {
      return __('Remote management');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nb = 0;
      switch ($item->getType()) {
         default:
            if ($_SESSION['glpishow_count_on_tabs']) {

               $nb = countElementsInTable(
                  self::getTable(), [
                     'items_id'     => $item->getID(),
                     'itemtype'     => $item->getType()
                  ]
               );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showForItem($item, $withtemplate);
   }


   /**
    * Get remote managements related to a given item
    *
    * @param CommonDBTM $item  Item instance
    * @param string     $sort  Field to sort on
    * @param string     $order Sort order
    *
    * @return DBmysqlIterator
    */
   public static function getFromItem(CommonDBTM $item, $sort = null, $order = null): DBmysqlIterator {
      global $DB;

      $iterator = $DB->request([
         'FROM'      => self::getTable(),
         'WHERE'     => [
            'itemtype'     => $item->getType(),
            'items_id'     => $item->fields['id']
         ]
      ]);
      return $iterator;
   }

   /**
    * Print the remote management
    *
    * @param CommonDBTM $item          Item object
    * @param boolean    $withtemplate  Template or basic item (default 0)
    *
    * @return void
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      $ID = $item->fields['id'];
      $itemtype = $item->getType();

      if (!$item->getFromDB($ID)
          || !$item->can($ID, READ)) {
         return false;
      }

      $canedit = $item->canEdit($ID);

      if ($canedit
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='".self::getFormURL()."?itemtype=$itemtype&items_id=$ID&amp;withtemplate=".
                  $withtemplate."'>";
         echo __('Add a remote management');
         echo "</a></div>\n";
      }

      $get = ['withtemplate' => $withtemplate] + $_GET;
      $item->showSublist(self::getType(), $get);
   }


   /**
    * Get remote management system link
    *
    * @return string
    */
   public function getRemoteLink(): string {
      $link= '<a href="%s" target="_blank">%s</a>';
      $id = Html::entities_deep($this->fields['remoteid']);
      $href = null;
      switch ($this->fields['type']) {
         case self::TEAMVIEWER:
            $href = "https://start.teamviewer.com/$id";
            break;
         case self::ANYDESK:
            $href = "anydesk:$id";
            break;
      }

      if ($href === null) {
         return $id;
      } else {
         return sprintf(
            $link,
            $href,
            $id
         );
      }
   }

   function rawSearchOptions() {

      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'remoteid',
         'name'               => __('ID'),
         'datatype'           => 'specific',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'type',
         'name'               => _n('Type', 'Types', 1),
         'datatype'           => 'string',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      return $tab;
   }

   public static function rawSearchOptionsToAdd($itemtype) {
      $tab = [];

      $name = self::getTypeName(Session::getPluralNumber());
      $tab[] = [
          'id'                 => 'remote_management',
          'name'               => $name
      ];

      $tab[] = [
         'id'                 => '180',
         'table'              => self::getTable(),
         'field'              => 'remoteid',
         'name'               => __('ID'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'specific',
      ];

      $tab[] = [
         'id'                 => '181',
         'table'              => self::getTable(),
         'field'              => 'type',
         'name'               => _n('Type', 'Types', 1),
         'forcegroupby'       => true,
         'width'              => 1000,
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item'
         ]
      ];

      return $tab;
   }


   function showForm($ID, $options = []) {
      $itemtype = null;
      if (isset($options['itemtype']) && !empty($options['itemtype'])) {
         $itemtype = $options['itemtype'];
      } else if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
      } else {
         throw new \RuntimeException('Unable to retrieve itemtype');
      }

      if (!Session::haveRight($itemtype::$rightname, READ)) {
         return false;
      }

      $item = new $itemtype();
      if ($ID > 0) {
         $this->check($ID, READ);
         $item->getFromDB($this->fields['items_id']);
      } else {
         $this->check(-1, CREATE, $options);
         $item->getFromDB($options['items_id']);
      }

      $this->showFormHeader($options);

      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='items_id' value='".$options['items_id']."'>";
         echo "<input type='hidden' name='itemtype' value='".$options['itemtype']."'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Item', 'Items', 1)."</td>";
      echo "<td>".$item->getLink()."</td>";
      echo "<td>".__('Automatic inventory')."</td>";
      echo "<td>";
      if ($ID && $this->fields['is_dynamic']) {
         echo __('Yes');
      } else {
         echo __('No');
      }
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Remote ID')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "remoteid");
      echo "</td><td>"._n('Type', 'Types', 1)."</td>";
      $types = [
         self::TEAMVIEWER => 'TeamViewer',
         self::LITEMANAGER => 'LiteManager',
         self::ANYDESK => 'AnyDesk'
      ];
      echo "<td>";
      echo Dropdown::showFromArray(
         'type',
         $types, [
            'value'   => $this->fields['type'],
            'display' => false
         ]
      );
      echo "</td></tr>";

      $itemtype = $this->fields['itemtype'];
      $options['canedit'] = Session::haveRight($itemtype::$rightname, UPDATE);
      $this->showFormButtons($options);

      return true;
   }

   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if ($options['searchopt']['datatype'] !== 'specific') {
         return;
      }

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'remoteid':
            $mgmt = new self;
            $mgmt->getFromDB($options['raw_data']['id']);
            return $mgmt->getRemoteLink();
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

}
