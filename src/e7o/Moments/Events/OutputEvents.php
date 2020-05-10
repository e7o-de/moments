<?php

namespace e7o\Moments\Events;

class OutputEvents
{
	/**
	* Just an example - not recommended for actual use, as the formatting is
	* not the best.
	*/
	public static function formatHtml($moment, $html)
	{
		$dom = new \DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadHTML($html);
		$dom->formatOutput = true;
		$html = $dom->saveHTML();
		return $html;
	}
}
