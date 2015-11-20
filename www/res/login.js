/**
 * @file    login.js
 * @author  Richard K. Szabó <richard@9eb.se>
 *
 * This file contains io.js, io-cache.js, page/login.js and specimen.js from Specimen.
 * Assembled for the demo.
 */

$(document).ready(function() {

  // During login we use a different end-point, override default io.
  Specimen.url_backend = "backend/login.php";

  /*
   * This demo does not bootstrap private keys so we're
   * explicitly setting the session variable key here.
   */
  Specimen.key_s = $.cookie("key_s");

  // If username has been provided we can load the captcha.
  $('#usr').blur(function() {
    if ($(this).val() != "") {
      Specimen.Login.reloadCaptcha();
    }
  });
  // Clicking captcha will regenerate
  $('#img-verif-keyt').unbind("click").bind("click", function() {
    Specimen.Login.reloadCaptcha();
  });
  /// Bind username <CR>
  $('#usr').keydown(function(e) {
    if (e.keyCode == 13) { $('#login').click(); }
  });
  /// Bind password <CR>
  $('#pwd').keydown(function(e) {
    if (e.keyCode == 13) { $('#login').click(); }
  });
  /// Bind captcha <CR>
  $('#cpt').keydown(function(e) {
    if (e.keyCode == 13) { $('#login').click(); }
  });

  // Bind login click
  $('#login').unbind('click').bind('click', function() {
    // Get form values
    var u = $('#usr').val();
    var p = $('#pwd').val();
    var c = $('#cpt').val();

    // Return and focus object if empty detected
    if (u === "") { $('#usr').focus(); return; }
    if (p === "") { $('#pwd').focus(); return; }
    if (c === "") { $('#cpt').focus(); return; }

    // Disclaimer anyone?
    if (!$('#co').is(':checked')) {
      alert("Tick the Disclaimer box to accept something.");
      return;
    }

    $('#login').attr('disabled', 'disabled');
    Specimen.Login.loading("Connecting...");

    // Hash passphrase and build the credentials string.
    p = CryptoJS.SHA256(p).toString();
    var cr = Base64.encode(u + " " + p);
    // Set Transport Key
    Specimen.IO.setTransportKey($('#cpt').val());
    // Login
    //
    // Please note that the API Session does not exist in this demo,
    // only the backend responder exist which is backend/login.php
    //
    Specimen.IO.get('Session', 'login', {
        // Base64-encoded username and password with space as separator.
        credentials: cr,
        // Deliver captcha as an echo only; the payload is encrypted with this value.
        captcha    : c
      },
      function(res) {
        // Re-enable Login button
        $('#login').removeAttr("disabled");

        // Debug
        Specimen.log(res);

        // This is where you normally bootstrap your client environment.
        if (res.id) {
          Specimen.Login.status("Welcome " + res.username);

          // Update Transport Key.
          Specimen.IO.setTransportKey(res.key_t);

          // Clear local form
          $('#usr').focus().val("");
          $('#pwd').val("");
          $('#cpt').val("");

          // Show success
          var d = $('<div/>').addClass("alert alert-success").append(
            $('<span/>').html("You have successfully logged on as <b>" + res.username + "</b>.")
          );
          d.prependTo($('#form_elems'));
        }
        else {
          Specimen.Login.error("Something did not work well and this demo can't handle that.");
        }
      },
      function() { /* Loading */
        Specimen.Login.status("Verifying credentials...");
      },
      function(err) { /* b0rk */
        Specimen.Login.error(err.message);
      }
    );
  });
});


/**
 * @file      specimen.js
 * @class     Specimen
 * @brief     Primary Specimen functions
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   2013
 * @date      mån 19 okt 2015 14:15:53
 *
 * This is a stripped version of specimen.js for the purposes of this demo.
 * Much of the original code has been removed.
 *
 */
Specimen = {
  me          : null,
  uid         : -1,

  host        : 'docs.9eb.se',
  url_backend : '/backend/io.php',
  url_ws      : '/not_in_use',

  sid         : null,
  key_s       : null,  // Session variables asymmetric key
  key_t       : null,  // Transport key, asymmetric

  debug       : true,

  /**
   * Retrieve a list of available API:s
   * @return list
   */
  getApis: function() {
    var apis = [];
    for (var prop in Specimen) {
      if (prop.substr(0, 1).charCodeAt(0) < 97) {
        apis[apis.length] = prop;
      }
    }
    return apis;
  },
  /**
   * Logging helper
   * @param  mixed  arg, arg, arg, ...
   */
  log: function() {
    // Disabled?
    if (!this.debug) { return; }
    // Can't write?
    if (!console || !console.log) { return; }

    var t = new Date().toTimeString();
    var args = arguments;
    if (args.length == 1) {
      console.log(t, args[0]);
    }
    else {
      console.log(t, args);
    }

    var output = "";
    for (a in args) {
      var json_string = JSON.stringify(args[a]);
      json_string = json_string.replace(/</g,'[').replace(/>/g,']');
      output += json_string + "\n";
    }
    $('.debug.code').prepend(
      $('<pre>').html("<b>" + t + "</b>\n" + output)
    );
  }
};


