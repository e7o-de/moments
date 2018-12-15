<?php

namespace e7o\Moments\Request\Controllers;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;
use \e7o\Moments\Response\Response;

interface Controller
{
	public function __construct(Moment $moment);
	public function handleRequest(Request $request, $route): Response;
}
