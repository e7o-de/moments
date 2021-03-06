# About

Moments is a small, young framework for building web applications. The focus is
on less magic (no second code parser to figure out pseudo annotations),
simplicity (no complex configurations if you don't need it) and a less-is-more
approach: stuff is not tied too tightly into specifics of a framework.

# Contribute?

If you're interested, you're welcome! Easiest ways:

- fork and do a pull request
- create a bundle with your functionality
- blog about the framework :)

Please check how the coding style is (close to PSR-2, without the stupid things
like the spaces instead of tabs). Please ensure your editor is configured to
touch only lines you actually touched (no "kill all spaces and reformat the whole
file" things). Do clean commits (one commit per functionality)!

# Basic usage

## Terms

There's only one important/not-common term important: `moment`. A moment equals basically
the page request itself and handles "everything". It also is the container where
you can get the services from.

## Quickstart

Just run:

```bash
composer require e7o/moments
vendor/bin/init-moments
```

This will modify/create your `composer.json`, create some scripts, place some
general template so you can start and adds a routing. Do your small homework
(like creating a `.gitignore` for vendor/ and so) and just go ahead. Don'T forget
to check the created config file, as there might be some example entries you
don't need or want, as they'll consume a little bit of CPU time (like the HTML
formatter).

For now you might have to add a `composer.json` with this before when you're
seeing an error about minimum-stability:

```json
{
	"minimum-stability": "dev"
}
```

## Setup PHP dev server

Execute in your project directory:

```
php -S localhost:8000
```

Now it's ready to run on http://localhost:8000/public/. If you're developing a webservice
and the client won't connect, check `nestatat` if it bound to IPv6 only, this seems to be
an issue in some cases (like on MacOS).

If you really wanna abuse this server for production or debugging purposes in a team
environment, check the env variable `PHP_CLI_SERVER_WORKERS`.

## Setup in nginx

This is the approach you can go:

```
location ~ /your-project/assets/(?<loc>.+) {
		try_files $uri /your-project/public/assets/$loc;
}
location /your-project {
		try_files $uri /your-project/public/index.php;
}
```

This is an example configuration for dev purposes, for production you should use
`location /`, of course.

If you're getting an error, check, if you have configured this:

```
fastcgi_param  REQUEST_URI        $request_uri;
fastcgi_param  DOCUMENT_URI       $document_uri;
```

There are some more, but usually there's a predefined config you or your operating systems
package manager is putting there.

Btw, if this variables are available and filled, it might be, that other servers will work
as well.

## Setup in Apache

Enable `mod_rewrite` by `a2enmod rewrite`. Either put this into your `.htaccess` (remember that `AllowOverride None`
is a no-go in this case) or into the configuration file:

```
RewriteEngine On
RewriteRule ^/your-project/assets/(.*)$ /your-project/public/assets/$1 [L]
RewriteRule ^/your-project/(.*)$ /your-project/public/index.php
```

Same as for nginx: Remove `your-project/` for production.

# Functionality - some documentation

Might not be completed or the best explanation yet. Also, don't forget to check the
docblock comments of classes/methods before calling, they contain useful information
as well. Some day that stuff will be collected in one place, but until then ...

## Data directories

For files and directories just used for storing data, you can specify in
`config/files.json` an automatic creation and chmodding:

```javascript
[
	{
		"type": "directory",
		"name": "data",
		"chmod": "0766"
	},
	{
		"type": "file",
		"name": "logfile.txt",
		"chmod": "0123",
		"chown": "www-data"
	},
	{
		"type": "file",
		"name": "data.sqlite",
		"copy": "bin/database-template.sqlite"
	}
],
```

It will not overwrite or delete existing files/directories. It will, however, change
the access rights, so you can commit some contents to your repository and still ensure
it's writeable. However, it might be, that you have to be root for the chown operation.

## Routes

See docblock on `\e7o\Moments\Request\Routers\SimpleRouter` and the examples in
`config/default.json`.

## Template

Moments is using Morosity.

Some important variables:

- `{{ $.meta }}` - all the important ressource and meta tag inclusion you don't
  want to care about.
- `{{ $.assets }}` - the path to where your personal assets (from public dir) are
  placed.
- `{{ $.route }}` - information about the current route. Especially `{{ $.route.requesturi }}`
  is interesting for form actions etc.

To build a route in template, you can use the `route` function:

```
{{ route('settings') }}
```

## Login/Authentication

Moments has a build-in authentication support, which you just have to fill with a connector
to your user database or whatever. Feel free to put credentials in a ActiveDirectory
or just in a simple text file.

Btw, nobody is stopping you from ignoring this functionality and do your own checks on top
of every controller action, if that's a legit use case.

Important: You cannot combine the different methods (you can, somehow, but it's neither
useful nor officially supported).

### isAllowed() method

If you extend from `MomentsController` (or, even better, from a `YourProjectController`, which is
extending from `MomentsController`), you can just overwrite this method for a pretty basic
check possibility (getRoute() and getRequest() are available at this time):

```php
public function isAllowed()
{
	return true;
}
```

Depending on the return value, there'll be different actions possible.

- `true` will just allow the request.
- a `Response` will just output the response like in every other controller action.
- a string response will take this route instead. This could be your login form which
  sets a cookie you're checking in the method.
- every other return value -- `false` is recommended -- will produce an error.

### Authenticator class

Create a service called `authenticator`. This has the advantage of being available
in every controller.

```javascript
"authenticator": {
	"class": "\\ACME\\YourProject\\Core\\Authenticator",
	"args": ["@moment"]
}
```

You can extend from the `MomentsAuthenticator`:

```
use \e7o\Moments\Request\Authentication\Authenticator as MomentsAuthenticator;
```

See `SimpleConfigAuthenticator` and `SimpleDatabaseAuthenticator` for example
implementations. You can also extend from them -- or just throw one of them into
the service config if the defaults fit your needs (check the security they're
providing, it's not the highest standard).

You don't have to implement everything, if you don't need a `getCurrentUser()`
(because you do not differentiate), you can just ignore this one. Just overwrite
everything you need.

As you do have the `$route` parameter with all the data specified in the route, you
can easily add your own custom parameters to the routes (like required "user groups",
check ip addresses or allow access based on the current time).

You can safely get the authenticator by calling `$this->getAuthenticator()` in your
controller.

## Building a bundle

Bundles have to use PSR-4 autoloading, but hey, you should use it anyways for a new
project :) In theory, you can have more than one bundle per repository, but this isn't
recommended, so users of it can be selective.

Your bundle has to have a `moments-bundle.json` with some valid json in it. In theory,
this file could be empty, besides a `{}`.

You can specify some stuff, like
- in `scripts` a list of plain php scripts which should do some tasks before integration.
  Here you can download the most recent version of a fancy JS library or so. It should be
  possible to write to the directories, as the script is run by composer.
- in `assets` you can specify assets folders to symlink to the public directory.
  `from` is the path in your bundle, `to` the path in the assets directory
- in `routes` some additional endpoints ...
- `include-scripts` and `include-styles` indicate which scripts should be automatically
  included in the template (if the template is using `$.meta`). Don't forget to add
  the correct asset dir name, as we don't use magic here.
- `services` and `routes` will work as in every other config file as well.

```json
{
	"assets": [
		{
			"from": "assets",
			"to": "fancy-bundle"
		}
	],
	"include-scripts": [
		"fancy-bundle/fancylib-latest.js"
	],
	"include-styles": [
		"fancy-bundle/fancylib-latest.css"
	]
}
```

The `prepare-moments` script will run after `composer install`, so it will integrate
into the destination project at this point.

Example required? Just add `e7o/moments-material-bundle`. This brings you some material design
related fonts into your project.

## Events

Main usecase is related to bundles. As Moments itself is a bundle as well, you can use some
of the predefined example events by specifying them in your config:

```json
"events": {
	"output:html": [
		"\\e7o\\Moments\\Events\\OutputEvents::formatHtml"
	]
}
```

(This `formatHTML` uses the libxml formatting, which is kind of chaotic, so it's not
recommended for production usage.)

The functions must be static, but can instantiate objects.

Otherwise, can specify them in your bundle configuration like this:

```json
"events": {
	"output:html": [
		"\\ACME\\FancyBundle\\Events::randomEvent",
	]
}
```

Available events (the Moment itself will be passed in any case as first argument,
but it's not listed in the following parameter lists):

- authentication
  - `authentication:failed($user)` and `authentication:succeeded($user)` will be fired
    on the corresponding happening (at least as long the methods of authenticator
    are not overwritten (or at least called with `parent::`))
- output
  - `output:html (string $html):string` - post-processing of your rendered template
    (works only there, if you return a `Response` object, it won't fire, as Moments
    doesn't know, what it produces).
- controller
  - `controller:finished($controller, $returned)` - after controller was called,
    before output. Allows you to call `addMetaTag` etc.

If you specify your own events in your bundle or config, you can also call them
just with `$moment->callEvents($name, ...$args)`.

## Further functionality

The following components have docblock comments explaining the functionality.
At some point in time, when we're having a real documentation, it will be available
there, for now please check the file directly:

- \e7o\Moments\Output\Forms\Generator
- \e7o\Moments\Database\Connection
- \e7o\Moments\Output\Images\Thumbnailer
- \e7o\Moments\Request\Routers\SimpleRouter
