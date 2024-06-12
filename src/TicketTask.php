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

class TicketTask extends CommonITILTask
{
    public static $rightname = 'task';


    public static function getTypeName($nb = 0)
    {
        return _n('Ticket task', 'Ticket tasks', $nb);
    }


    public static function canCreate(): bool
    {
        return (Session::haveRightsOr(
            self::$rightname,
            [
                parent::ADDALLITEM,
                parent::ADDGROUPTICKET,
                parent::ADDMYTICKET,
                parent::ADD_AS_OBSERVER,
                parent::ADD_AS_TECHNICIAN
            ]
        )
        || Session::haveRight('ticket', Ticket::OWN));
    }

    public static function canUpdate(): bool
    {
        return (Session::haveRightsOr(self::$rightname, [parent::UPDATEALL, parent::UPDATEMY]));
    }


    public function canViewPrivates()
    {
        return Session::haveRight(self::$rightname, parent::SEEPRIVATE);
    }


    public function canEditAll()
    {
        return Session::haveRight(self::$rightname, parent::UPDATEALL);
    }


    /**
     * Does current user have right to show the current task?
     *
     * @return boolean
     **/
    public function canViewItem(): bool
    {

        if (!$this->canReadITILItem()) {
            return false;
        }

        if (Session::haveRight(self::$rightname, parent::SEEPRIVATE)) {
            return true;
        }

        if (
            !$this->fields['is_private']
            && Session::haveRight(self::$rightname, parent::SEEPUBLIC)
        ) {
            return true;
        }

       // see task created or affected to me
        if (
            Session::getCurrentInterface() == "central"
            && ($this->fields["users_id"] === Session::getLoginUserID())
              || ($this->fields["users_id_tech"] === Session::getLoginUserID())
        ) {
            return true;
        }

        if (
            $this->fields["groups_id_tech"] && ($this->fields["groups_id_tech"] > 0)
            && isset($_SESSION["glpigroups"])
            && in_array($this->fields["groups_id_tech"], $_SESSION["glpigroups"])
        ) {
            return true;
        }

        return false;
    }


    /**
     * Does current user have right to create the current task?
     *
     * @return boolean
     **/
    public function canCreateItem(): bool
    {
        if (!$this->canReadITILItem()) {
            return false;
        }

        $ticket = new Ticket();
        $ticket->getFromDB($this->fields['tickets_id']);

        return $ticket->canAddTasks();
    }


    /**
     * Does current user have right to update the current task?
     *
     * @return boolean
     **/
    public function canUpdateItem(): bool
    {

        if (!$this->canReadITILItem()) {
            return false;
        }

        $ticket = new Ticket();
        if (
            $ticket->getFromDB($this->fields['tickets_id'])
            && in_array($ticket->fields['status'], $ticket->getClosedStatusArray())
        ) {
            return false;
        }

        if (
            (($this->fields["users_id"] != Session::getLoginUserID())
            && !Session::haveRight(self::$rightname, parent::UPDATEALL))
            || ($this->fields["users_id"] == Session::getLoginUserID()
            && !Session::haveRight(self::$rightname, parent::UPDATEMY))
        ) {
            return false;
        }

        return true;
    }


    /**
     * Does current user have right to purge the current task?
     *
     * @return boolean
     **/
    public function canPurgeItem(): bool
    {
        $ticket = new Ticket();
        if (
            $ticket->getFromDB($this->fields['tickets_id'])
            && in_array($ticket->fields['status'], $ticket->getClosedStatusArray())
        ) {
            return false;
        }

        return Session::haveRight(self::$rightname, PURGE);
    }


    /**
     * Populate the planning with planned ticket tasks
     *
     * @param $options   array of possible options:
     *    - who          ID of the user (0 = undefined)
     *    - whogroup     ID of the group of users (0 = undefined)
     *    - begin        Date
     *    - end          Date
     *
     * @return array of planning item
     **/
    public static function populatePlanning($options = []): array
    {
        return parent::genericPopulatePlanning(__CLASS__, $options);
    }


    /**
     * Populate the planning with planned ticket tasks
     *
     * @param $options   array of possible options:
     *    - who          ID of the user (0 = undefined)
     *    - whogroup     ID of the group of users (0 = undefined)
     *    - begin        Date
     *    - end          Date
     *
     * @return array of planning item
     **/
    public static function populateNotPlanned($options = []): array
    {
        return parent::genericPopulateNotPlanned(__CLASS__, $options);
    }


    /**
     * Display a Planning Item
     *
     * @param array           $val       array of the item to display
     * @param integer         $who       ID of the user (0 if all)
     * @param string          $type      position of the item in the time block (in, through, begin or end)
     * @param integer|boolean $complete  complete display (more details)
     *
     * @return string
     */
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {
        return parent::genericDisplayPlanningItem(__CLASS__, $val, $who, $type, $complete);
    }


    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[UPDATE], $values[CREATE], $values[READ]);

        if ($interface == 'central') {
            $values[parent::UPDATEALL]      = __('Update all');
            $values[parent::ADDALLITEM  ]   = __('Add to all tickets');
            $values[parent::SEEPRIVATE]     = __('See private ones');
        }

        $values[self::ADDGROUPTICKET]
                                     = ['short' => __('Add (associated groups)'),
                                         'long'  => __('Add to tickets of associated groups')
                                     ];
        $values[self::UPDATEMY]    = __('Update (author)');
        $values[self::ADDMYTICKET] = ['short' => __('Add (requester)'),
            'long'  => __('Add to tickets (requester)')
        ];
        $values[self::ADD_AS_OBSERVER] = ['short' => __('Add (observer)'),
            'long'  => __('Add to tickets (observer)')
        ];
        $values[self::ADD_AS_TECHNICIAN] = ['short' => __('Add (technician)'),
            'long'  => __('Add to tickets (technician)')
        ];
        $values[parent::SEEPUBLIC]   = __('See public ones');

        if ($interface == 'helpdesk') {
            unset($values[PURGE]);
        }

        return $values;
    }

    /**
     * Build parent condition for search
     *
     * @return string
     */
    public static function buildParentCondition()
    {
        return "(0 = 1 " . Ticket::buildCanViewCondition("tickets_id") . ") ";
    }
}
