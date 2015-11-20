<?php
/**
 * @file      Encryption.php
 * @class     Encryption
 * @brief     Provides base functionality to encrypt and decrypt.
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   2013
 * @date      Fri 23 Oct 2015 07:06:46 AM UTC
 * @bug       N/A
 * @warning   -
 *
 * This is a stripped version for the purposes of this demo.
 *
 * MIT
 *
 */
class Encryption {

  public static function enc($data, $key) {
    return GibberishAES::enc($data, $key);
  }

  public static function dec($data, $key) {
    return GibberishAES::dec($data, $key);
  }

  /**
   * Generate a very random string based (base64-encoded) on this genPwd and urandom.
   *
   * @param  integer  $max_length   Maximum length of passphrase to return
   * @return string   base64-encoded text
   */
  public static function genGarbage($max_length = 100) {
    // Yes, at the moment we only care about /dev/urandom but should garbage
    // be read from somewhere else it should go in this method.
    return self::getFromUrandom($max_length, true);
  }

  /**
   * Return data from /dev/urandom
   *
   * @param  integer  $max_length
   * @param  boolean  $base64        Whether or not to armor content
   * @return string
   */
  public static function getFromUrandom($max_length = 100, $base64 = false) {
    // Store result in a file
    $f = "/tmp/" . rand(1, 1000) . sha1($_SERVER["REMOTE_ADDR"]) . ".tmp"; // for demo only
    exec("head -c " . $max_length . " /dev/urandom > " . $f);
    // Fetch it
    $data = file_get_contents($f);
    // Clean up
    if (file_exists($f)) @unlink($f);

    // Return
    $s = $base64 ? base64_encode($data) : $data;
    return mb_substr($s, 0, $max_length);
  }
}
