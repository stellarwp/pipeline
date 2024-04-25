# StellarWP Pipeline

[![Tests](https://github.com/stellarwp/pipeline/workflows/Tests/badge.svg)](https://github.com/stellarwp/pipeline/actions?query=branch%3Amain) [![Static Analysis](https://github.com/stellarwp/pipeline/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/stellarwp/pipeline/actions/workflows/static-analysis.yml)

A Pipeline / Command Design Pattern implementation based on [Laravel's Pipeline implementation](https://github.com/illuminate/pipeline/blob/master/Pipeline.php).

## Table of contents

* [Installation](#installation)
* [Usage prerequisites](#usage-prerequisites)
* [Important note on examples](#important-note-on-examples)
* [Pipelines](#pipelines)
  * [Using functions](#using-functions)
  * [Using closures](#using-closures)
  * [Using classes with the `handle` method](#using-classes-with-the-handle-method)
  * [Using classes with a custom method](#using-classes-with-a-custom-method)
  * [Doing more than returning](#doing-more-than-returning)

## Installation

It's recommended that you install Pipeline as a project dependency via [Composer](https://getcomposer.org/):

```bash
composer require stellarwp/pipeline
```

> We _actually_ recommend that this library gets included in your project using [Strauss](https://github.com/BrianHenryIE/strauss).
>
> Luckily, adding Strauss to your `composer.json` is only slightly more complicated than adding a typical dependency, so checkout our [strauss docs](https://github.com/stellarwp/global-docs/blob/main/docs/strauss-setup.md).

## Usage prerequisites

To actually _use_ the Pipeline library, you must have a container that conforms to the `ContainerInterface` interface found in [stellarwp/container-contract](https://github.com/stellarwp/container-contract).

## Important note on examples

All examples within the documentation for this project will be assuming that you are using [Strauss](#strauss) to prefix the namespaces provided by this library.

The examples will be using:

* `Boom\Shakalaka\` as the namespace prefix, though will sometimes be referenced as `PREFIX\` for the purpose of brevity in the docs.
* `BOOM_SHAKALAKA_` as the constant prefix.

## Pipelines

Pipelines provide a way to send data through a series of "pipes" (functions, closures, and/or classes) to get a result at the end.
The pipes are executed in the order that they are added to the pipeline.

Below are a few examples of how to use the library with different types of pipes. You can mix and match to your liking!

### Using functions

You can create a pipeline by instantiating the `Pipeline` class and passing in your container instance. Then, you can
add your "pipes" to be executed against the input data, getting a result at the end.

#### Example pipeline

```php
use Boom\Shakalaka\StellarWP\Pipeline\Pipeline;
use Boom\Shakalaka\Container;

$container = new Container();
$pipeline  = new Pipeline( $container );

// The input is a string with no capitalization and a whole bunch of trailing whitespace.
$result = $pipeline->send( 'a sample string that is passed through to all pipes.         ' )
	->through(
		'trim',
		'ucwords'
	)->then_return();

// The output would be stored in $result and would be:
// A Sample String That Is Passed Through To All Pipes.
echo $result;
```

### Using closures

If you have a more complicated function that you wish to use as a pipe, you can pass in a callable instead of a string.
Your closure will need to accept two parameters, the first being the input data and the second being the next item in the pipeline.

#### Example pipeline

```php
use Boom\Shakalaka\StellarWP\Pipeline\Pipeline;
use Boom\Shakalaka\Container;

$container = new Container();
$pipeline  = new Pipeline( $container );

// The input is a string with no capitalization and a whole bunch of trailing whitespace.
$result = $pipeline->send( 'a sample string that is passed through to all pipes.         ' )
	->through(
		static function ( string $passable, Closure $next ) {
			$passable = str_ireplace( 'All', 'All The', $passable );

			return $next( $passable );
		},
		'ucwords'
	)->then_return();

// The output would be stored in $result and would be:
// A Sample String That Is Passed Through To All The Pipes.
echo $result;
```

### Using classes with the `handle` method

You can even create your own classes to use as pipes in the pipeline. For a class to be usable in the pipeline, it needs
a method that accepts two parameters, the first being the input data and the second being the next item in the pipeline.

By default, the Pipeline expects that the method is called `handle`. If you want to use that method name, you can
optionally implement the `StellarWP\Pipeline\Contracts\PipeInterface` interface to enforce that method convention.

#### Example classes

First class:
```php
use Boom\Shakalaka\StellarWP\Pipeline\Contracts\PipeInterface;

class SweetUppercasePipe implements PipeInterface {
	public function handle( $passable, Closure $next ) {
		$passable = ucwords( $passable );

		return $next( $passable );
	}
}
```

Second class:
```php
use Boom\Shakalaka\StellarWP\Pipeline\Contracts\PipeInterface;

class TrimTheStringPipe implements PipeInterface {
	public function handle( $passable, Closure $next ) {
		$passable = trime( $passable );

		return $next( $passable );
	}
}
```

#### Example pipeline

```php
use Boom\Shakalaka\StellarWP\Pipeline\Pipeline;
use Boom\Shakalaka\Container;

$container = new Container();
$pipeline  = new Pipeline( $container );

// The input is a string with no capitalization and a whole bunch of trailing whitespace.
$result = $pipeline->send( 'a sample string that is passed through to all pipes.         ' )
	->through(
		SweetUppercasePipe::class,
		TrimTheStringPipe::class
	)->then_return();

// The output would be stored in $result and would be:
// A Sample String That Is Passed Through To All Pipes.
echo $result;
```

### Using classes with a custom method

If you want to use classes but want to use a different method than the expected default (`handle`), you can declare
the alternate method name using the `via()` method.

#### Example classes

First class:
```php
class DifferentSweetUppercasePipe {
	public function run( $passable, Closure $next ) {
		$passable = ucwords( $passable );

		return $next( $passable );
	}
}
```

Second class:
```php
class DifferentTrimTheStringPipe {
	public function run( $passable, Closure $next ) {
		$passable = trime( $passable );

		return $next( $passable );
	}
}
```

#### Example pipeline

```php
use Boom\Shakalaka\StellarWP\Pipeline\Pipeline;
use Boom\Shakalaka\Container;

$container = new Container();
$pipeline  = new Pipeline( $container );

// Set the method to use to run the pipes as "run".
// The input is a string with no capitalization and a whole bunch of trailing whitespace.
$result = $pipeline->via( 'run' )
	->send( 'a sample string that is passed through to all pipes.         ' )
	->through(
		DifferentSweetUppercasePipe::class,
		DifferentTrimTheStringPipe::class
	)->then_return();

// The output would be stored in $result and would be:
// A Sample String That Is Passed Through To All Pipes.
echo $result;
```

### Doing more than returning

Sometimes you may want to do something more than just return the result when the pipeline completes. You can do that by
using the `then()` method instead of `then_return()`.

#### Example pipeline

```php
use Boom\Shakalaka\StellarWP\Pipeline\Pipeline;
use Boom\Shakalaka\Container;

$container = new Container();
$pipeline  = new Pipeline( $container );

// The input is a string with no capitalization and a whole bunch of trailing whitespace.
$result = $pipeline->send( 'a sample string that is passed through to all pipes.         ' )
	->through(
		'trim',
		'ucwords'
	)->then( static function ( $passable ) {
		return str_ireplace( 'A Sample', 'A Nice Long', $passable );
	} );

// The output would be stored in $result and would be:
// A Nice Long String That Is Passed Through To All Pipes.
echo $result;
```
