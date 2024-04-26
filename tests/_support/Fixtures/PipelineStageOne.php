<?php declare(strict_types=1);

namespace StellarWP\Pipeline\Tests\Fixtures;

use Closure;
use StellarWP\Pipeline\Contracts\Pipe;

// This class extends the PipeInterface.
final class PipelineStageOne implements Pipe {
	public function handle( $passable, Closure $next ): string {
		$passable = ucwords( $passable );

		return $next( $passable );
	}
}
