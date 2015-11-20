<?php
/**
 * Login demo [Égalité]
 * --------------------
 * Richard K. Szabó <richard@9eb.se>
 * https://specimen.me / https://richardkszabo.me
 *
 * This particular page and README.md is licensed AGPLv3.
 */

/**
 * Bootstrap
 */
$auto_login = false;
require("../include/iq.php");

/**
 * The majority of this page is taken from README.md
 * Attempt to load and parse it. Should it fail, abort the render of
 * this page and suggest heading to one of my other pages.
 */
use \Michelf\MarkdownExtra;
require_once('../third/php-markdown/Michelf/Markdown.inc.php');
require_once('../third/php-markdown/Michelf/MarkdownExtra.inc.php');
$readme = MarkdownExtra::defaultTransform(file_get_contents("README.md"));
if (strlen($readme) < 1024) {
  die("README.md missing. Go to https://richardkszabo.me or https://github.com/rszabo");
}

/**
 * Display our session-data key
 */
$key_s = Session::getStorageKey();

?><!DOCTYPE html5>
<html lang="en">
  <head>
    <title>Égalité</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Written by Richard K. Szabó/SPECIMEN"/>
    <meta name="description" content="Example of Specimen login system"/>
  </head>
  <body class="page_login">

    <link charset="utf-8" type="text/css" rel="stylesheet" href="res/bootstrap.css" />
    <link charset="utf-8" type="text/css" rel="stylesheet" href="res/login.css" />

    <script charset="utf-8" type="text/javascript" src="res/jquery.js"></script>
    <script charset="utf-8" type="text/javascript" src="res/base64.js"></script>
    <script charset="utf-8" type="text/javascript" src="res/crypto.js"></script>
    <script charset="utf-8" type="text/javascript" src="res/gibberish-aes.js"></script>
    <script charset="utf-8" type="text/javascript" src="res/login.js"></script>

    <div id="site-login" class="container">

      <div class="span5"><?php echo $readme; ?></div>

      <div class="span1"></div>

      <div class="span5 controls">
        <h3>Login Demo</h3>
        <p class="info">
          Provide any username and passphrase. The verification captcha will only load
          when username is supplied and passphrase has begun to be entered.
        </p>

        <form method="post" id="form-login" class="form">
          <div id="form_elems" class="span4">
            <div><label for="usr">Username</label> <input type="password" id="usr" maxlength=20 /></div>
            <div><label for="pwd">Passphrase</label> <input type="password" id="pwd" maxlength=100 /></div>

            <div id="d-cpt">
              <label for="cpt">Verify</label>
              <input type="password" id="cpt" maxlength=10 />
              <a
                href="javascript:void(0);"
                id="img-verif-keyt"
                tabindex="-1"><img
                                src="res/img/px.gif"
                                id="cptimg"
                                class="cpt-log"
                                width="140"
                                height="65"
                                alt="Click to generate new" /></a>
              <div class="clear"></div>
            </div>

            <label class="disc_ok">
              <input  type="checkbox" value="1" id="co" /> Some kind of disclaimer to accept.
            </label>

            <div class="clear height2"></div>

            <div><input type="button" class="button btn-primary" id="login" value="Login" /></div>

          </div>
        </form>

        <div class="span5">
          <h3>Debug</h3>
          <div id="inf" class="alert">Login is idle.</div>
          <div class="debug code">
            <pre>Your session variables are encrypted using <?php echo $key_s; ?>.<br/>
The transport key is not set until captcha is loaded.<br/>
Upon a successful login, the transport key will change and be returned to the client,
and using that key to encrypt future transmissions.
            </pre>
          </div>
        </div>
      </div>


      <div class="span12">
      </div>
    </div>
  </body>
</html>
