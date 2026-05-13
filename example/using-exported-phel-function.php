<?php declare(strict_types=1);

use Phel\Phel;
use PhelGenerated\CliSkeleton\Modules\AdderModule;

$projectRootDir = dirname(__DIR__);

require $projectRootDir . '/vendor/autoload.php';

Phel::run($projectRootDir, 'cli-skeleton.modules.adder-module');

###################################################
# Run the export command first to (re)generate the
# PHP wrappers from the exported phel functions:
#
#   $ composer export    # or: vendor/bin/phel export
#   $ php example/using-exported-phel-function.php
###################################################
$result = AdderModule::adder(1, 2, 3);

echo 'Result = ' . $result . PHP_EOL;
