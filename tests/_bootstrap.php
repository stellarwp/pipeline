<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

Codeception\Util\Autoload::addNamespace( 'StellarWP\Pipeline\Tests\Helper', __DIR__ . '/_support/Helper' );
Codeception\Util\Autoload::addNamespace( 'StellarWP\Pipeline\Tests', __DIR__ . '/_support' );
