<?php

namespace e7o\Moments\Response;

class JsonResponse extends Response
{
	protected $content;
	
	public function __construct(array $content)
	{
		$this->content = $content;
	}
	
	public function render()
	{
		header('Content-Type: application/json');
		// TODO: Remove the pretty-print flag at some point ;)
		echo json_encode($this->content, JSON_PRETTY_PRINT);
	}
}
