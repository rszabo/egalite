<?php
/**
 * @file      Request.php
 * @class     Session_Request
 * @brief     A singleton class returning the current process' types and whathaveyou. @todo Comment.
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   Mon 29 Jun 2015 03:37:22 UTC
 * @date      Wed 04 Nov 2015 07:21:13 PM UTC
 * @bug       N/A
 * @warning   N/A
 * @version   $Id$
 * @copyright Specimen, 2013-2015. https://specimen.me
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * LICENSE (root directory of project) / https://specimen.me/about
 */
class Session_Request {

  /**
   * Cache self; one instance only.
   */
  static private $cache;

  /**
   * Get an instance
   */
  static public function getInstance() {
    if (!isset(self::$cache)) self::$cache = new self();
    return self::$cache;
  }

  /**
   * Determine if running in console (cli) or not.
   *
   * @return boolean
   */
  static public function isConsole() {
    return isset($_SERVER["SHELL"]) && !self::isWebDocument();
  }

  /**
   * Determine whether or not the current session is within the API.
   * @return boolean
   */
  static public function isApi() {
    return
      isset($_SERVER['REQUEST_URI'])
      && strpos($_SERVER['REQUEST_URI'], "/passage/") !== false;
  }

  /**
   * Determine whether or not the current session lives within an XHR request.
   * @return boolean
   */
  static public function isXhr() {
    return
      isset($_SERVER['HTTP_X_REQUESTED_WITH'])
      && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
  }

  /**
   * Determine whether or not the request is for a HTML page.
   *
   * @return boolean
   */
  static public function isWebDocument() {
    return isset($_SERVER["HTTP_HOST"]);
  }

  /**
   * A helper to determine the remote IP-address.
   * Connections to Specimen are either proxied through it's
   * own servers or a direct connection is use.
   *
   * @return  string   IP
   */
  static public function getIp() {
    if (isset($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
    return '127.0.0.1';
  }

  /**
   * @return string
   */
  static public function getIpTrace() {
    return !empty($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : null;
  }

  /**
   * Return current user agent.
   * @return string
   */
  static public function getUserAgent() {
    // Obviously.
    if (isset($_SERVER['HTTP_USER_AGENT'])) return $_SERVER['HTTP_USER_AGENT'];
    // Secondary check.
    return self::isConsole() ? $GLOBALS["SPECIMENPREFS"]->getUserAgent() : null;
  }

  /**
   * Use getInstance() instead
   */
  private function __construct() { }

  /**
   * End operation.
   *
   * @param  integer  $code            Any non-zero value for error.
   * @param  string   $error_message   Display a message on exit.
   */
  public function destroy($code = 0, $error_message = null) {
    if ($session = Session::getInstance()) $session->logout();
    if (!empty($error_message)) echo $error_message . "\n";
    exit($code);
  }
}
