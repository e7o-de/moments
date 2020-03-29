== About ==

Moments is a small, young framework for building web applications. The focus is
on less magic (no second code parser to figure out pseudo annotations),
simplicity (no complex configurations if you don't need it) and a less-is-more
approach: stuff is not tied too tightly into specifics of a framework.

== Quickstart ==

Just run:

```
composer require e7o/moments
vendor/bin/init-moments
```

Do your small homework (like creating a .gitignore for vendor/ amd so) and start

== Setup in nginx ==

This is the approach you can go:

```
location /your-project/public {
	try_files $uri /your-project/public/index.php;
}
```

This is an example configuration for dev purposes, for production you should use
`location /`, of course.
