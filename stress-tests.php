#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Stress tests runner
 * Usage: php stress-tests.php
 */

$cliOptions = getopt('n:c:r:h', [
    'name:',
    'config:',
    'repeat:',
    'help',
]);

$options = [
    'collectionName' => $cliOptions['name']   ?? $cliOptions['n'] ?? null,
    'configFile'     => $cliOptions['config'] ?? $cliOptions['c'] ?? null,
    'repeatNo'       => $cliOptions['repeat'] ?? $cliOptions['r'] ?? 1e3,
    'help'           => $cliOptions['help']   ?? $cliOptions['h'] ?? null,
];

// Show help
if ($options['help'] !== null) {
    echo <<<HTML

Run stress tests on current cockpit installation

Usage:
  php {$argv[0]} --name articles --config tests/config.php

Options:
  -n --name    Collection name
  -c --conifg  Path to config file. When not defined using default one.
  -r --repeat  Repeat number
  -c --help    Show help

HTML;
    exit(0);
}


// Read collection name
if ($options['collectionName']) {
    sprintf('Info: Using collection `%s`', $options['collectionName']) . PHP_EOL;
} else {
    echo 'Info: Using all available collections' . PHP_EOL;
}


// Get path to config from command line
if (!$options['configFile']) {
    echo 'Info: Using default config' . PHP_EOL;
} else {
    echo sprintf('Info: Using custom config `%s`', $options['configFile']) . PHP_EOL;
    // Validate that path exists
    $configPath = __DIR__ . '/' . $options['configFile'];

    if (!is_file($configPath)) {
        exit(sprintf('Error: Config does not exist at path `%s`', $configPath) . PHP_EOL);
    }

    define('COCKPIT_CONFIG_PATH', $configPath);
}

// Initialize cockpit
$cockpitBootstrap = __DIR__ . '/../../bootstrap.php';

if (!file_exists($cockpitBootstrap)) {
    exit('Error: Cockpit not installed in parent directory' . PHP_EOL);
}

require $cockpitBootstrap;


// Start timer
$perf = [
    'startAt' => microtime(true),
    'endAt' => null,
];


// Resolve collection names
$collectionNames = $options['collectionName']
    ? [$collectionName]
    : array_column($cockpit->module('collections')->collections(), 'name');

// Run tasks
foreach ($collectionNames as $collectionName) {
    if (!$cockpit->module('collections')->exists($collectionName)) {
        echo sprintf('Warning: Collection `%s` does not exist', $collectionName) . PHP_EOL;
        continue;
    }

    // Common properties are: _id|_created|_modified|_by|_mby|_o
    for ($i = 0; $i < $options['repeatNo']; $i++) {
        // Sort descending by created at
        $cockpit->module('collections')->find($collectionName, [
            ['sort' => ['_created' => -1]],
        ]);

        // Filter ids >= 0
        $cockpit->module('collections')->find($collectionName, [
            'filter' => ['_id' => [
                '$gte' => 0
            ]]
        ]);
    }
}

$perf['endAt'] = microtime(true);

// Summary
echo PHP_EOL . vsprintf('Tasks took in %.6fs (%d×)', [
    $perf['endAt'] - $perf['startAt'],
    $options['repeatNo'],
]) . PHP_EOL;

exit(0);
