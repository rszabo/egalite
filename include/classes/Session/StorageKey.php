<?php
/**
 * @file      StorageKey.php
 * @class     Session_StorageKey
 * @brief     Aids in encrypting and decrypting a storage key
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   Mon 21 Jul 2014 08:59:53 PM CEST
 * @date      Wed 18 Nov 2015 09:15:01 AM UTC
 * @todo      Should be rewritten. It was primarily written so that code could be moved from Session.
 * @warning   N/A
 * @version   $Id: 7f72931aef63deb9b050830f05ca427c7ba35e6d $
 * @copyright Specimen, 2013-2014. https://specimen.me
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
class Session_StorageKey {

  static public function generate() {
    $sk = base64_encode(
      Encryption::enc(
        self::getData(), self::getSalt()
      )
    );
    return $sk;
  }

  static public function getData() {
    return sha1(Session::getIp())
           . sha1(Session_Request::getUserAgent())
           . sha1(Site::getKey(Session::STORAGE_KEY));
  }

  static public function getSalt() {
    $sa = base64_encode(substr(Session::getIp(), -5));
    return $sa;
  }

  static public function match($storage_key) {
    $dec = Encryption::dec(
      base64_decode($storage_key), self::getSalt()
    );
    return $dec === self::getData();
  }
}