/**
 * @file      io.js
 * @fn        Specimen.IO
 * @brief     I/O for JSON RPC / AJAX
 * @usage     Specimen.IO.get(backend, method, params, cb_ok, cb_progress, cb_err)
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   2013
 * @date      Fri 13 Mar 2015 08:11:29 UTC
 *
 * This is a stripped version of io.js for the purposes of this demo.
 *
 * MIT License 2015.
 *
 */

/**
 * Any errors originating from the client gets this type.
 */
var ERR_CLIENT_EXCEPTION = 'ClientException';


Specimen.IO = {

  INVALID_REQUEST  : -32600,
  METHOD_NOT_FOUND : -32601,
  INVALID_PARAMS   : -32602,
  INTERNAL_ERROR   : -32603,

  workers: [],

  timeout: 15000,

  transport_key: "",

  running: 0,
  init: function() {
    Specimen.log("IO initialized");
  },
  get: function(backend, func, params, cb_ok, cb_progress, cb_err) {

    // Setup identifier
    this.running      = this.running + 1;
    var id            = this.workers.length + 1;

    // Setup defaults
    params            = params || {};
    params['backend'] = backend;
    params['func']    = func;

    // Lack of callbacks should not produce errors
    cb_ok       = cb_ok       || Specimen.log;
    cb_progress = cb_progress || Specimen.log;
    cb_err      = cb_err      || Specimen.log;

    Specimen.log(
      "<IO #" + id + "> Requesting " + params["backend"] + "::" + params["func"], params
    );

    /* Encrypt request parameters and trigger worker. */
    var p_strs    = GibberishAES.enc(JSON.stringify(params, null, 2), this.getTransportKey());
    var cache_key = CryptoJS.MD5(JSON.stringify(params, null, 2));

    // Check cache
    if (Specimen.IO.Cache.has(cache_key)) {
      return cb_ok(Specimen.IO.Cache.get(cache_key));
    }

    var worker = new IO(id, p_strs, cb_ok, cb_progress, cb_err, cache_key);
    this.workers.push(worker);
  },
  isIdle: function() {
    return this.running === 0;
  },
  isWorking: function() {
    return this.running > 0;
  },
  deleteWorker: function(id) {
    this.running = this.running - 1;
    delete this.workers[id];
  },

  // added for demo: does not have to be global but can be per IO.
  setTransportKey: function(k) {
    this.transport_key = Base64.encode(k);
    Specimen.log("Transport Key set to " + this.transport_key);
  },
  // added for demo: does not have to be global but can be per IO.
  getTransportKey: function() {
    return this.transport_key;
  }
};


/**
 * Worker utilized by Specimen.IO
 *
 * Notes on error handling:
 * ------------------------
 *  When Api:handleRequest() fails in a call that error is pushed to the client as so:
 *  {
 *    error: {
 *      code:    <some kind of negative number>,
 *      message: <"invalid request" or similar>,
 *      details: <user friendly error message>,
 *      type:    <error or exception type>
 *    }
 *  }
 *  Indeed, Specimen uses a variant based on the default JSON-RPC 2.0 specification.
 *  Errors are delivered between JS-components without the top-level error array.
 */
function IO(id, params_json, cb_ok, cb_progress, cb_err, cache_key) {
  this.id          = id;
  this.params_json = params_json;
  this.cb_ok       = cb_ok;
  this.cb_progress = cb_progress;
  this.cb_err      = cb_err;
  this.cache_key   = cache_key;
  this.init();
}

