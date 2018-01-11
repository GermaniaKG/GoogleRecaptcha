# Germania KG · GoogleRecaptcha

**Callable wrapper, Slim3 Middleware and Pimple-style Service Provider for Google's [ReCaptcha.](https://www.google.com/recaptcha/admin)**


## Installation

```bash
$ composer require germania-kg/googlerecaptcha
```

Alternatively, add this package directly to your *composer.json:*

```json
"require": {
    "germania-kg/googlerecaptcha": "^1.0"
}
```


## Usage

The following examples assume you're working with [Pimple](https://pimple.symfony.com/) or [Slim Framework](https://www.slimframework.com/) and have your [DI container](https://www.slimframework.com/docs/concepts/di.html) at hand:

```php
$app = new Slim\App;
$dic = $app->getContainer();
$dic = new Slim\Container;
$dic = new Pimple\Container;
```


------

### ServiceProvider

See chapter [The services in detail](#the-services-in-detail) to see which services and resources are offered. For public and secret test keys, see the official [reCAPTCHA v2 FAQ](https://developers.google.com/recaptcha/docs/faq).

```php
<?php
use Germania\GoogleRecaptcha\GoogleRecaptchaServiceProvider;

// Officially intended for test purposes; see FAQ
$public_key = "lots_of_characters_here";
$secret_key = "secret_bunch_of_characters_here";

// Pass keys to ServiceProvider
$recaptcha_services = new GoogleRecaptchaServiceProvider( $public_key, $secret_key );
// ... and optionally a PSR-3 Logger
$recaptcha_services = new GoogleRecaptchaServiceProvider( $public_key, $secret_key, $logger );

// Now register; all services will transparently be added to the $dic
$dic->register( $recaptcha_services );
```

------

### Slim3-style Middleware

Before the route controller is executed, this middleware checks 

1. whether there is a recaptcha user input in `$_POST['g-recaptcha-response']` 
2. whether the validation with Google's *ReCaptcha* client succeeds. 

Dependent on the results, this middleware does

- Store the validation result in a *Request* attribute named `GoogleRecaptcha`. 
- When the validation fails, add a `400 Bad Request` status code to the *Response* object. 

The string identifiers used here can be modified in the [**Google.Recaptcha.Config**](#googlerecaptchaconfig) service, see below.

```php
<?php
use Germania\GoogleRecaptcha\GoogleRecaptchaMiddleware;

// 1. Add Service provider first
// 2. Create route
$route = $app->post('/new', function() { ...} );

// 2. Add route middleware
$route->add( 'Google.Recaptcha.Middleware' );
```

**IMPORTANT NOTICE: The middleware will call the *$next* middleware, regardless of the validation status.** Any route controller should check for the HTTP status itself and react accordingly. The `GoogleRecaptcha` Request attribute array will help – just ask for *failed* or *success* or *status* elements.

```php
$route = $app->post('/new', function(Request $request, Response $response) { ...

	// Grab
	$recaptcha_status = $request->getAttribute("GoogleRecaptcha");

	// All these are boolean
	if ($recaptcha_status['failed']) { ... }
	if ($recaptcha_status['success']) { ... }
	if ($recaptcha_status['status'] == true) { ... }
});
```



------

### Callable validation wrapper

The *ReCaptcha* validation client is instantiated automatically. The callable wrapper uses the same logger instance than the [*ServiceProvider*](#serviceprovider).  See section [**Google.Recaptcha.Validator**](#googlerecaptchavalidator) on how to setup your own validator instance.

```php
<?php
// 1. Add Service provider first
// 2. Grab service. 
$callable_recaptcha = $dic['Google.Recaptcha.Validator.Callable'];

// TRUE or FALSE
$valid = $callable_recaptcha( $_POST['g_recaptcha_response'], $_SERVER['REMOTE_ADDR'] );
```
--


## The services in detail

#### Google.Recaptcha.PublicKey
This is the service you surely will need most often.

```php
<?php
$public_key = $dic['Google.Recaptcha.PublicKey'];

echo $twig->render('form.tpl', [
	'recaptcha_key' => $public_key
]);
```


#### Google.Recaptcha.Logger
The default logger has been passed on instantiation. Override or customize like this:

```php
<?php
$dic->extend('Google.Recaptcha.Logger', function($default_logger, $dic) {
	$custom_logger = $dic['CustomLogger'];
    return $custom_logger->withName( "CustomLogger" );
});
```

#### Google.Recaptcha.ClientIP
The client API is used to ask Googles web API; its default is `$_SERVER['REMOTE_ADDR']`. You normally will not need to override this:

```php
<?php
$dic->extend('Google.Recaptcha.ClientIP', function($server_remote_addr, $dic) {
	$ip = 'whatever'
    return $ip;
});
```

#### Google.Recaptcha.Validator  
This creates Google's server-side validation client which comes with the official [ReCaptcha\ReCaptcha](https://packagist.org/packages/google/recaptcha) library. It will be automatically installed with this *GoogleRecaptcha* package and automatically instantiated. If you wish to create your own, do something like:

```php
<?php
use ReCaptcha\ReCaptcha;

$dic->extend('Google.Recaptcha.Validator', function($default, $dic) {
	$secret = $dic['Google.Recaptcha.SecretKey'];
	$my_recaptcha = new Recaptcha( $secret );
    return $my_recaptcha;
});
```


#### Google.Recaptcha.Validator.Callable
is a callable wrapper, i.e. an invokable class, around the [**Google.Recaptcha.Validator**](#googlerecaptchavalidator) service. This executable will return exactly **true** or **false.** It uses the [**Google.Recaptcha.Logger**](#googlerecaptchalogger) instance from above, logging an *info* on success and a *notice* on failure.

```php
<?php
// Most simple:
$callable_recaptcha = $dic['Google.Recaptcha.Validator.Callable'];

// Validate a form:
$recaptcha_input = $_POST['g-recaptcha-response'];
$remote_ip = $dic['Google.Recaptcha.ClientIP'];

$valid = $callable_recaptcha( $recaptcha_input, $remote_ip);

```

When instantiated manually, it accepts an optional logger. 

```php
<?php>
use Germania\GoogleRecaptcha\GoogleRecaptchaCallable;

$validator = $dic['Google.Recaptcha.Validator'];
$callable_recaptcha = new GoogleRecaptchaCallable( $validator );
// Optionally with custom Logger
$callable_recaptcha = new GoogleRecaptchaCallable( $validator, $logger );
```


#### Google.Recaptcha.Config
This configuration array is used by the [**GoogleRecaptchaMiddleware**](#slim3-style-middleware) and provides these values:

field | value | description
:-----|:------|:-----------
input_field | `g-recaptcha-response` | The form field name
status_code | `400` | The *Response* status when validation fails
request_attribute | `GoogleRecaptcha` | The *Request* attribute name for validation information


```php
<?php
$dic->extend('Google.Recaptcha.Config', function($default, $dic) {
    return array_merge($default, [
        'request_attribute' => 'custom_attr_name'
    ]);
});
```




## Development

```bash
$ git clone https://github.com/GermaniaKG/GoogleRecaptcha.git
$ cd GoogleRecaptcha
$ composer install
```


## Unit tests

Either copy `phpunit.xml.dist` to `phpunit.xml` and adapt to your needs, or leave as is. 
Run [PhpUnit](https://phpunit.de/) like this:

```bash
$ vendor/bin/phpunit
```
