<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/dropdown.class.php */

class DropdownTest extends DbTestCase {

   /**
    * @covers Dropdown::showLanguages
    */
   public function testShowLanguages() {

      $opt = [ 'display_emptychoice' => true, 'display' => false ];
      $out = Dropdown::showLanguages('dropfoo', $opt);
      $this->assertContains("name='dropfoo'", $out);
      $this->assertContains("value='' selected", $out);
      $this->assertNotContains("value='0'", $out);
      $this->assertContains("value='fr_FR'", $out);

      $opt = [ 'display' => false, 'value' => 'cs_CZ', 'rand' => '1234' ];
      $out = Dropdown::showLanguages('language', $opt);
      $this->assertNotContains("value=''", $out);
      $this->assertNotContains("value='0'", $out);
      $this->assertContains("name='language' id='dropdown_language1234", $out);
      $this->assertContains("value='cs_CZ' selected", $out);
      $this->assertContains("value='fr_FR'", $out);
   }

   public function dataTestImport() {
      return [
            // input,             name,  return,  message
            [ [ ],                '',    -1,      'missing name'],
            [ [ 'name' => ''],    '',    -1,      'empty name'],
            [ [ 'name' => ' '],   '',    -1,      'space name'],
            [ [ 'name' => ' a '], 'a',   'int',   'simple name'],
            [ [ 'name' => 'foo'], 'foo', 'int',   'simple name'],
      ];
   }

   /**
    * @covers Dropdown::import
    * @covers CommonDropdown::import
    * @dataProvider dataTestImport
    */
   public function testImport($input, $result, $return, $msg) {
      $id = Dropdown::import('UserTitle', $input);
      if ($return == 'false') {
         $this->assertFalse($id, $msg);
      } else if ($return == -1) {
         $this->assertEquals(-1, $id, $msg);
      } else if ($return == 'int') {
         $this->assertGreaterThan(0, $id, $msg);
      }
      if ($result) {
         $ut = new UserTitle();
         $this->assertTrue($ut->getFromDB($id), $msg);
         $this->assertEquals($result, $ut->getField('name'), $msg);
      }
   }

   public function dataTestTreeImport() {
      return [
            // input,                                                       name,    completename, return,  message
            [ [ 'entities_id' => 0],                                        '',     '',           -1,      'missing name'],
            [ [ 'name' => '', 'entities_id' => 0],                          '',     '',           -1,      'empty name'],
            [ [ 'name' => ' ', 'entities_id' => 0],                         '',     '',           -1,      'space name'],
            [ [ 'name' => ' a ', 'entities_id' => 0],                       'a',    'a',          'int',   'simple name'],
            [ [ 'name' => 'foo', 'entities_id' => 0],                       'foo',  'foo',        'int',   'simple name'],
            [ [ 'completename' => 'foo > bar', 'entities_id' => 0],         'bar',  'foo > bar',  'int',   'two names'],
            [ [ 'completename' => ' ', 'entities_id' => 0],                 '',     '',           '0',     'only space'],
            [ [ 'completename' => '>', 'entities_id' => 0],                 '',     '',           '0',     'only >'],
            [ [ 'completename' => ' > ', 'entities_id' => 0],               '',     '',           '0',     'only > and spaces'],
            [ [ 'completename' => 'foo>bar', 'entities_id' => 0],           'bar',  'foo > bar',  'int',   'two names with no space'],
            [ [ 'completename' => '>foo>>bar>', 'entities_id' => 0],        'bar',  'foo > bar',  'int',   'two names with additional >'],
            [ [ 'completename' => ' foo >   > bar > ', 'entities_id' => 0], 'bar',  'foo > bar',  'int',   'two names with garbage'],
      ];
   }

   /**
    * @covers Dropdown::import
    * @covers CommonTreeDropdown::import
    * @dataProvider dataTestTreeImport
    */
   public function testTreeImport($input, $result, $complete, $return, $msg) {
      $id = Dropdown::import('Location', $input);
      if ($return == 'false') {
         $this->assertFalse($id, $msg);
      } else if ($return == -1) {
         $this->assertEquals(-1, $id, $msg);
      } else if ($return == '0') {
         $this->assertEquals(0, $id, $msg);
      } else if ($return == 'int') {
         $this->assertGreaterThan(0, $id, $msg);
      }
      if ($result) {
         $ut = new Location();
         $this->assertTrue($ut->getFromDB($id), $msg);
         $this->assertEquals($result, $ut->getField('name'), $msg);
         $this->assertEquals($complete, $ut->getField('completename'), $msg);
      }
   }
}
