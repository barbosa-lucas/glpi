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

namespace tests\units\Glpi\ContentTemplates\Parameters;

class ProblemParameters extends AbstractParameters
{
    public function testGetValues(): void
    {
        $this->login();
        $test_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $this->createItem('ITILCategory', [
            'name' => 'category_testGetValues'
        ]);

        $itilcategories_id = getItemByTypeName('ITILCategory', 'category_testGetValues', true);

        $observer_groups_id1 = getItemByTypeName('Group', '_test_group_1', true);
        $observer_groups_id2 = getItemByTypeName('Group', '_test_group_2', true);
        $assigned_users_id   = getItemByTypeName('User', 'tech', true);

        $this->createItem('Problem', [
            'name'                  => 'problem_testGetValues',
            'content'               => '<p>problem_testGetValues content</p>',
            'entities_id'           => $test_entity_id,
            'date'                  => '2021-07-19 17:11:28',
            'itilcategories_id'     => $itilcategories_id,
            '_groups_id_observer'   => [$observer_groups_id1, $observer_groups_id2],
            '_users_id_assign'      => [$assigned_users_id],
        ]);

        $problems_id = getItemByTypeName('Problem', 'problem_testGetValues', true);

        $parameters = $this->newTestedInstance();
        $values = $parameters->getValues(getItemByTypeName('Problem', 'problem_testGetValues'));
        $this->array($values)->isEqualTo([
            'id'        => $problems_id,
            'ref'       => "#$problems_id",
            'link'      => "<a  href='/glpi/front/problem.form.php?id=$problems_id'  title=\"problem_testGetValues\">problem_testGetValues</a>",
            'name'      => 'problem_testGetValues',
            'content'   => '<p>problem_testGetValues content</p>',
            'date'      => '2021-07-19 17:11:28',
            'solvedate' => null,
            'closedate' => null,
            'status'    => 'Processing (assigned)',
            'urgency'   => 'Medium',
            'impact'    => 'Medium',
            'priority'  => 'Medium',
            'entity'    => [
                'id'           => $test_entity_id,
                'name'         => '_test_child_2',
                'completename' => 'Root entity > _test_root_entity > _test_child_2',
            ],
            'itilcategory' => [
                'id'           => $itilcategories_id,
                'name'         => 'category_testGetValues',
                'completename' => 'category_testGetValues',
            ],
            'requesters' => [
                'users'  => [],
                ],
                'groups' => [],
            ],
            'observers' => [
                'users'  => [],
                'groups' => [
                    [
                        'id'           => $observer_groups_id1,
                        'name'         => '_test_group_1',
                        'completename' => '_test_group_1',
                    ],
                    [
                        'id'           => $observer_groups_id2,
                        'name'         => '_test_group_2',
                        'completename' => '_test_group_1 > _test_group_2',
                    ],
                ],
            ],
            'assignees' => [
                'users'     => [
                    [
                        'id'       => $assigned_users_id,
                        'login'    => 'tech',
                        'fullname' => 'tech',
                        'email'    => '',
                        'phone'    => null,
                        'phone2'   => null,
                        'mobile'   => null,
                        'firstname'  => null,
                        'realname'   => null,
                        'used_items' => [],
                    ],
                ],
                'groups'    => [],
                'suppliers' => [],
            ],
        ]);

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
