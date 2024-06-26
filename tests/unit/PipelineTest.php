<?php

namespace StellarWP\Pipeline\Tests\Unit;

use Closure;
use StellarWP\Pipeline\Pipeline;
use StellarWP\Pipeline\Tests\Fixtures\PipelineStageOne;
use StellarWP\Pipeline\Tests\Fixtures\PipelineStageTwo;
use StellarWP\Pipeline\Tests\Container;
use StellarWP\ContainerContract\ContainerInterface;

class PipelineTest extends AbstractBase {

	protected function setUp(): void {
		parent::setUp();

		$this->container = new Container();
		$this->container->bind( Pipeline::class, Pipeline::class );
		$this->container
			->when( Pipeline::class )
			->needs( ContainerInterface::class )
			->give( $this->container );
	}

	public function test_it_runs_a_pipeline_with_callables(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )
			->through(
				'ucwords',
				'trim',
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_with_callables_and_executes_the_destination(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )
			->through(
				'ucwords',
				'trim',
			)->then( static function ( $passable ) {
				return str_ireplace( 'A Sample', 'A Nice Long', $passable );
			} );

		$this->assertSame( 'A Nice Long String That Is Passed Through To All Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_with_callables_and_closures(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )
			->through(
				static function ( string $passable, Closure $next ) {
					$passable = str_ireplace( 'All', 'All The', $passable );

					return $next( $passable );
				},
				'ucwords',
				'trim'
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All The Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_with_closures(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.' )
			->through(
				static function ( string $passable, Closure $next ) {
					$passable = ucwords( $passable );

					return $next( $passable );
				},
				static function ( string $passable, Closure $next ) {
					$passable = str_ireplace( 'All', 'All The', $passable );

					return $next( $passable );
				}
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All The Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_with_class_strings_where_the_container_makes_the_instances(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.' )
			->through(
				PipelineStageOne::class,
				PipelineStageTwo::class,
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All The Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_using_object_handlers(): void {
		$stage1 = new class() {
			public function handle( string $passable, Closure $next ) {
				$passable = ucwords( $passable );

				return $next( $passable );
			}
		};

		$stage2 = new class() {
			public function handle( string $passable, Closure $next ) {
				$passable = str_ireplace( 'All', 'All The', $passable );

				return $next( $passable );
			}
		};

		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.' )
			->through(
				$stage1,
				$stage2
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All The Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_using_custom_object_handlers(): void {
		$stage1 = new class() {
			public function run( string $passable, Closure $next ) {
				$passable = ucwords( $passable );

				return $next( $passable );
			}
		};

		$stage2 = new class() {
			public function run( string $passable, Closure $next ) {
				$passable = str_ireplace( 'All', 'All The', $passable );

				return $next( $passable );
			}
		};

		$pipeline = $this->container->get( Pipeline::class );
		// Tell the pipeline to use the "run" method instead of the default "handle" on all stages.
		$result = $pipeline->via( 'run' )
			->send( 'a sample string that is passed through to all pipes.' )
			->through(
				$stage1,
				$stage2
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All The Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_by_sending_late(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$pipeline->through(
			'ucwords',
			'trim'
		);

		$result = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_setup_via_pipe(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$pipeline->pipe(
			'ucwords',
			'trim'
		);

		$result = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );
	}

	public function test_it_runs_a_function_pipeline_without_a_container(): void {
		$pipeline = new Pipeline();
		$pipeline->pipe(
			'ucwords',
			'trim'
		);

		$result = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );
	}

	public function test_it_runs_a_closure_pipeline_without_a_container(): void {
		$pipeline = new Pipeline();
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.' )
			->through(
				static function ( string $passable, Closure $next ) {
					$passable = ucwords( $passable );

					return $next( $passable );
				},
				static function ( string $passable, Closure $next ) {
					$passable = str_ireplace( 'All', 'All The', $passable );

					return $next( $passable );
				}
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All The Pipes.', $result );
	}

	public function test_it_runs_a_class_pipeline_without_a_container(): void {
		$pipeline = new Pipeline();
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.' )
			->through(
				new PipelineStageOne(),
				new PipelineStageTwo()
			)->thenReturn();

		$this->assertSame( 'A Sample String That Is Passed Through To All The Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_setup_with_aliases(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$pipeline->pipes(
			'ucwords',
			'trim'
		);

		$result = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )->run_and_return();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );

		$pipeline = $this->container->get( Pipeline::class );
		$pipeline->add_pipe( 'ucwords' );
		$pipeline->add_pipe( 'trim' );

		$result = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )->run();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );

		$pipeline = $this->container->get( Pipeline::class );
		$pipeline->pipe(
			'ucwords',
			'trim',
		);

		$result = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )->run();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );
	}

	public function test_it_runs_a_pipeline_with_callables_with_an_empty_then(): void {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'a sample string that is passed through to all pipes.       ' )
			->through(
				'ucwords',
				'trim',
			)->then();

		$this->assertSame( 'A Sample String That Is Passed Through To All Pipes.', $result );
	}

	public function test_it_bails_early() {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'bork' )
			->through(
				static function ( string $passable, Closure $next ) {
					return false;
				},
				'trim',
			)->then();

		$this->assertFalse( $result );
	}

	public function test_it_bails_in_the_middle() {
		$pipeline = $this->container->get( Pipeline::class );
		$result   = $pipeline->send( 'bork        ' )
			->through(
				'trim',
				static function ( string $passable, Closure $next ) {
					return $passable;
				},
				'ucwords',
			)->then();

		$this->assertSame( 'bork', $result );
	}
}
