<?php

namespace e7o\Moments\Bundles;

use \e7o\Moments\Request\Controllers\MomentsController;
use \e7o\Moments\Configuration\Configuration;

class BundleManager implements Configuration
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
	
	public function get(string $name, $default = null)
	{
		return $this->conf[$name] ?? $default;
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
