<?php

namespace e7o\Moments\Response;

class FileResponse extends Response
{
	protected $file;
	protected $mime;
	
	public function __construct($file, $mime = null)
	{
		$this->file = $file;
		$this->mime = $mime;
	}
	
	public function render()
	{
		if ($this->mime !== null) {
			header('Content-Type: ' . $this->mime);
		}
		if ($this->file instanceof \Closure) {
			$f = $this->file;
			$f();
		} else {
			readfile($this->file);
		}
	}
}
