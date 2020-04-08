<?php

namespace e7o\Moments\Response;

/**
* Redirects to another page.
* 
* You have to provide an absolute URL according to the standards!
*/
class RedirectResponse extends Response
{
	private $destination;
	private $code;
	
	public function __construct($absoluteRedirectUrl, $code = 302)
	{
		$this->destination = $absoluteRedirectUrl;
		$this->code = $code;
	}
	
	public function render()
	{
		header('Location: ' . $this->destination, true, $this->code);
	}
}
