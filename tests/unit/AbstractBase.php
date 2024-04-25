<?php

namespace StellarWP\Pipeline\Tests\Unit;

class AbstractBase extends \PHPUnit\Framework\TestCase {
	/**
	 * @var string The root directory of the library.
	 */
	protected string $root;

	/**
	 * @var string The root directory of the test suite.
	 */
	protected string $tests_root;

	public function __construct() {
		parent::__construct();

		$this->tests_root = dirname( __DIR__ );
		$this->root       = dirname( $this->tests_root );
	}
}
