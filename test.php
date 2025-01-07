<?php

require('vendor/autoload.php');

function callMethod($obj, $function, $args = array())
{
	$object = is_object($obj);
	$method = new \ReflectionMethod($object ? get_class($obj) : $obj, $function);
	$method->setAccessible(true);
	return $method->invokeArgs($object ? $obj : null, $args);
}

