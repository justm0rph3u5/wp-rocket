<?php

namespace WP_Rocket\Tests\Unit\inc\Engine\Preload\Controller\CheckExcludedTrait;

use Mockery;
use WP_Rocket\Engine\Preload\Controller\CheckExcludedTrait;
use WP_Rocket\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * @covers \WP_Rocket\Engine\Preload\Controller\CheckExcludedTrait::is_excluded
 * @group  Preload
 */
class Test_IsExcluded extends TestCase
{
	protected $trait;

	protected function setUp(): void
	{
		parent::setUp();
		$this->trait = Mockery::mock(CheckExcludedTrait::class)->makePartial();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnAsExpected($config, $expected) {
		Functions\expect('get_rocket_cache_reject_uri')->andReturn($config['excluded_urls']);
		$method = $this->get_reflective_method('is_excluded',  get_class($this->trait));
		$this->assertSame($expected, $method->invokeArgs($this->trait,[$config['url']]));
	}
}
