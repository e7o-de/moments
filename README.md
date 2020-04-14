# About

Moments is a small, young framework for building web applications. The focus is
on less magic (no second code parser to figure out pseudo annotations),
simplicity (no complex configurations if you don't need it) and a less-is-more
approach: stuff is not tied too tightly into specifics of a framework.

# Basic usage

## Quickstart

Just run:

```bash
composer require e7o/moments
vendor/bin/init-moments
```

This will modify/create your `composer.json`, create some scripts, place some
general template so you can start and adds a routing. Do your small homework
(like creating a `.gitignore` for vendor/ and so) and just go.

For now you might have to add a `composer.json` with this before when you're
seeing an error about minimum-stability:

```json
{
	"minimum-stability": "dev"
}
```

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

# Functionality - some documentation

Might not be completed or the best explanation yet. Also, don't forget to check the
docblock comments of classes/methods before calling, they contain useful information
as well. Some day that stuff will be collected in one place, but until then ...

## Template variables

Some important variables:

- `{{ $.meta }}` - all the important ressource and meta tag inclusion you don't
  want to care about.
- `{{ $.assets }}` - the path to where your personal assets (from public dir) are
  placed.

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
- `include-scripts' and `include-styles' indicate which scripts should be automatically
  included in the template (if the template is using `$.meta`). Don't forget to add
  the correct asset dir name, as we don't use magic here.
- `services` and `routes` will work as in every other config file as well.

```json
{
	"scripts": [
		"fancy-setup-script.php"
	],
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