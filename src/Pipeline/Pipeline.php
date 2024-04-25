<?php declare( strict_types = 1 );

namespace StellarWP\Pipeline;

use Closure;
use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;
use Throwable;

/**
 * Adapted from Laravel's Pipeline to use our container system.
 */
class Pipeline {
	/**
	 * The container implementation.
	 *
	 * @var ContainerInterface|null
	 */
	protected ?ContainerInterface $container;

	/**
	 * The object being passed through the pipeline.
	 *
	 * @var mixed
	 */
	protected mixed $passable;

	/**
	 * The array of class pipes.
	 *
	 * @var mixed[]
	 */
	protected array $pipes = [];

	/**
	 * The method to call on each pipe.
	 *
	 * @var string
	 */
	protected string $method = 'handle';

	/**
	 * Create a new class instance.
	 *
	 * @param ContainerInterface|null $container
	 */
	public function __construct( ContainerInterface $container = null ) {
		$this->container = $container;
	}

	/**
	 * Set the container instance.
	 *
	 * @param ContainerInterface $container The container instance.
	 *
	 * @return $this
	 */
	public function set_container( ContainerInterface $container ): self {
		$this->container = $container;

		return $this;
	}

	/**
	 * Set the object being sent through the pipeline.
	 *
	 * @param mixed $passable The object to pass through the pipeline.
	 *
	 * @return $this
	 */
	public function send( $passable ): self {
		$this->passable = $passable;

		return $this;
	}

	/**
	 * Set the array of pipes.
	 *
	 * @param array|mixed $pipes The pipes to set.
	 *
	 * @return $this
	 */
	public function through( $pipes ): self {
		$this->pipes = is_array( $pipes ) ? $pipes : func_get_args();

		return $this;
	}

	/**
	 * Push additional pipes onto the pipeline.
	 *
	 * @param array|mixed $pipes The pipes to push onto the pipeline.
	 *
	 * @return $this
	 */
	public function pipe( mixed $pipes ): self {
		array_push( $this->pipes, ...( is_array( $pipes ) ? $pipes : func_get_args() ) );

		return $this;
	}

	/**
	 * Set the method to call on the pipes.
	 *
	 * @param string $method The method to call on the pipes.
	 *
	 * @return $this
	 */
	public function via( string $method ): self {
		$this->method = $method;

		return $this;
	}

	/**
	 * Run the pipeline with a final destination callback.
	 *
	 * @param Closure $destination The destination callback.
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function then( Closure $destination ) {
		$pipeline = array_reduce(
			array_reverse( $this->pipes() ),
			$this->carry(),
			$this->prepare_destination( $destination )
		);

		return $pipeline( $this->passable );
	}

	/**
	 * Run the pipeline and return the result.
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function then_return() {
		return $this->then( fn( $passable ) => $passable );
	}

	/**
	 * Get the final piece of the Closure onion.
	 *
	 * @throws \RuntimeException
	 *
	 * @param Closure $destination The destination callback.
	 *
	 * @return Closure
	 */
	protected function prepare_destination( Closure $destination ): Closure {
		return function( $passable ) use ( $destination ) {
			try {
				return $destination( $passable );
			} catch ( Throwable $e ) {
				return $this->handle_exception( $passable, $e );
			}
		};
	}

	/**
	 * Get a Closure that represents a slice of the application onion.
	 *
	 * @throws \RuntimeException
	 *
	 * @return Closure
	 */
	protected function carry(): Closure {
		return function ( $stack, $pipe ) {
			return function ( $passable ) use ( $stack, $pipe ) {
				try {
					if ( is_callable( $pipe ) && is_string( $pipe ) ) {
						// If the pipe is a callable string (function), we will call dynamicall create a
						// closure and pass it to the pipe.
						$pipe = static function ( $passable, Closure $next ) use ( $pipe ) {
							$passable = $pipe( $passable );
							return $next( $passable );
						};

						$parameters = [ $passable, $stack ];
					} elseif ( is_callable( $pipe ) ) {
						// If the pipe is a callable, then we will call it directly, but otherwise we
						// will resolve the pipes out of the dependency container and call it with
						// the appropriate method and arguments, returning the results back out.
						return $pipe( $passable, $stack );
					} elseif ( ! is_object( $pipe ) ) {
						[ $name, $parameters ] = $this->parse_pipe_string( $pipe );

						// If the pipe is a string we will parse the string and resolve the class out
						// of the dependency injection container. We can then build a callable and
						// execute the pipe function giving in the parameters that are required.
						$pipe = $this->get_container()->get( $name );

						$parameters = array_merge( [ $passable, $stack ], $parameters );
					} else {
						// If the pipe is already an object we'll just make a callable and pass it to
						// the pipe as-is. There is no need to do any extra parsing and formatting
						// since the object we're given was already a fully instantiated object.
						$parameters = [ $passable, $stack ];
					}

					$carry = method_exists( $pipe, $this->method )
						? $pipe->{$this->method}( ...$parameters )
						: $pipe( ...$parameters );

					return $this->handle_carry( $carry );
				} catch ( Throwable $e ) {
					return $this->handle_exception( $passable, $e );
				}
			};
		};
	}

	/**
	 * Parse full pipe string to get name and parameters.
	 *
	 * @param string $pipe The pipe string to parse.
	 *
	 * @return array<int, mixed>
	 */
	protected function parse_pipe_string( string $pipe ): array {
		[ $name, $parameters ] = array_pad( explode( ':', $pipe, 2 ), 2, [] );

		if ( is_string( $parameters ) ) {
			$parameters = explode( ',', $parameters );
		}

		return [ $name, $parameters ];
	}

	/**
	 * Get the array of configured pipes.
	 *
	 * @return mixed[]
	 */
	protected function pipes(): array {
		return $this->pipes;
	}

	/**
	 * Get the container instance.
	 *
	 * @throws \RuntimeException
	 */
	protected function get_container(): ContainerInterface {
		if ( ! $this->container ) {
			throw new RuntimeException( 'A container instance has not been passed to the Pipeline.' );
		}

		return $this->container;
	}

	/**
	 * Handle the value returned from each pipe before passing it to the next.
	 */
	#[\ReturnTypeWillChange]
	protected function handle_carry( $carry ) {
		return $carry;
	}

	/**
	 * Handle the given exception.
	 *
	 * @param mixed     $passable The value to pass through the pipeline.
	 * @param Throwable $e        The exception to handle.
	 *
	 * @return mixed
	 *
	 * @throws Throwable The exception to throw.
	 */
	#[\ReturnTypeWillChange]
	protected function handle_exception( $passable, Throwable $e ) {
		throw $e;
	}

	/**
	 * Laravel-like method aliases.
	 */
	/**
	 * @see set_container()
	 *
	 * @return $this
	 */
	public function setContainer( ContainerInterface $container ): self { // phpcs:ignore
		return $this->set_container( $container );
	}

	/**
	 * Run the pipeline and return the result.
	 *
	 * @see then_return()
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function thenReturn() { // phpcs:ignore
		return $this->then_return();
	}
}