IO.prototype = {
  /**
   * @fn    init
   * @brief Perform call to backend
   */
  init: function() {
    var self = this;
    var data = { "_" : self.params_json, "key_s" : Specimen.key_s };

    Specimen.log("[worker #" + this.id + "] Sending", data);

    $.ajax({
      type:    'POST',
      url:     Specimen.url_backend,
      data:    data,
      timeout: self.timeout, /* must be pretty high during signup. @todo change based on case */
      beforeSend: function(xhr) {
        if (self.cb_progress) { self.cb_progress.call(xhr); }
      }
    }).done(function(data, textStatus, jqXHR) {

      Specimen.IO.deleteWorker(self.id);
      var jd, d;

      /* Attempt to decode, or fail. */
      try {
        d = GibberishAES.dec(data, Specimen.IO.getTransportKey());
      }
      catch (err) {
        // Determine error message
        var err_msg = "Failed to decrypt data from backend.";
        if (data.substr(0, 4) == "U2Fs") {
          err_msg = "Unable to decrypt response.";
        }
        else if (data.substr(0, 1) == "{" && data.substr(-1) == "}") {
          var err_obj = JSON.parse(data);
          if (!err_obj || !err_obj.error.message) {
            Specimen.log("Incompatible backend", data);
            throw "Invalid JSON response from backend. No further actions taken.";
          }
          err_msg = err_obj.error.message;
        }

        return self.err({
          message : err_msg,
          code    : -1,
          details : data.length + "B data in reply",
          raw     : data,
          type    : ERR_CLIENT_EXCEPTION
        }, self.cb_err);
      }

      /* OK */
      var last_known_err;
      if (d) {
        try {
          jd = jQuery.parseJSON(d);

          /* Any errors from back-end? */
          if (jd.error) {
            return self.err(jd.error, self.cb_err);
          }

          Specimen.log("<IO #" + self.id + "> Result", jd.result);

          // Cache
          Specimen.IO.Cache.set(self.cache_key, jd.result || jd);

          /* Everything OK, log and trigger callback. */
          try {
            var res = self.cb_ok.call(this, jd.result);
            return res;
          }
          catch (err) {
            Specimen.log("<IO #" + self.id + "> <<<ERROR IN CALLBACK>>> Does not compute:" + self.cb_ok);
          }
          return true;
        }
        catch (error) { last_known_err = error; }
      }
      // Catch any other errors here
      self.err({
        code    : -1,
        message : last_known_err,
        details : last_known_err,
        type    : ERR_CLIENT_EXCEPTION
      });
    }).fail(function(jqXHR, textStatus, errorThrown ) {
      self.err({
        code    : self.INTERNAL_ERROR,
        message : "A fatal I/O error has occurred (" + textStatus + ")",
        type    : ERR_CLIENT_EXCEPTION,
        details : [jqXHR, errorThrown]
      });
    });
  },
  /**
   * @fn     IO.err
   * @brief  Errors produced by the worker are processed here.
   *
   * @param  object    obj       { code, message, type, details }[, more: [jqXHR, errorThrown]] }
   */
  err: function(obj) {
    Specimen.log("<IO #" + this.id +  "> <<<ERROR>>> " + obj.message, obj);
    // In case we got additional information we can output that as well.
    if (obj.more && obj.more[0]) {
      Specimen.log(obj.more[0]);
    }
    // Delete worker and trigger custom error callback if any.
    Specimen.IO.deleteWorker(this.id);
    if (this.cb_err) { this.cb_err.call(this, obj); }
  }
};

/**
 * @brief SockJS wrapper for Specimen.IO
 */
function WS(realm) {
  this.socket    = null;
  this.realm     = realm;
  this.connected = false;

  this.init(realm);
}

WS.prototype = {
  /**
   * @fn     init
   * @brief  Connect to a WS backend and assign basic handlers
   *
   * @param  string  realm
   */
  init: function(realm) {
    /* Ensure that we have a sane environment */
    realm                 = realm || "info";
    this.socket           = new SockJS(Specimen.url_ws + "/" + realm);
    this.socket.onmessage = this.onMessage;
    this.socket.onopen    = this.onOpen;
    this.socket.onclose   = this.onClose;
    Specimen.log("ws: " + realm, this.socket);
  },
  /**
   * @fn     send
   * @brief  Send data to existing realm
   *
   * @param  string  method
   * @param  object  params
   *
   */
  send: function(method, params) {
    try {
      var contents = {
        id      : this.id,
        jsonrpc : 2.0,
        method  : method,
        params  : params
      };
      var s_contents = GibberishAES.enc(JSON.stringify(contents), Specimen.IO.getTransportKey());
      var pkg = {
        "_"     : s_contents,
        "sid"   : Specimen.sid,
        "key_s" : Specimen.key_s
      };

      this.socket.send(JSON.stringify(pkg));
    }
    catch (err) {
      Specimen.log("<WS WORKER> ERROR: " + err);
    }
  },
  /**
   * Listen for captured messages
   * @param function callback
   */
  addMessageEvent: function(callback) {
    this.socket.addEventListener('message', callback);
  },
  /**
   * Remove a message listener
   * @param function callback
   */
  removeMessageEvent: function(callback) {
    this.socket.removeEventListener('message', callback);
  },
  /**
   * Override this.
   */
  onMessage: function(m) {
    Specimen.log(m);
  },
  /**
   * Override this.
   * Upon interaction: onOpen
   */
  onOpen: function() {
    this.connected = true;
    Specimen.log("onOpen");
  },
  /**
   * Override this.
   * Upon dead interaction: onClose
   */
  onClose: function() {
    this.connected = false;
    Specimen.log("onClose");
  }
};

