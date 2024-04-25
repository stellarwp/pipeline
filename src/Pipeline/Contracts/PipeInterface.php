<?php declare( strict_types = 1 );

namespace StellarWP\Pipeline\Contracts;

use Closure;

/**
 * Interface PipeInterface
 *
 * @package StellarWP\Pipeline\Contracts
 */
interface PipeInterface {
	/**
	 * Handle the given value.
	 *
	 * @param mixed   $passable The value to handle.
	 * @param Closure $next     The next pipe in the pipeline.
	 *
	 * @return mixed
	 */
	public function handle( $passable, Closure $next );
}
