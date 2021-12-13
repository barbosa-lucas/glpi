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
    die("Sorry. You can't access this file directly");
}

define('NS_GLPI', 'Glpi\\');
define('NS_PLUG', 'GlpiPlugin\\');

/**
 * Is the script launch in Command line?
 *
 * @return boolean
 */
function isCommandLine()
{
    return (PHP_SAPI == 'cli');
}

/**
 * Is the script launched From API?
 *
 * @return boolean
 */
function isAPI()
{
    global $CFG_GLPI;

    $called_url = (!empty($_SERVER['HTTPS'] ?? "") && ($_SERVER['HTTPS'] ?? "") !== 'off'
                     ? 'https'
                     : 'http') .
                 '://' . ($_SERVER['HTTP_HOST'] ?? "") .
                 ($_SERVER['REQUEST_URI'] ?? "");

    $base_api_url = $CFG_GLPI['url_base_api'] ?? ""; // $CFG_GLPI may be not defined if DB is not available
    if (!empty($base_api_url) && strpos($called_url, $base_api_url) !== false) {
        return true;
    }

    $script = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
    if (strpos($script, 'apirest.php') !== false) {
        return true;
    }
    if (strpos($script, 'apixmlrpc.php') !== false) {
        return true;
    }

    return false;
}


/**
 * Determine if an class name is a plugin one
 *
 * @param string $classname Class name to analyze
 *
 * @return boolean|array False or an array containing plugin name and class name
 */
function isPluginItemType($classname)
{

   /** @var array $matches */
    if (preg_match("/^Plugin([A-Z][a-z0-9]+)([A-Z]\w+)$/", $classname, $matches)) {
        $plug           = [];
        $plug['plugin'] = $matches[1];
        $plug['class']  = $matches[2];
        return $plug;
    } else if (substr($classname, 0, \strlen(NS_PLUG)) === NS_PLUG) {
        $tab = explode('\\', $classname, 3);
        $plug           = [];
        $plug['plugin'] = $tab[1];
        $plug['class']  = $tab[2];
        return $plug;
    }
   // Standard case
    return false;
}


/**
 * Translate a string
 *
 * @since 0.84
 *
 * @param string $str    String to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string translated string
 */
function __($str, $domain = 'glpi')
{
    global $TRANSLATE;

    if (is_null($TRANSLATE)) { // before login
        return $str;
    }
    $trans = $TRANSLATE->translate($str, $domain);
   // Wrong call when plural defined
    if (is_array($trans)) {
        return $trans[0];
    }
    return  $trans;
}


/**
 * Translate a string and escape HTML entities
 *
 * @since 0.84
 *
 * @param string $str    String to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function __s($str, $domain = 'glpi')
{
    return htmlentities(__($str, $domain), ENT_QUOTES, 'UTF-8');
}


/**
 * Translate a contextualized string and escape HTML entities
 *
 * @since 0.84
 *
 * @param string $ctx    context
 * @param string $str    to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string protected string (with htmlentities)
 */
function _sx($ctx, $str, $domain = 'glpi')
{
    return htmlentities(_x($ctx, $str, $domain), ENT_QUOTES, 'UTF-8');
}


/**
 * Pluralized translation
 *
 * @since 0.84
 *
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plural
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string translated string
 */
function _n($sing, $plural, $nb, $domain = 'glpi')
{
    global $TRANSLATE;

    if (is_null($TRANSLATE)) { // before login
        if ($nb == 0 || $nb > 1) {
            return $plural;
        } else {
            return $sing;
        }
    }

    return $TRANSLATE->translatePlural($sing, $plural, $nb, $domain);
}


/**
 * Pluralized translation with HTML entities escaped
 *
 * @since 0.84
 *
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plural
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string protected string (with htmlentities)
 */
function _sn($sing, $plural, $nb, $domain = 'glpi')
{
    return htmlentities(_n($sing, $plural, $nb, $domain), ENT_QUOTES, 'UTF-8');
}


