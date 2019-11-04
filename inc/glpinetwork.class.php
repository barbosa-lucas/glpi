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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class GLPINetwork {

   public function getTabNameForItem() {
      return __('GLPI Network');
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Config') {
         $glpiNetwork = new self();
         $glpiNetwork->showForConfig();
      }
   }

   public static function showForConfig() {
      if (!Config::canView()) {
         return;
      }

      $registration_key = self::getRegistrationKey();
      $informations = self::getRegistrationInformations();

      $canedit = Config::canUpdate();
      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(Config::class)."\" method='post'>";
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='2'>" . __('Registration') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='glpinetwork_registration_key'>" . __('Registration key') . "</label></td>";
      echo "<td>" . Html::textarea(['name' => 'glpinetwork_registration_key', 'value' => $registration_key, 'display' => false]) . "</td>";
      echo "</tr>";

      if ($registration_key !== "") {
         if (!empty($informations['validation_message'])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td></td>";
            echo "<td colspan='2'>";
            echo "<div class=' " . ($informations['is_valid'] ? 'ok' : 'red') . "'> ";
            echo "<i class='fa fa-info-circle'></i>";
            echo $informations['validation_message'];
            echo "</div>";
            echo "</td>";
            echo "</tr>";
         }

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Subscription') . "</td>";
         echo "<td>" . ($informations['subscription'] !== null ? $informations['subscription']['title'] : __('Unknown')) . "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Registered by') . "</td>";
         echo "<td>" . ($informations['owner'] !== null ? $informations['owner']['name'] : __('Unknown')) . "</td>";
         echo "</tr>";
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='2' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
         echo "</td></tr>";
      }

      echo "</table></div>";
      Html::closeForm();
   }

   /**
    * Get GLPI User Agent in expected format from GLPI Network services.
    *
    * @return string
    */
   public static function getGlpiUserAgent(): string {
      $version = defined('GLPI_PREVER') ? GLPI_PREVER : GLPI_VERSION;
      $comments = sprintf('installation-mode:%s', GLPI_INSTALL_MODE);
      if (!empty(GLPI_USER_AGENT_EXTRA_COMMENTS)) {
         // append extra comments (remove '(' and ')' chars to not break UA string)
         $comments .= '; ' . preg_replace('/\(\)/', ' ', GLPI_USER_AGENT_EXTRA_COMMENTS);
      }
      return sprintf('GLPI/%s (%s)', $version, $comments);
   }

   /**
    * Get GLPI Network UID to pass in requests to GLPI Network Services.
    *
    * @return string
    */
   public static function getGlpiNetworkUid(): string {
      return Config::getUuid('glpi_network');
   }

   /**
    * Get GLPI Network registration key.
    *
    * A registration key is a base64 encoded JSON string with a key 'signature' containing the binary
    * signature of the whole.
    *
    * @return string
    */
   public static function getRegistrationKey(): string {
      global $CFG_GLPI;
      return $CFG_GLPI['glpinetwork_registration_key'] ?? '';
   }

   /**
    * Get GLPI Network registration informations.
    *
    * @param boolean $offline  True to prevent fetching informations from registration API
    *
    * @return array  Registration data:
    *    - is_valid (boolean):          indicates if key is valid;
    *    - validation_message (string): message related to validation state;
    *    - owner (array):               owner attributes;
    *    - subscription (array):        subscription attributes.
    */
   public static function getRegistrationInformations(bool $offline = false) {
      global $CFG_GLPI, $GLPI_CACHE;

      $registration_key = self::getRegistrationKey();

      $cache_key = sprintf('registration_%s_informations', sha1($registration_key));
      if (($informations = $GLPI_CACHE->get($cache_key)) !== null) {
         return $informations;
      }

      $informations = [
         'is_valid'           => false,
         'validation_message' => null,
         'owner'              => null,
         'subscription'       => null,
      ];

      if ($registration_key === '') {
         return $informations;
      }

      // Decode data from key
      $key_data = json_decode(base64_decode($registration_key), true);
      if (json_last_error() !== JSON_ERROR_NONE || !is_array($key_data)
          || !array_key_exists('signature', $key_data)) {
         $informations['validation_message'] = __('The registration key is invalid.');
         return $informations;
      }

      // Check signature validity
      $signature_key = $CFG_GLPI['glpinetwork_signature_key'] ?? '';

      if ($offline && empty($signature_key)) {
         $informations['validation_message'] = __('Unable to verify registration key signature.');
         return $informations;
      }

      $is_signature_valid = self::isRegistrationKeySignatureValid($registration_key, $signature_key);

      if (!$offline && (empty($signature_key) || !$is_signature_valid)) {
         // Try to get up to date signature public key for following cases:
         // - signature key is not yet known,
         // - registration key signature validation fails (signature key may have changed since last sync).
         $new_signature_key = self::fetchRegistrationSignaturePublicKey();

         if ($new_signature_key === null) {
            $informations['validation_message'] = __('Unable to fetch signature key to verify the registration key.');
            return $informations;
         } else if ($new_signature_key !== $signature_key) {
            $is_signature_valid = self::isRegistrationKeySignatureValid($registration_key, $signature_key);
         }
      }

      if (!$is_signature_valid) {
         $informations['validation_message'] = __('Registration key signature is not valid.');
         return $informations;
      }

      if ($offline) {
         // Offline mode, skip checks
         // Assume that key is valid as we were able to read their informations
         $informations['is_valid'] = true;
         return $informations;
      }

      // Verify registration from registration API
      $error_message = null;
      $registration_response = Toolbox::callCurl(
         rtrim(GLPI_NETWORK_REGISTRATION_API_URL, '/') . '/info',
         [
            CURLOPT_HTTPHEADER => [
               'Accept:application/json',
               'Content-Type:application/json',
               'User-Agent:' . self::getGlpiUserAgent(),
               'X-Registration-Key:' . $registration_key,
               'X-Glpi-Network-Uid:' . self::getGlpiNetworkUid(),
            ]
         ],
         $error_message
      );
      $registration_data = $error_message === null ? json_decode($registration_response, true) : null;
      if ($error_message !== null || json_last_error() !== JSON_ERROR_NONE
          || !is_array($registration_data) || !array_key_exists('is_valid', $registration_data)) {
         $informations['validation_message'] = __('Unable to fetch registration informations.');
         return $informations;
      }

      $informations['is_valid']           = $registration_data['is_valid'];
      $informations['validation_message'] = $registration_data['is_valid']
         ? __('The registration key is valid.')
         : __('The registration key is invalid.');
      $informations['owner']              = $registration_data['owner'];
      $informations['subscription']       = $registration_data['subscription'];

      $GLPI_CACHE->set($cache_key, $informations, new \DateInterval('P1D')); // Cache for one day

      return $informations;
   }

   /**
    * Check if GLPI Network registration is existing and valid.
    *
    * @return boolean
    */
   public static function isRegistered(bool $offline = false): bool {
      return self::getRegistrationInformations($offline)['is_valid'];
   }

   /**
    * Validate the signature of a registration key.
    *
    * @param string $registration_key      Registration key
    * @param string $signature_public_key  Public key of key used to verify signature
    *
    * @return boolean
    */
   private static function isRegistrationKeySignatureValid($registration_key, $signature_public_key): bool {

      $key_data = json_decode(base64_decode($registration_key), true);
      if (json_last_error() !== JSON_ERROR_NONE || !is_array($key_data)
          || !array_key_exists('signature', $key_data)) {
         return false;
      }

      $signature = base64_decode($key_data['signature'], true);
      if ($signature === false) {
         return false;
      }

      $original_data = $key_data;
      unset($original_data['signature']); // signature has not been used to generate itself
      $valid = openssl_verify(json_encode($original_data), $signature, $signature_public_key);

      return $valid === 1;
   }

   /**
    * Fetch public key used to verify signature of registration keys.
    *
    * @return string|null
    */
   private static function fetchRegistrationSignaturePublicKey(): ?string {
      global $CFG_GLPI;

      $error_message = null;
      $signature_response = Toolbox::callCurl(
         rtrim(GLPI_NETWORK_REGISTRATION_API_URL, '/') . '/signature-key',
         [
            CURLOPT_HTTPHEADER => ['Accept:application/json']
         ],
         $error_message
      );
      $signature_data = $error_message === null ? json_decode($signature_response, true) : null;
      if ($error_message !== null || json_last_error() !== JSON_ERROR_NONE
          || !is_array($signature_data) || !array_key_exists('signature-key', $signature_data)) {
         return null;
      }

      $signature_key = $signature_data['signature-key'];
      Config::setConfigurationValues('core', ['glpinetwork_signature_key' => $signature_key]);

      return $signature_key;
   }

   public static function showInstallMessage() {
      return nl2br(sprintf(__("You need help to integrate GLPI in your IT, have a bug fixed or benefit from pre-configured rules or dictionaries?\n\n".
         "We provide the %s space for you.\n".
         "GLPI-Network is a commercial product that includes a subscription for tier 3 support, ensuring the correction of bugs encountered with a commitment time.\n\n".
         "In this same space, you will be able to <b>contact an official partner</b> to help you with your GLPI integration.\n\n".
         "Or, support the GLPI development effort by <b>donating</b>."),
         "<a href='".GLPI_NETWORK_SERVICES."' target='_blank'>".GLPI_NETWORK_SERVICES."</a>"));
   }

   public static function getErrorMessage() {
      return nl2br(sprintf(__("Having troubles setting up an advanced GLPI module?\n".
         "We can help you solve them. Sign up for support on %s."),
         "<a href='".GLPI_NETWORK_SERVICES."' target='_blank'>".GLPI_NETWORK_SERVICES."</a>"));
   }

   public static function addErrorMessageAfterRedirect() {
      Session::addMessageAfterRedirect(self::getErrorMessage(), false, ERROR);
   }

   public static function isServicesAvailable() {
      $content = \Toolbox::callCurl(GLPI_NETWORK_REGISTRATION_API_URL);
      return strlen($content) > 0;
   }

   public static function getOffers(bool $force_refresh = false): array {
      global $GLPI_CACHE;

      $lang = preg_replace('/^([a-z]+)_.+$/', '$1', $_SESSION["glpilanguage"]);
      $cache_key = 'glpi_network_offers_' . $lang;

      if (!$force_refresh && $GLPI_CACHE->has($cache_key)) {
         return $GLPI_CACHE->get($cache_key);
      }

      $response = \Toolbox::callCurl(
         rtrim(GLPI_NETWORK_REGISTRATION_API_URL, '/') . '/offers',
         [
            CURLOPT_HTTPHEADER => ['Accept-Language: ' . $lang]
         ]
      );
      $offers   = json_decode($response, true);

      if (is_array($offers)) {
         $GLPI_CACHE->set($cache_key, $offers, HOUR_TIMESTAMP);
      }

      return $offers;
   }
}
