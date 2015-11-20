<?php
/*
* File: [Captcha/]Text.php (was CaptchaSecurityImages.php)
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 03/08/06
* Updated: 07/02/07
*
* Modified by Richard K. Szabó/Specimen, 2013-2015.
*
* Requirements: PHP 4/5 with GD and FreeType libraries
* Link: http://www.white-hat-web-design.co.uk/articles/php-captcha.php
*
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/
class Captcha_Text {

  private $font = '../../res/DejaVuSansMono.ttf';

  private $code, $image;

  /**
   * Generate captcha
   *
   * @param  integer  $characters        Amount of characters to draw
   * @param  integer  $width
   * @param  integer  $height
   */
  public function __construct ($characters = 10, $width = 140, $height = 24) {

    if (!file_exists($this->font)) {
      throw new RuntimeException("Font " . $this->font . " is missing. Can't proceed.");
    }

    $this->code = $this->generateCode($characters);

    /* font size will be 75% of the image height */
    $font_size = $height * 0.7;
    $image     = imagecreate($width, $height) or die('Cannot initialize new GD image stream');
    /* set the colours */
    $noise_color = imagecolorallocate($image, 255, 255, 255);
    $text_color  = imagecolorallocate($image, 44, 44, 44);
    $text_color2 = imagecolorallocate($image, 244, 1, 1);
    $line_color  = imagecolorallocate($image, 244, 0, 0);

    /* generate random dots in background */
    for( $i=0; $i<($width*$height)/3; $i++ ) {
      imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
    }
    /* generate random lines in background of text */
    for( $i=0; $i<($width*$height)/150; $i++ ) {
      imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
    }
    /* create textbox and add text */
    $textbox = imagettfbbox($font_size, 0, $this->font, $this->code) or die('Error in imagettfbbox function');
    $x = ($width - $textbox[4])/2;
    $y = ($height - $textbox[5])/2;
    imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font , $this->code) or die('Error in imagettftext function');
    imagettftext($image, $font_size, 0, $x + 1, $y + 1, $text_color2, $this->font, substr($this->code, 0, 4));
    imagettftext($image, $font_size + 2, 0, $width - 50, 20, $line_color, $this->font, substr($this->code, -3));

    $this->image = $image;
  }

  /**
   * Get generated code
   * @return string
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * Get generated image
   * @return image resource
   */
  public function getImage() {
    return $this->image;
  }

  /**
   * Generate a Captcha code.
   * It must have a proper length, not be dictionary-based and easy to type.
   *
   * @param  integer  $characters  Default 10
   * @return string
   */
  private function generateCode($characters = 10) {
    // Shuffle alphabet with additional characters.
    $r    = rand(1, 999);
    $word = array_merge(range('a', 'z'), array("@", "#"));
    shuffle($word);

    // Remove unwanted letters and return
    $ret = implode($word);
    $ret = str_replace(array("l", "j", "q", "g", "i"), "", $ret); // @todo Change font to remove.
    return substr($ret, 0, $characters - strlen($r)) . $r;
  }
}