/**
 * @file      io-cache.js
 * @class     Specimen.IO.Cache
 * @brief     Provides low-level cache for IO requests.
 * @author    Richard K. Szabó/Specimen <richard@9eb.se>
 * @created   Fri 30 Jan 2015 06:28:55 AM CET
 * @date      Wed 25 Feb 2015 17:34:43 UTC
 * @bug       N/A
 * @version   $Id: b08ae045a258484914a13192f3c613e20dd20a3a $
 * @copyright Specimen, 2013-2015. https://specimen.me
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
Specimen.IO.Cache = {
  /**
   * Internal object to store cache.
   * @var  hash  cache
   */
  cache   : {},
  /**
   * When to expunge last item.
   * @var  integer  timeout
   */
  timeout : 9000,

  /**
   * Disable?
   * @var  boolean  enabled
   */
  enabled: true,

  /**
   * @fn    has
   * @brief Determine if a key if present in the cache.
   *
   * @param  string  key
   * @return boolean
   */
  has: function(key) {
    if (!this.enabled) {
      return false;
    }
    key = this.__hash(key);
    for (var k in this.cache) {
      if (k == key) { return true; }
    }
    return false;
  },
  /**
   * @fn    get
   * @brief Get cache entry for key
   *
   * @param  string  key
   * @return mixed
   */
  get: function(key) {
    var key_hash = this.__hash(key);
    Specimen.log("Cache.get: "  + key_hash + " " + key);
    return this.cache[key_hash];
  },
  /**
   * @fn    set
   * @brief Store value in key
   *
   * @param  string  key
   * @param  mixed   value
   */
  set: function(key, value) {
    if (!this.enabled) {
      return;
    }

    key = this.__hash(key);
    this.cache[key] = value;

    // Trigger cleaning process
    var self = this;
    window.setTimeout(function() {
      self.__clean();
    }, self.timeout);
  },
  /**
   * @fn    __hash
   * @brief Hash key for internal use
   *
   * @param  string  key
   * @return string
   */
  __hash: function(key) {
    return CryptoJS.MD5(key);
  },
  /**
   * @fn    __clean
   * @brief A private function triggered by set to clear old items.
   */
  __clean: function() {
    // Only expire one item
    for (var k in this.cache) {
      delete this.cache[k];
      return;
    }
  }
};



/**
 * Behaviour of Login page.
 * @class  Specimen.Login
 */
Specimen.Login = {
  loading: function(s) {
    $('#login').attr('disabled', true);
    $('#form_elems').attr('disabled', true).stop().animate({
      opacity: 0.8
    }, 1600);
    this.status(s);
  },
  idle: function(s) {
    $('#login').removeAttr('disabled');
    $('#form_elems').stop().animate({
      opacity: 1
    }, 150, function() {
      $('#form_elems').removeAttr('disabled');
      $('#usr').focus();
    });
    this.status(s);
  },
  error: function(s) {
    $('#login').removeAttr('disabled');
    $('#form_elems').stop().animate({
      opacity: 1
    }, 300, function() {
      $('#form_elems').removeAttr('disabled');
      $('#usr.focus');
    });
    this.status(s);
  },
  status: function(msg) {
    $('#inf').html(msg);
  },
  timer: null,
  reloadCaptcha: function() {
    var self = Specimen.Login;
    if (self.timer == null) {
      $('#cptimg').attr('src', 'backend/verify.php?' + (new Date().getTime()));
      self.timer = window.setTimeout(self.reloadCaptcha, 70000);
    }
    else {
      window.clearTimeout(self.timer);
      window.setTimeout(function() {
        self.timer = null;
        self.reloadCaptcha();
      }, 1200);
    }
  }
};
