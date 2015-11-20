<?php
/**
 * @file      Captcha.php
 * @class     Captcha
 * @brief     Captcha with style.
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   2014
 * @date      Fri 20 Feb 2015 19:04:32 UTC
 * @bug       N/A
 * @warning   -
 * @version   $Id: e819721ec607b44b7db78e5d37100dff4be2f608 $
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
class Captcha {

  const KEY_LOGIN      = "captcha";
  const LIFETIME_LOGIN = 120;

  private $captcha, $render;

  /**
   * Create a new captcha.
   *
   * @param  integer  $characters      The amount of characters to show.
   * @param  integer  $width
   * @param  integer  $height
   *
   * @return image resource
   */
  public function __construct($characters = 10, $width = 145, $height = 65) {

    $this->captcha    = new Captcha_Text($characters, $width - 2, $height / 2.8);
    $im               = imagecreatetruecolor($width, $height);
    $noise_color      = imagecolorallocate($im, 131, 131, 131);
    $background_color = imagecolorallocate($im, 244, 0, 0);

    imagefill($im, 0, 0, $background_color);

    // Fetch captcha text
    $cpt = $this->captcha->getImage();
    $w = imagesx($cpt);
    $h = imagesy($cpt);

    // Rotate captcha text
    $cpt2 = imagerotate($cpt, rand(1, 20), rand(1, 9));
    imagecopy($im, $cpt2, rand(1, 5), rand(1, 6), rand(1, 5), rand(1, 6), $width, $height);

    // Make some noise.
    for( $i=0; $i<($width*$height)/6; $i++ ) {
      imagefilledellipse($im, mt_rand(0, $width), mt_rand(0, $height), 1, 1, $noise_color);
    }

    //$im = imagerotate($im, rand(0, 30), rand(0, 5));

    // Assign captcha
    $this->render = $im;

    // Clean used images
    imagedestroy($cpt);
    imagedestroy($cpt2);
  }

  /**
   * Return the code which is printed on this captcha.
   *
   * @return string
   */
  public function getCode() {
    return $this->captcha->getCode();
  }

  /**
   * Output generated captcha.
   */
  public function render() {
    header("Content-Type: image/jpeg");
    imagejpeg($this->render, null, 95);
    imagedestroy($this->render);
  }
}