/**
 * Contextualized translation
 *
 * @since 0.84
 *
 * @param string $ctx    context
 * @param string $str    to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _x($ctx, $str, $domain = 'glpi')
{

   // simulate pgettext
    $msg   = $ctx . "\004" . $str;
    $trans = __($msg, $domain);

    if ($trans == $msg) {
       // No translation
        return $str;
    }
    return $trans;
}


/**
 * Pluralized contextualized translation
 *
 * @since 0.84
 *
 * @param string  $ctx    context
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plural
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _nx($ctx, $sing, $plural, $nb, $domain = 'glpi')
{

   // simulate pgettext
    $singmsg    = $ctx . "\004" . $sing;
    $pluralmsg  = $ctx . "\004" . $plural;
    $trans      = _n($singmsg, $pluralmsg, $nb, $domain);

    if ($trans == $singmsg) {
       // No translation
        return $sing;
    }
    if ($trans == $pluralmsg) {
       // No translation
        return $plural;
    }
    return $trans;
}


/**
 * Classes loader
 *
 * @param string $classname : class to load
 *
 * @return void|boolean
 */
function glpi_autoload($classname)
{

    if (
        $classname === 'phpCAS'
        && file_exists(stream_resolve_include_path("CAS.php"))
    ) {
        include_once('CAS.php');
        return true;
    }

   // Deprecation warn for RuleImportComputer* classes
    if (in_array($classname, ['RuleImportComputer', 'RuleImportComputerCollection'])) {
        Toolbox::deprecated(
            sprintf(
                '%s has been replaced by %s.',
                $classname,
                str_replace('Computer', 'Asset', $classname)
            )
        );
    }

    $plug = isPluginItemType($classname);
    if (!$plug) {
        return false;
    }

    $plugname = strtolower($plug['plugin']);
    $dir      = GLPI_ROOT . "/plugins/$plugname/inc/";
    $item     = str_replace('\\', '/', strtolower($plug['class']));

    if (!Plugin::isPluginLoaded($plugname)) {
        return false;
    }

    if (file_exists("$dir$item.class.php")) {
        include_once("$dir$item.class.php");
    }
}


// Check if dependencies are up to date
$needrun  = false;

// composer dependencies
$autoload = GLPI_ROOT . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    $needrun = true;
} else if (file_exists(GLPI_ROOT . '/composer.lock')) {
    if (!file_exists(GLPI_ROOT . '/.composer.hash')) {
       /* First time */
        $needrun = true;
    } else if (sha1_file(GLPI_ROOT . '/composer.lock') != file_get_contents(GLPI_ROOT . '/.composer.hash')) {
       /* update */
        $needrun = true;
    }
}

// node dependencies
if (!file_exists(GLPI_ROOT . '/public/lib')) {
    $needrun = true;
} else if (file_exists(GLPI_ROOT . '/package-lock.json')) {
    if (!file_exists(GLPI_ROOT . '/.package.hash')) {
       /* First time */
        $needrun = true;
    } else if (sha1_file(GLPI_ROOT . '/package-lock.json') != file_get_contents(GLPI_ROOT . '/.package.hash')) {
       /* update */
        $needrun = true;
    }
}

if ($needrun) {
    $deps_install_msg = 'Application dependencies are not up to date.' . PHP_EOL
      . 'Run "php bin/console dependencies install" in the glpi tree to fix this.' . PHP_EOL;
    if (isCommandLine()) {
        echo $deps_install_msg;
    } else {
        echo nl2br($deps_install_msg);
    }
    die(1);
}

// Check if locales are compiled.
$need_mo_compile = false;
$locales_files = scandir(GLPI_ROOT . '/locales');
$po_files = preg_grep('/\.po$/', $locales_files);
$mo_files = preg_grep('/\.mo$/', $locales_files);
if (count($mo_files) < count($po_files)) {
    $need_mo_compile = true;
} else if (file_exists(GLPI_ROOT . '/locales/glpi.pot')) {
   // Assume that `locales/glpi.pot` file only exists when installation mode is GIT
    foreach ($po_files as $po_file) {
        $po_file = GLPI_ROOT . '/locales/' . $po_file;
        $mo_file = preg_replace('/\.po$/', '.mo', $po_file);
        if (!file_exists($mo_file) || filemtime($mo_file) < filemtime($po_file)) {
            $need_mo_compile = true;
            break; // No need to scan the whole dir
        }
    }
}
if ($need_mo_compile) {
    $mo_compile_msg = 'Application locales have to be compiled.' . PHP_EOL
      . 'Run "php bin/console locales:compile" in the glpi tree to fix this.' . PHP_EOL;
    if (isCommandLine()) {
        echo $mo_compile_msg;
    } else {
        echo nl2br($mo_compile_msg);
    }
    die(1);
}

require_once $autoload;

// Use spl autoload to allow stackable autoload.
spl_autoload_register('glpi_autoload');
