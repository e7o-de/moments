<?php

namespace e7o\Moments\Rpc;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;
use \e7o\Moments\Response\Response;

class Communicator
{
	private Moment $moment;
	private bool $prepared = false;
	
	public function __construct(Moment $moment)
	{
		$this->moment = $moment;
	}
	
	private function prepare()
	{
		if ($this->prepared) {
			return;
		}
		
		header('X-Accel-Buffering: no');
		header('Content-Encoding: none'); // this is the secret for nginx/fpm
		ob_implicit_flush(1);
		ob_end_flush();
		
		$this->prepared = true;
	}
	
	public function send($jsonObj)
	{
		$this->prepare();
		$data = json_encode($jsonObj, JSON_PRETTY_PRINT);
		echo $data;
		ob_flush();
	}
}
