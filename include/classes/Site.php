<?php
/**
 * for demo purposes
 */
class Site {

  /**
   * This provides an internal asymmetric key for cache- and database encryption.
   * For this demo it only returns the hashed input.
   *
   * @param  string  $str
   * @return string
   */
  static public function getKey($str) {
    return sha1($str);
  }
}
