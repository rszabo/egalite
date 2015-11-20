<?php
/**
 * @file      Session.php
 * @class     Session
 * @brief     I/O for a connected citizen
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   2013
 * @date      Wed 11 Nov 2015 05:39:47 PM UTC
 * @bug       N/A
 *
 *   This is a stripped version provided for demo purposes.
 *   DO NOT imitate, DO NOT assume final implemention.
 */
class Session {

  private
    $name             = '',
    $debug            = true,
    $expire_data      = 120,
    $expire_session   = 0,
    $client           = null,
    $session_id       = null,
    $citizen          = null;

  private static
    $ip               = null,
    $current          = null,
    $sessions         = array(),
    $require_login    = true;

  const TRANSPORT_KEY = 'key_t';
  const STORAGE_KEY   = 'key_s';
  const KEYSIZE       = 192;

  /**
   * Setup or get a previously created session.
   * @param  string  $session_name    Set to null for default
   */
  static public function getInstance($session_name = null) {

    if (is_null($session_name)) $session_name = "smth";

    self::setCurrent($session_name);
    /// Update IP-address if not set
    if (self::getIp() === null) self::setIp(Session_Request::getIp());
    /// Session in progress?
    if ($session = self::getSession($session_name)) return $session;
    /// Create new
    return self::register($session_name);
  }

  /**
   * @param  string  $session_name
   * @return mixed
   */
  static public function getSession($session_name) {
    return isset(self::$sessions[$session_name])
           ? self::$sessions[$session_name]
           : null;
  }

  /**
   * @param  string  $session_name
   * @return Session
   */
  static private function register($session_name) {
    self::$sessions[$session_name] = new self($session_name);
    return self::$sessions[$session_name];
  }

  /**
   * @return mixed  null on failure, Session on success
   */
  static public function getCurrent() {
    if (!isset(self::$sessions[self::$current])) return null;
    return self::$sessions[self::$current];
  }

  /**
   * @param  string  $session_name
   */
  static public function setCurrent($session_name) {
    self::$current = $session_name;
  }

  /**
   * @param  string  $ip
   */
  static public function setIp($ip) {
    self::$ip = $ip;
  }

  /**
   * @return string
   */
  static public function getIp() {
    return self::$ip;
  }

  /**
   * Return key used to decrypt $_SESSION-data
   *
   * @throws RuntimeException
   * @return string
   */
  public static function getStorageKey() {

    /* If we're running within the system as a service we can use whatever. */
    if (Session_Request::isConsole()) return sha1(self::getConsoleApp());

    /* Determine where the key is stored */
    $k = "";
    if (self::getRequest(self::STORAGE_KEY)) $k = self::getRequest(self::STORAGE_KEY);
    if (self::getCookie(self::STORAGE_KEY))  $k = self::getCookie(self::STORAGE_KEY);

    /* We've found storage key. Verify! */
    if (!empty($k)) {
      $k = preg_replace("/[^a-z^0-9]+/im", "", $k);
      if (!Session_StorageKey::match($k)) {
        throw new RuntimeException("Storage keys is malformed");
      }
      return base64_decode($k);
    }
    throw new RuntimeException("No storage key present, all actions halted.");
  }

  /**
   * Set $_SESSION variable.
   *
   * @param  string  $key
   * @param  mixed   $value    If null, unsets key
   * @param  string  $context  Optional, i.e. 'store'
   * @return boolean
   */
  static public function s($key, $value = null, $context = null) {

    // Null clears and bails
    if ($value === null && (isset($_SESSION[$key]) || isset($_SESSION[$context][$key]))) {
      if (!is_null($context)) {
        unset($_SESSION[$context][$key]);
      }
      else {
        unset($_SESSION[$key]);
      }
      return;
    }

    // @todo Implement
    if (is_object($key) || is_array($key)) {
      throw new LogicException("Unfortunately we do not support objects as keys.");
    }

    // @todo Implement
    if (is_object($value) || is_array($value)) {
      throw new LogicException("Unfortunately we do not support storing of objects.");
    }

    // Encrypt if necessary
    if (!empty($value)) $value = Encryption::enc($value, self::getStorageKey());

    // Set
    if (!empty($context)) {
      if (empty($_SESSION[$context])) $_SESSION[$context] = array();
      $_SESSION[$context][$key] = $value;
    }
    else {
      $_SESSION[$key] = $value;
    }

    return true;
  }

  /**
   * Get $_SESSION variable.
   *
   * @param  string  $key
   * @param  string  $context  Optional, i.e. 'store'
   * @return string
   */
  static public function g($key, $context = null) {

    if (!empty($context)) {
      if (isset($_SESSION[$context][$key])) {
        $val = Encryption::dec($_SESSION[$context][$key], self::getStorageKey());
        return $val;
      }
      return null;
    }
    if (!isset($_SESSION[$key])) return null;
    $val = Encryption::dec($_SESSION[$key], self::getStorageKey());

    return $val;
  }

