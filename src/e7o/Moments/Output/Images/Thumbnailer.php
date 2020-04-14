<?php

namespace e7o\Moments\Output\Images;

use \e7o\Moments\Response\FileResponse;

/**
 * Simple Thumbnailer script. Example usage in a controller:
 * 
 * return Thumbnailer::start()
 * 	 ->fromFile($moment->getBasePath() . '/data/file-' . $id)
 * 	 ->setCacheName($moment->getBasePath() . '/data/thumb-100x100-' . $id)
 * 	 ->setDimensions(100, 100)
 * 	 ->getResponse()
 * 	;
 * 
 * Ensure that your cache is deleted when using this feature and updating the
 * original image. You're responsible as well for checking if the file exists;
 * otherwise it might come to an error.
 * 
 * Also note, that it requires the GD lib (check with gd_info or phpinfo).
 */
class Thumbnailer
{
	private $filename;
	private $cache = null;
	private $width = null;
	private $height = null;
	private $keepAspect = true;
	private $backgroundColor = null;
	
	public static function start()
	{
		return new Thumbnailer();
	}
	
	public function __construct()
	{
	}
	
	public function fromFile(string $filename)
	{
		$this->filename = $filename;
		return $this;
	}
	
	public function setCacheName(string $filename)
	{
		$this->cache = $filename;
		return $this;
	}
	
	/**
	* Specifies the size; at least one parameter must be a meaningful
	* number.
	*/
	public function setDimensions(int $w = null, int $h = null)
	{
		$this->width = $w;
		$this->height = $h;
		return $this;
	}
	
	public function keepAspectRatio(bool $v)
	{
		$this->keepAspect = $v;
		return $this;
	}
	
	public function setBackgroundColor(int $r, int $g, int $b)
	{
		$this->backgroundColor = [$r, $g, $b];
		return $this;
	}
	
	public function setBackgroundTransparent()
	{
		$this->backgroundColor = null;
		return $this;
	}
	
	public function getResponse()
	{
		if (file_exists($this->cache)) {
			return new FileResponse($this->cache, 'image/png');
		}
		
		$needTransparency = false;
		$info = getimagesize($this->filename);
		switch ($info['mime']) {
			case 'image/jpeg':
				$iSrc = imagecreatefromjpeg($this->filename);
				break;
			case 'image/png':
				$iSrc = imagecreatefrompng($this->filename);
				$needTransparency = true;
				break;
			case 'image/gif':
				$iSrc = imagecreatefromgif($this->filename);
				break;
		}
		
		$width = $this->width;
		$height = $this->height;
		if ($this->keepAspect) {
			$aspectSource = $info[1] / (float)$info[0];
			if (empty($this->width)) {
				$width = $height * $aspectSource;
				if ($width > $this->width) {
					$width = $this->width;
					$height = $width * $aspectSource;
				}
			} else {
				$height = $width * $aspectSource;
				if (!empty($this->height) && $height > $this->height) {
					$height = $this->height;
					$width = $height / $aspectSource;
				}
			}
		}
		
		$frameWidth = $this->width ?: $width;
		$frameHeight = $this->height ?: $height;
		$iDst = imagecreatetruecolor($frameWidth, $frameHeight);
		
		if ($needTransparency) {
			$backgroundTransparent = imagecolorallocate($iDst, 20, 30, 40);
			imagecolortransparent($iDst, $backgroundTransparent);
			imagefilledrectangle($iDst, 0, 0, $frameWidth, $frameHeight, $backgroundTransparent);
		}
		
		$x = ($frameWidth - $width) >> 1;
		$y = ($frameHeight - $height) >> 1;
		imagecopyresampled($iDst, $iSrc, $x, $y, 0, 0, $width, $height, $info[0], $info[1]);
		
		$cache = $this->cache;
		return new FileResponse(
			function () use ($iDst, $cache) {
				imagepng($iDst);
				if (!empty($cache)) {
					imagepng($iDst, $cache);
				}
				imagedestroy($iDst);
			},
			'image/png'
		);
	}
}
