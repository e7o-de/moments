<?php

namespace e7o\Moments\Tests\Request\Routers;

use \e7o\Moments\Request\Routers\SimpleRouter;
use \PHPUnit\Framework\TestCase;

class SimpleRouterTest extends TestCase
{
	private $plaintextRoutes = [
		['id' => 'a', 'route' => '/a/'],
		['id' => 'b', 'route' => '/b/'],
		['id' => 'c', 'route' => '/c'],
	];
	
	private $regexRoutes = [
		['id' => 'a', 'route' => '/a/{number:[5-9]+}/'],
		['id' => 'b', 'route' => '/someother'],
		['id' => 'c', 'route' => '/{filename:[0-9a-z.]+}'],
	];
	
	public function testPlaintextRoutesSlashes()
	{
		$r = new SimpleRouter($this->plaintextRoutes);
		
		$f = callMethod($r, 'findRoute', ['/a/']);
		$this->assertEquals('a', $f['id']);
		
		$f = callMethod($r, 'findRoute', ['/a']);
		$this->assertEmpty($f);
		
		$f = callMethod($r, 'findRoute', ['/c/']);
		$this->assertEmpty($f);
		
		$f = callMethod($r, 'findRoute', ['/c']);
		$this->assertEquals('c', $f['id']);
	}
	
	public function testPlaintextRoutesSimilar()
	{
		$r = new SimpleRouter($this->plaintextRoutes);
		
		$f = callMethod($r, 'findRoute', ['/anotherone']);
		$this->assertEmpty($f);
		
		$f = callMethod($r, 'findRoute', ['/creepyone']);
		$this->assertEmpty($f);
	}
	
	public function testPlaintextRoutesNotFound()
	{
		$r = new SimpleRouter($this->plaintextRoutes);
		
		$f = callMethod($r, 'findRoute', ['/x']);
		$this->assertEmpty($f);
	}
	
	public function testSimpleParams()
	{
		$t = [
			['id' => 'a', 'route' => '/a/{number}/'],
		];
		$r = new SimpleRouter($t);
		
		$f = callMethod($r, 'findRoute', ['/a/1234/']);
		$this->assertEquals('a', $f['id']);
		$this->assertEquals(['number' => 1234], $f['parameters']);
	}
	
	public function testComplexParams()
	{
		$t = [
			['id' => 'a', 'route' => '/{type}/{number:[0-9]+}/'],
		];
		$r = new SimpleRouter($t);
		
		$f = callMethod($r, 'findRoute', ['/product/1234/']);
		$this->assertEquals('product', $f['parameters']['type']);
		$this->assertEquals(1234, $f['parameters']['number']);
	}
	
	public function testComplexRegexWithBrackets()
	{
		$t = [
			['id' => 'a', 'route' => '/{type}/{number:[0-9]{1,5}}/'],
		];
		$r = new SimpleRouter($t);
		
		$f = callMethod($r, 'findRoute', ['/product/1234/']);
		$this->assertEquals('product', $f['parameters']['type']);
		$this->assertEquals(1234, $f['parameters']['number']);
	}
	
	public function testRegexParams()
	{
		$r = new SimpleRouter($this->regexRoutes);
		
		$f = callMethod($r, 'findRoute', ['/somefile.js']);
		$this->assertEquals('c', $f['id']);
		
		$f = callMethod($r, 'findRoute', ['/someother']);
		$this->assertEquals('b', $f['id']);
		
		$f = callMethod($r, 'findRoute', ['/similar.css/']);
		$this->assertEmpty($f);
	}
	
	public function testRegexParamRestrictions()
	{
		$r = new SimpleRouter($this->regexRoutes);
		
		$f = callMethod($r, 'findRoute', ['/a/5678/']);
		$this->assertEquals('a', $f['id']);
		
		$f = callMethod($r, 'findRoute', ['/a/1234/']);
		$this->assertEmpty($f);
	}
}

