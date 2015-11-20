<?php
/**
 * @file      login.php
 * @brief     Accepts login through the web
 * @details   This file  must be  posted to,  encrypting the
 *            entire block of data using the Captcha as key.
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   2013-11-21
 * @date      ons 21 okt 2015 09:06:27
 * @bug       N/A
 *
 * @warning   Only JSON-formatted data should be displayed.
 *
 *
 * This is a simple example of how to respond to a login request.
 *
 */
$auto_login = false;
require("../../include/iq.php");
try {

  /* Validate body */
  $body = file_get_contents('php://input');
  if (!$body && !isset($_REQUEST['_'])) throw new RuntimeException("404");

  /* Input is encrypted with the hashed captcha! */
  $key   = base64_encode(Session::getCurrent()->get(Captcha::KEY_LOGIN));

  // Attempt to decrypt
  if (!$data = Encryption::dec($_REQUEST["_"], $key)) {
    throw new RuntimeException("Unable to decrypt received data, please check your local key or verification code.");
  }

  // Check JSON
  if (!$jdata = json_decode($data, true)) {
    throw new RuntimeException("Unable to parse input.");
  }

  if (empty($jdata["credentials"])) {
    throw new RuntimeException("Received credentials are missing.");
  }

  // Read credentials
  $cred_obj = explode(" ", base64_decode($jdata['credentials']));
  if (count($cred_obj) < 2) {
    throw new RuntimeException("Received credentials are malformed.");
  }

  // This is where you normally handle the call, i.e.. $res = Api::handleRequest($jdata);

  // Username and passphrase successfully read.
  $username   = $cred_obj[0];
  $passphrase = $cred_obj[1];
  // Set asymmetric key to be used in future communication
  $session = Session::getInstance();
  $session->set(Session::TRANSPORT_KEY, Encryption::genGarbage(Session::KEYSIZE));
  // Clear login key
  $session->set(Captcha::KEY_LOGIN, null);

  // Let's say that everything went well and Citizen $username was loaded successfully.
  $res = array(
    "id"                   => 123,
    "username"             => $username,
    Session::STORAGE_KEY   => base64_encode(Session::getStorageKey()),
    Session::TRANSPORT_KEY => base64_encode($session->get(Session::TRANSPORT_KEY))
  );

  // This is where you normally pass $res (Api::handleRequest()) to whatever renderer is available.

  /* Output */
  $output = json_encode(
    array("result" => $res)
  );
  die(Encryption::enc($output, $key));
}
/*
 * Never log UserExceptions, ignore them altogether
 */
catch (Exception $e) {
  $output = json_encode(array(
    "error" => array(
      "code"    => 1,
      "message" => $e->getMessage()
    )
  ));
  trigger_error($e->getMessage());
  die(Encryption::enc($output, $key));
}
