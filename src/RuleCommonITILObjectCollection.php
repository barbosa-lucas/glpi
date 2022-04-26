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

abstract class RuleCommonITILObjectCollection extends RuleCollection
{
    // From RuleCollection
    public $use_output_rule_process_as_next_input   = true;

    /**
     * @param $entity (default 0)
     **/
    public function __construct($entity = 0)
    {
        parent::__construct();
        $this->entity = $entity;
    }

    /**
     * Get the ITIL Object itemtype that this rule collection is for
     * @return string "Ticket", "Change" or "Problem"
     */
    public static function getItemtype(): string
    {
        // Return text between Rule and Collection is the current class name
        $matches = [];
        preg_match('/^Rule(.*)Collection$/', static::class, $matches);
        return $matches[1];
    }

    public static function canView()
    {
        $rule_class = (new static())->getRuleClassName();
        return Session::haveRightsOr(self::$rightname, [READ, $rule_class::PARENT]);
    }

    public function canList()
    {
        return static::canView();
    }

    public function preProcessPreviewResults($output)
    {
        $output = parent::preProcessPreviewResults($output);
        /** @var CommonITILObject $itemtype */
        $itemtype = static::getItemtype();
        return $itemtype::showPreviewAssignAction($output);
    }

    public function showInheritedTab()
    {
        $rule_class = $this->getRuleClassName();
        return (Session::haveRight(self::$rightname, $rule_class::PARENT) && ($this->entity));
    }

    public function showChildrensTab()
    {
        return (Session::haveRight(self::$rightname, READ)
            && (count($_SESSION['glpiactiveentities']) > 1));
    }

    /**
     * @see RuleCollection::prepareInputDataForProcess()
     **/
    public function prepareInputDataForProcess($input, $params)
    {
        $input['_groups_id_of_requester'] = [];
        // Get groups of users
        if (isset($input['_users_id_requester'])) {
            if (!is_array($input['_users_id_requester'])) {
                $requesters = [$input['_users_id_requester']];
            } else {
                $requesters = $input['_users_id_requester'];
            }
            foreach ($requesters as $uid) {
                foreach (Group_User::getUserGroups($uid) as $g) {
                    $input['_groups_id_of_requester'][$g['id']] = $g['id'];
                }
            }
        }

        if (isset($input['itilcategories_id'])) {
            $input['itilcategories_id_cn'] = $input['itilcategories_id'];
        }
        return $input;
    }
}
