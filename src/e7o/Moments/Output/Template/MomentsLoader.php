<?php

namespace e7o\Moments\Output\Template;

use \e7o\Morosity\Loader\Loader;

class MomentsLoader implements Loader
{
	private $dir;
	
	public function __construct(string $rootDir)
	{
		if (substr($rootDir, -1) != '/') {
			$rootDir .= '/';
		}
		$this->dir = $rootDir;
	}
	
	public function load(string $file)
	{
		$possibleFilename = $this->dir . $file;
		if (!file_exists($possibleFilename)) {
			throw new \Exception('Template not found: ' . $file);
		}
		return file_get_contents($possibleFilename);
	}
}
