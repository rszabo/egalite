Égalité
============

### A suggestion to implement a more secure and robust login system and session management.

This is a working fork of the login system used in the communications platform
[Specimen](https://specimen.me),
a service focused on privacy, personal integrity and security.

This document will explain how to set up a safe session environment in PHP and how additional
encryption besides SSL can be implemented for login systems.

The old way
------------

Strictly relying on a web browsers security settings can have rather unpleasant consequences.
An outgoing request such as the following is one typical case.

```
{
  name     : "your name",
  password : "your password"
}
```

The new way
------------

Hash password (passphrase) in the web browser, encrypt the credentials using a 10 byte one-time key
visualised by a captcha, and separate login requests from  API.

```
{
  "_"     : "U2FsdGVkX18to1cMtVT/CilOkiNPTdTKN...",
  "key_s" : "VTJGc2RHVmtYMS9aQXBIZldR1Kcy9DcDA..."
}
```

### Application implementation

The following is an example of a more robust login request.

```
// Hash passphrase
var passphrase = CryptoJS.SHA256(p_input).toString();

// Create the credentials data
var cr = Base64.encode(username + " " + passphrase);

// Set Transport Key to the value presented
// in the Captcha. `Specimen.IO` will use it
// to encrypt its payload.
Specimen.IO.setTransportKey(captcha_input);

// Attempt to login
Specimen.IO.get("Session", "login", {
  credentials: cr
},
function(res_obj) {
  if (res_obj.id) {
    /* Citizen signed on */
  }
});
```
Because `passphrase` is hashed, the end-point does not need to care about invalid characters or
otherwise parse input in the same manner as one typically does.

`credentials` contains `username` and `passphrase` separated by a single space. The separator can be
changed to whatever.

### Core implementation (without cache)

The underlying API of `Specimen.IO` will use the Transport Key to encrypt the entire JSON request.
The following is an excerpt of said class (object) where the actual delivery is taking place.

```
// Gather parameters
params            = params || {};
params['backend'] = backend; // Session
params['func']    = func;    // login

// Encrypt payload prior to waking worker.
var p_strs = GibberishAES.enc(
  JSON.stringify(params, null, 2),
  this.getTransportKey()
);

// Store worker in queue.
this.workers.push(
  new IO(
    id, p_strs, cb_ok, cb_progress, cb_err
  )
);
```

### Payload delivery

The actual payload will look like this:

```
{
  "_"     : "U2FsdGVkX18to1cMtVT/CilOkiNPTdTKN...",
  "key_s" : "VTJGc2RHVmtYMS9aQXBIZldR1Kcy9DcDA..."
}
```
or:

```
_=U2FsdGVkX1%2B3x5Pr9RrsKmoarboK93vzCE3SenBiAovyItT6cYlcxiA3ox6PHZwy%0A%2Fk1kuBlEWMRfkskFJ4XrVuar3Wm3S5M6H4GW6ZsceLCy
%2FN5YCEjQa5JmBPgqQKsT%0AeAM012NdMlNg5sj7imL...
```

The response is an encrypted string:

```
U2FsdGVkX19wZpqbilaevDASG/DlJ1lnlC0oTvDJ3CqAXbyyUXdjzupIQVL58W6o6aQhDklq3kVQQlMsG6HRBJllJBmRwBdceOVZQ1OeQKnW...
```

Using Transport Key to decrypt the response we find:

```
{
  "id"       : 123,
  "username" : "some-user-name"
  "key_s"    : "VTJGc2RHVmtYMS9aQXBIZldRTjZ...",
  "key_t"    : "V3hVdlBma0lRR1l3Uys5ZWo4WFJ0e...",
}
```

**Summary**
`key_t` must be set locally to become the new Transport Key, while `key_s` is just an echo of the
already received cookie which represents the Session Storage Key. `Specimen.IO` will always send `key_s`
to the backend, or else personal session data is either lost, corrupt or deleted.

Sessions
------------

We have seen how communication between a server and a client can be secured, so what about
session management on the server-side? Besides using a key-value database with support for
expiration time, the most important thing to secure is visitor data.

The following is a very simple example of how the `get` and `set` methods of a custom session
handler should operate.

```
Set:
$data = Encryption::enc(
  base64_encode($data), $this->getInternalKey()
);
$_SESSION[$reserved_namespace][$key] = $data;

Get:
$data = Encryption::dec(
  $_SESSION[$reserved_namespace][$key], $this->getInternalKey()
);
return base64_decode($data);
```

`reserved_namespace` is a variable which controls where in the global `$_SESSION`-object custom
session data is stored. Using the root is not recommended!

> While `$_SESSION` is unique per user, it's storage is not.

No matter the storage of the session handler, it's data can only be read by the user that initiated
the request. So while a coordinated data mining attack could easily fetch the contents of all sessions on a
server, reading the actual contents is quite the opposite.

Breakdown
------------

**Égalité** provides the following:

  * Encrypted payload between client and server.
    * End-points will natively discard common probe attempts as the expected input is unencrypted
  * Hashed passphrase (SHA-256).
  * Server session variables are locked to the existing user using `key_s` (AES-256, 192 B)
  * IO provides a secondary transport channel with additional encryption.
    * Encrypted using `key_t` (AES-256, 192 B)
    * Supports WebSockets
  * Captcha designed for readability and style, without compromising base functionality.
    * Refreshes every 60 seconds
    * Provides `key_t`
  * Login channel is separate from regular API requests because the Transport Key change.
  * Developed in PHP and Javascript.
  * Works in all major web browsers.

**Furthermore, this project utilises:**

  * [jQuery](https://jquery.com)
  * GibberishAES [JS](https://github.com/mdp/gibberish-aes) &amp; [PHP](https://github.com/ivantcholakov/gibberish-aes-php)
  * [CryptoJS](http://code.google.com/p/crypto-js)
  * GD2 for PHP

**[Live Demo](https://docs.9eb.se/egalite/www/):**

  * [Bootstrap 2](http://getbootstrap.com/)
  * [php-markdown](https://github.com/michelf/php-markdown) to preview [README.md](./README.md)

**And expects...**

  * That you run this on your favourite Linux distribution.

Pros and cons
------------
  * Not suited for auto-login.
  * The methods described here in this document does not attempt to suggest or otherwise comment on
  API authentication and the use of tokens.
  * Encryption is illegal in some countries.
  * Don't be a douche.

Further reading
------------

Systems like these are not flawless, only more robust.

  * [PWNtcha by Caca Labs](http://caca.zoy.org/wiki/PWNtcha) - *"Pretend We're Not a Turing Computer
    But a Human Antagonist"*
  * [Javascript Cryptography Considered Harmful](https://www.nccgroup.trust/us/about-us/newsroom-and-events/blog/2011/august/javascript-cryptography-considered-harmful/) - Consider some of it's contents.

License
------------

Unless stated otherwise, all code etc by [Richard K. Szabó](https://richardkszabo.me) (2013-2015). Licensed under
[MIT](http://opensource.org/licenses/MIT).

  * Proof of concept: 2013
  * This demo: 2015
  * [Github](https://github.com/rszabo/egalite)

[Specimen](https://specimen.me)
