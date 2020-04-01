<?php

namespace e7o\Moments\Bundles;

use \e7o\Moments\Request\Controllers\MomentsController;

class BundleManager
{
	private $conf;
	
	public function __construct($configFile)
	{
		$this->conf = [];
		if (file_exists($configFile)) {
			$content = json_decode(file_get_contents($configFile), true);
			if ($content !== null) {
				$this->conf = $content;
			}
		}
	}
	
	public function getRoutes()
	{
		return $this->conf['routes'];
	}
	
	public function getServices()
	{
		return $this->conf['services'];
	}
	
	public function addRequiredAssets(MomentsController $toController)
	{
		foreach ($this->conf['include-scripts'] ?? [] as $asset) {
			$toController->addScript($asset);
		}
		foreach ($this->conf['include-styles'] ?? [] as $asset) {
			$toController->addStylesheet($asset);
		}
	}
}
