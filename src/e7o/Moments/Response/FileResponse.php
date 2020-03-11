<?php

namespace e7o\Moments\Response;

class FileResponse extends Response
{
	protected $file;
	protected $mime;
	
	public function __construct($file, $mime)
	{
		$this->file = $file;;
		$this->mime = $mime;
	}
	
	public function render()
	{
		header('Content-Type: ' . $this->mime);
		readfile($this->file);
	}
}