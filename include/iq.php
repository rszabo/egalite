<?php
/**
 *  @file      iq.php
 *  @details   Bootstraps includes, error handlers and more.
 *  @author    Richard K. Szabó/Specimen <info@richardkszabo.me>
 *  @created   2013
 *  @date      Mon 05 Jan 2015 05:28:33 PM CET
 *
 *  This is a stripped version for the demo.
 *
 */
/** For timed instructions we turn to this global */
$__START = microtime(true);
/** Current directory name */
$dir = dirname(__FILE__);
/** Initialize Session. Allow autoload to be disabed by i.e. the internal backend. */
if (!isset($session_autostart)) $session_autostart = true;

/** Write your own error handler */
ini_set("display_errors", 0);
error_reporting(E_ALL);

DEFINE("IQ_LOADED", true);

/** Write your own class loader */
function __autoload($class_name) {
  $dir      = dirname(__FILE__);
  $filename = str_replace("_", "/", $class_name) . '.php';
  // Check for class
  if (file_exists($dir . "/classes/" . $filename)) {
    require($dir . '/classes/' . $filename);
    return;
  }
  throw new RuntimeException("Class " . $class_name . " not found, but requested by code.");
}

// auto_login must be disabled on the actual login page.
if (isset($auto_login) && $auto_login === false) Session::setRequireLogin(false);
$session = $session_autostart ? Session::getInstance(null) : null;
