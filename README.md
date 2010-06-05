Traffic
========

Traffic is a micro-routing framework for PHP 5.3+. It is inspired by, but not a copycat of
[Sinatra](http://www.sinatrarb.com/).

Routes
=======

In Traffic, you match routes against the current URL request and if it matches, the callback function
is invoked.

A route is a combination of a request method, and a URL matching pattern:

    use Fu\Traffic as t;

    // simplest kind of rule
    t::get('/some/path', function () {
       echo 'I hit /some/path';
    });

Nested Routes
--------------

Routes can also be nested to make code a little more structured and concise:

    use Fu\Traffic as t;

    t::rel('/users', function () {

       // ommit the first path argument as it's not needed
       t::get(function(){
        echo 'I hit /users';
       });

       t::get('/roles', function(){
        echo 'I hit /users/roles';
       });

       t::post('/roles', function(){
        echo 'I want to create a new user role';
       });

    });

Labels & Splats
----------------

Routes can also include :labels which match anything up until the next / and splats (\*) that are the equivalent of .\*.

These labels are made available in the first argument returned to the callback and also available via the params() method:

    use Fu\Traffic as t;

    // nesting routes, next rules matches a request that equals /users or /users/*
    t::get('/user/:username', function ($params) {
      echo 'I hit /user/'.$params['username'];
    });

    // brackets make an optional part of the matching pattern
    t::get('/downloads/*(.*)', function ($params) {
      echo 'I want to download a file with the following name: '.$params['splats'][0]."\n";
      if ($params['splats][1]) {
        echo 'and with the extension: '.$params['splats'][1];
      }
    });


Request Methods
----------------

Traffic supports the following HTTP request methods:

- GET
- POST
- PUT
- DELETE

As PUT and DELETE are not supported in most web browsers, you can mimick these requests by using POST and then passing through a _method parameter with a value of PUT or DELETE:

    use Fu\Traffic as t;

    // relative_to is an alias to rel, in case you prefer to be more verbose
    t::relative_to('/user/:username', function () {
      t::get(function ($params) {
        echo 'Viewing user:'.$params['username'];
      });

      t::post(function ($params) {
        echo 'Creating user:'.$params['username'];
      });

      t::put(function ($params) {
        echo 'Updating user:'.$params['username'];
      });

      t::delete(function ($params) {
        echo 'Deleting user:'.$params['username'];
      });
    });


Options
--------

Traffic has various options that can be used to tweak how it behaves.

These options can be set per rule or globally to apply to all subsequent rules:

- agent: a string or regex to match against the HTTP_USER_AGENT
- exit_after_callback: whether to exist PHP execution after a request has successfully matched. true=yes, false=no and all subsequent calls to Traffic will be ignored unless unexit() is called.

### Agent

    use Fu\Traffic as t;

    t::get('/', function () {
        echo 'Enter 2001 mode.';
    }, array('agent' => 'MSIE 6'));

    t::options('agent', 'MSIE 6');

### Qualifying labels with a Regex

Labels (e.g. :userid) can be qualified with a regular expression. The regular expression is passed in as an option and can be either a full regex (e.g. /^[a-z]+$/i), or a simpler regex which Traffic will wrap up (e.g. [a-z]+ becomes /^[a-z]+$/i):

    use Fu\Traffic as t;

    t::get('/users/:userid', array(':userid' => '[a-z]+'), function () {
        echo 'Enter 2001 mode.';
    });

    t::options(':userid', '[a-z]+'); // ensures all routes with :userid are qualified in every route


Other Methods
--------------

Traffic has various other methods to help the flow of your routing or to help respond to a request.


### register_global_functions()

Calling register_global_functions() will declare all of the functoin described here in the global namespace.

This will allow you to be more concise, but you must be sure that there will be no clashes.

    Fu\Traffic::register_global_functions();

    // simplest kind of rule
    get('/some/path', function () {
      var_dump(params());
      echo 'I hit /some/path';
    });

### request()

Acts the same as get(), post() et al, but doesn't care about the request method.

### not_found()

Send a 404 response and runs a callback function if defined.

This could be used as the last rule, if no other patters match or as a programmatic response to a query which appears valid, e.g. if you have a username on the query string but you cannot find that user in the DB, call not_found():

    use Fu\Traffic as t;

    // relative_to is an alias to rel, in case you prefer to be more verbose
    t::get('/users/:username', function ($params) {
        if (!find_user($params['username'])) {
            not_found();
        }

        echo 'Viewing user:'.$params['username'];
    });

    not_found(function(){
      echo 'Page Not Found (404)';
    });

### halt()

Same as not_found but doesn't send a 404 http response.

### pass()

Pass is really useful if you want to match against a rule, but then let traffic match against further rules. E.g. you want to make all requests to *.rss have a text/xml header sent:

    use Fu\Traffic as t;

    t::get('*.rss', function(){
	  t::content_type('rss'); // read on…
	  t::pass();
	});

### request_method()

Will return the request method for the current request, taking into account the _method hack for PUT and DELETE.

### request_uri()

Returns the request URI used for matching

### content_type()

Will send a header('Content-Type: …') based on an extension:

    use Fu\Traffic as t;

    t::get('*.css', function(){
	  t::content_type('css');
	  t::pass();
	});

### http_response ()

Pass it the HTTP Response code, and it'll send the appropriate HTTP 1.1 header:

    use Fu\Traffic as t;
    t::http_response(500); // server error

### unexit ()

Internally used for testing after Trsffic has matched a rule.

Miscellaneous
--------------

Traffic allows you to pass arguments to get, post, put, delete & rel/relative_to in any order you wish. The callback function is the only required argument:

    use Fu\Traffic as t;
    t::register_global_functions();

    // valid
    get('/:tinyurl', function(){}, array(':tinyurl' => '[a-z0-9]{6}'));
    get('/:tinyurl', array(':tinyurl' => '[a-z0-9]{6}'), function(){});
    get(function(){});
    get(function(){}, '/:tinyurl', array(':tinyurl' => '[a-z0-9]{6}'));

    // invalid
    get('/:tinyurl');


### Example Mod Rewrite Rules to throw into you Apache config of .htaccess

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_URI} !^/favicon.ico$
    RewriteRule ^(.*)$ index.php [QSA,L]


TO DO
======


License
========

<a rel="license" href="http://creativecommons.org/licenses/by/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by/3.0/88x31.png" /></a><br /><span xmlns:dc="http://purl.org/dc/elements/1.1/" property="dc:title">Traffic</span> by <a xmlns:cc="http://creativecommons.org/ns#" href="http://kelvinjones.co.uk" property="cc:attributionName" rel="cc:attributionURL">Kelvin Jones</a> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 Unported License</a>.