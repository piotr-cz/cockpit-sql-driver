<?php
/**
 * https://phpunit.readthedocs.io/en/8.3/configuration.html#appendixes-configuration-phpunit-bootstrap
 *
 * Cockpit doesn't use composer to autoload it's own librararies.
 * There is no problem when using addon/ module from inside Cockpit, however when running unit tests
 * PHPUnit neends to be able to somehow autoload classes which are used in tests
 *
 * Unfortunatelly composer autoload is requred before this bootstrap file so when classes are not defined
 * in composer.json, PHPUnit will break with message like
 * `PHP Fatal error:  Uncaught Error: Class 'MongoHybrid\Client' not found` in /lib/MongoHybridClientWrapper.php
 *
 * Issue doesn't occurs when using PHPUnit installed globally
 */

// Require module autoload
if (!class_exists('MongoHybridClientWrapper', false)) {
    require_once __DIR__ . '/../autoload.php';
}
