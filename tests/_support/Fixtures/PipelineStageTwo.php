<?php declare(strict_types=1);

namespace StellarWP\Pipeline\Tests\Fixtures;

use Closure;

// This class purposefully does not extend the PipeInterface.
final class PipelineStageTwo {
	public function handle( string $passable, Closure $next ): string {
		$passable = str_ireplace( 'All', 'All The', $passable );

		return $next( $passable );
	}
}
