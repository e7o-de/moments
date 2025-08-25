<?php

namespace e7o\Moments\Response;

class ErrorResponse extends Response
{
	public function __construct($errorCode, $reason)
	{
		parent::__construct($reason);
		$this->setResponseCode($errorCode);
	}
}
