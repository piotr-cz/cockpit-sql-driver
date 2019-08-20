<?php
/**
 * Autoload classes when not using composers' generated autoload
 * Similar to following in composer.json
 *
 * ```json
 * "autoload": {
 *     "psr-4": {
 *         "": "lib/"
 *     }
 * }
 * ```
 */
spl_autoload_register(function (string $fqcn): void {
    $class_path = sprintf('%s/lib/%s.php', __DIR__, str_replace('\\', '/', $fqcn));

    if (is_file($class_path)) {
        include_once $class_path;
    }
});
