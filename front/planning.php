<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

include ('../inc/includes.php');

if (!isset($_GET['genical'])) {
   Session::checkRight("planning", READ);
}

if (!isset($_GET["uID"])) {
   if (($uid = Session::getLoginUserID())
       && !Session::haveRight("planning", Planning::READALL)) {
      $_GET["uID"] = $uid;
   } else {
      $_GET["uID"] = 0;
   }
}

if (!isset($_GET["gID"])) {
   $_GET["gID"] = 0;
}

if (!isset($_GET["limititemtype"])) {
   $_GET["limititemtype"] = "";
}

if (isset($CFG_GLPI['planning_days'])) {
   $CFG_GLPI['planning_days'] =  json_encode($CFG_GLPI['planning_days']);
}

// Normal call via $_GET
if (isset($_GET['checkavailability'])) {
   Html::popHeader(__('Availability'));

   Planning::checkAvailability($_GET);
   Html::popFooter();

} else if (isset($_GET['genical'])) {
   if (isset($_GET['token'])) {
      // Check user token
      $user = new User();
      if ($user->getFromDBByToken($_GET['token'])) {
         if (isset($_GET['entities_id']) && isset($_GET['is_recursive'])) {
            $user->loadMinimalSession($_GET['entities_id'], $_GET['is_recursive']);

            // load entities & profiles
            // needed to pass canViewItem() in populatePlanning functions in case of ical export
            $_SESSION["glpidefault_entity"]  = $user->fields['entities_id'];
            Session::initEntityProfiles($user->getID());
            if (isset($_SESSION['glpiprofiles'][$user->fields['profiles_id']])) {
               Session::changeProfile($user->fields['profiles_id']);

            } else {
               Session::changeProfile(key($_SESSION['glpiprofiles']));
            }
         }
         //// check if the request is valid: rights on uID / gID
         // First check mine : user then groups
         $ismine = false;
         if ($user->getID() == $_GET["uID"]) {
            $ismine = true;
         }
         // Check groups if have right to see
         if (!$ismine && ($_GET["gID"] !== 0)) {
            if ($_GET["gID"] === 'mine') {
               $ismine = true;
            } else {
               $entities = Profile_User::getUserEntitiesForRight($user->getID(),
                                                                 Planning::$rightname,
                                                                 Planning::READGROUP);
               $groups   = Group_User::getUserGroups($user->getID());
               foreach ($groups as $group) {
                  if (($_GET["gID"] == $group['id'])
                      && in_array($group['entities_id'], $entities)) {
                     $ismine = true;
                  }
               }
            }
         }

         $canview = false;
         // If not mine check global right
         if (!$ismine) {
            // First check user
            $entities = Profile_User::getUserEntitiesForRight($user->getID(),
                                                              Planning::$rightname,
                                                              Planning::READALL);
            if ($_GET["uID"]) {
               $userentities = Profile_User::getUserEntities($user->getID());
               $intersect    = array_intersect($entities, $userentities);
               if (count($intersect)) {
                  $canview = true;
               }
            }
            // Else check group
            if (!$canview && $_GET['gID']) {
               $group = new Group();
               if ($group->getFromDB($_GET['gID'])) {
                  if (in_array($group->getEntityID(), $entities)) {
                     $canview = true;
                  }
               }
            }
         }

         if ($ismine || $canview) {
            Planning::generateIcal($_GET["uID"], $_GET["gID"], $_GET["limititemtype"]);
         }
      }
   }
} else {
   Html::header(__('Planning'), $_SERVER['PHP_SELF'], "helpdesk", "planning");

   Session::checkRightsOr('planning', [Planning::READALL, Planning::READMY]);

   if (!isset($_GET["date"]) || empty($_GET["date"])) {
      $_GET["date"] = strftime("%Y-%m-%d");
   }
   if (!isset($_GET["type"])) {
      $_GET["type"] = "week";
   }
   $planning = new Planning();
   $planning->display($_GET);

   Html::footer();
}