  /**
   * Return keys and values stored in a specific context.
   * @param  string  $context       I.e. 'store'
   */
  static public function getContextKeys($context) {
    if (!isset($_SESSION[$context])) return null;

    $ret = array();
    foreach($_SESSION[$context] as $key => $value) $ret[$key] = $value;
    return $ret;
  }

  /**
   * Set an entire block of data.
   *
   * @param  string  $context
   * @param  array   $data
   */
  static public function setContextData($context, array $data) {
    $_SESSION[$context] = $data;
  }

  /**
   * Determine whether or not the Session is setup entirely with login.
   *
   * @param  Citizen $citizen     Match with the one registered.
   * @return boolean
   */
  static public function isSaneAndLoggedIn(Citizen $citizen) {
    $session = Session::getCurrent();
    if (!$session)               return false;
    if (!$session->getCitizen()) return false;

    return $citizen->get('id') == $session->getCitizen()->get('id');
  }

  /**
   * Determine and return the name of the running script.
   */
  static public function getConsoleApp() {
    if (!Session_Request::isConsole()) throw new LogicException("Not running as console client.");
    return $_SERVER['PHP_SELF'];
  }

  /**
   * Empty cookies used by Specimen
   */
  static public function clearCookies() {
    self::setCookie(session_name(), null);
    self::setCookie('key_t', null);
    self::setCookie('key_s', null);
    self::setCookie('state', null);
    self::setCookie('contactlist', null);
    self::setCookie('instancetag', null);
    self::setCookie('fingerprint', null);
  }

  /**
   * Helper for setcookie. Totally safe for use in any environment as
   * logic within the method determines the possibility of setting
   * $_COOKIE and sending the cookie.
   *
   * @param  string  $key    If null: clears $_COOKIE
   * @param  string  $val    If null: deletes cookie
   * @param  integer $time   Defaults to 0: session
   */
  static public function setCookie($key, $val = null, $time = 0) {
    $path = "/";

    /** Unset */
    if ($val === null && isset($_COOKIE[$key])) {
      $time = time() - 1800;
      unset($_COOKIE[$key]);
    }
    /* Normal set operation */
    else {
      $_COOKIE[$key] = $val;
    }

    // Send cookies if we can.
    if (Session_Request::isWebDocument()) setcookie($key, $val, $time, $path);
  }

  /**
   * Get value of cookie
   *
   * @param  $key
   * @return mixed  string or null
   */
  static public function getCookie($key) {
    return (!empty($_COOKIE[$key])) ? $_COOKIE[$key] : null;
  }

  /**
   * Get value of request parameter
   *
   * @param  $key
   * @return mixed  string or null
   */
  static public function getRequest($key) {
    return (!empty($_REQUEST[$key])) ? $_REQUEST[$key] : null;
  }

  /**
   * Private construct. Use getInstance() to establish a Session.
   */
  private function __construct($session_name)  {
    // Setup Session and Cookie details
    // IMPORTANT: Two identical sessions, say bin/cron/determine_entropy.php
    // will have a native lock function when the system wish for an exclusive
    // session file. This is a good thing.
    //
    $this->name       = $session_name;
    $this->client     = self::fetchCurrentClient();
    $this->session_id = $this->generateSessionId();

    // Send Storage key if not already delivered
    // In the future this particular setup should be for a sane env rather
    // than storage key as that too will only be delivered once logged in.
    $this->sendStorageKeyIfCrapMethod();

    if (!Session_Request::isConsole()) {
      session_set_cookie_params($this->expire_session, "/");
      session_name($session_name);
      session_id($this->session_id);
      if (!session_start()) {
        throw new RuntimeException("Unable to initialize session.");
      }
    }

    /* Generate initial data */
    $this->setup();

    /* Load citizen if any*/
    if (self::getRequireLogin()) $this->loadCitizen();
  }

  /**
   * Validate our environment. If anything is out of the ordinary
   * we send the storage key, break the connection and logout.
   *
   * @todo WILL BE REMOVED IN A NEAR FUTURE... LIKE THIS WEEKEND MAYBE.
   *
   *
   * @return boolean
   */
    public function sendStorageKeyIfCrapMethod() {
    try {
      $whatever = self::getStorageKey();
    }
    catch (Exception $e) {
      self::sendStorageKey();
      if (!$this->isLoggedIn()) {
        header("Location: index.php");
        exit;
      }
    }
    return true;
  }

  /**
   * Send storage key in the form of a cookie to the client.
   */
  static public function sendStorageKey() {
    Session::setCookie(self::STORAGE_KEY, Session_StorageKey::generate());
  }

  /**
   * Store data in session.
   *
   * HEAVILY MODIFIED FOR THIS DEMO.
   *
   * @param  string  $key
   * @param  mixed   $data    If null, unsets
   * @param  integer $expire  Optional, in seconds. Use rarely.
   * @return boolean
   */
  public function set($key, $data = null, $expire = null)  {
    if (!isset($_SESSION["tmp"])) $_SESSION["tmp"] = array();

    $data = base64_encode($data);
    $data = Encryption::enc($data, $this->getInternalKey());

    $_SESSION["tmp"][$key] = $data;

    return true;
  }

