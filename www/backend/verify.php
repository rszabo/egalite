<?php
/**
 * This script generates an image containing an asymmetric key used by login.
 *
 * @author    Richard K. Szabó <richard@9eb.se>
 * @file      verify.php
 * @copyright MIT
 */
$auto_login = false;
require("../../include/iq.php");
$captcha = new Captcha(10);
$session->set(Captcha::KEY_LOGIN, $captcha->getCode(), Captcha::LIFETIME_LOGIN);
$captcha->render();