  /**
   * Get data from session
   *
   * HEAVILY MODIFIED FOR THIS DEMO.
   *
   * @param  string  $key
   * @return string
   */
  public function get($key) {
    if (!isset($_SESSION["tmp"][$key])) return null;

    $data = Encryption::dec($_SESSION["tmp"][$key], $this->getInternalKey());
    return base64_decode($data);
  }

  /**
   * Returns an array of all session keys and their data.
   *
   * HEAVILY MODIFIED FOR THIS DEMO.
   *
   * @return array
   */
  public function getAll() {
    return $_SESSION;
  }

  /**
   * Completely eradicate a session.
   *
   * HEAVILY MODIFIED FOR THIS DEMO.
   *
   * @return boolean
   */
  public function delete() {
    self::clearCookies();
    /* Def. */
    session_write_close();
    $_SESSION = array();
    return true;
  }

  /**
   * Unbind variables and settings associated with the session and exit.
   * @return boolean
   */
  public function logout() {
    if ($this->isLoggedIn() && $this->getCitizen()) {
      $bosh = Bosh::getInstance()->logout();
      $this->getCitizen()->logout();
    }
    $this->delete();
    return true;
  }

  /**
   * Prevent Catch-22 in pages such as login.
   */
  public static function setRequireLogin($require_login = true) {
    self::$require_login = $require_login;
  }

  public static function getRequireLogin() {
    return self::$require_login;
  }

  /**
   * See if we're logged in or are required to login.
   * @return boolean
   */
  public function isLoggedIn() {
    if (!self::$require_login) return true;
    return (!empty($this->citizen)) && $this->citizen instanceof Citizen;
  }

  /**
   * Return transport key
   * @return string
   */
  public function getTransportKey() {
    return $this->get(self::TRANSPORT_KEY);
  }

  /**
   * Each session use a highly unique key for data storage.
   * This is internal storage on object and provides additional
   * security over :s/:g.
   *
   * ALSO HEAVILY MODIFIED FOR THE PURPOSES OF THIS DEMO.
   *
   * @return string
   *
   */
  private function getInternalKey() {
    $key = Site::getKey('session') . self::getIp() . sha1(Site::getKey('session'));
    if (self::g('salt')) {
      $key = Encryption::dec(self::g('salt'), $this->client);
    }
    /** Every request adds STORAGE_KEY; prepend! */
    return self::getStorageKey() . $key;
  }

  /**
   * Create basic salt for internal encryption
   * Should not be ran if already created.
   */
  private function buildInternalKey() {
    if (self::g('salt')) {
      throw new LogicException("Do not run after initialization.");
    }

    self::s(
      'salt',
      Encryption::enc(
        Encryption::genGarbage(8) . str_shuffle(Site::getKey('session'))
        . sha1(str_shuffle(self::g('client'))), $this->client
      )
    );
  }

  /**
   * For newly created sessions initial data is required,
   * such as client information, private salt and such.
   */
  private function setup() {
    // No need?
    if (self::g('client') != "") return;

    // Setup!
    self::s('client',  Encryption::enc($this->client, Site::getKey('session')));
    self::s('created', date("Y-m-d H:i:s"));
    // Every Session use a unique key; see getInternalKey().
    //
    $this->buildInternalKey();
    $this->set("end", "now.");
  }

 /**
   * Upon initialization, load citizen if any
   * and update TTL.
   */
  private function loadCitizen() {
    //$citizen_id = intval($this->get('citizen'));
    $citizen_id = intval($this->get('citizen'));
    if ($citizen_id < 1) {
      return false;
    }
    $this->citizen = $citizen_id;
    $this->setCitizen($citizen_id);
  }

  public function getCitizen() {
    return $this->citizen;
  }

  /**
   * Verify client
   */
  private function isValidClient() {
    return (self::fetchCurrentClient() == $this->getStoredClient());
  }

  /**
   * Determine and return current client.
   *
   * @return  string
   */
  public static function fetchCurrentClient() {

    $client = sha1(
      self::getIp() . Session_Request::getIpTrace() .
      Session_Request::getUserAgent() . Site::getKey('session')
    );

    if (Session_Request::isConsole()) $client .= md5($_SERVER["PHP_SELF"]);

    return $client;
  }

  /**
   * Return the client which is registered in our session.
   * The client id is encrypted in the session file,
   * and has a totally different encryption in the
   * database.
   */
  public function getStoredClient() {
    $c = self::g('client');
    return $c ? Encryption::dec($c, Site::getKey('session')) : '';
  }

  /**
   * Generate and return a unique session id
   *
   * @return string
   */
  private function generateSessionId() {
    //< 'sp'-identifier required by gc_sessions
    return "sp" . sha1($this->client . Site::getKey('session'))
           . sha1(Session_Request::getUserAgent() . self::getIp());
  }

  /**
   * Enable or disable internal debugging
   */
  public function setDebug($enabled) {
    $this->debug = $enabled;
  }
}
