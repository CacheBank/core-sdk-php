<?php

/*
 * Autoload dos testes. Com `composer install` no próprio SDK, usa vendor/autoload.php.
 * Sem isso, aceita um autoload externo que já tenha Guzzle e PHPUnit (ex.: o de um projeto que usa o SDK):
 *   CACHEDIGITAL_TEST_AUTOLOAD=/caminho/vendor/autoload.php vendor/bin/phpunit
 */
$local = __DIR__ . '/../vendor/autoload.php';
$external = getenv('CACHEDIGITAL_TEST_AUTOLOAD') ?: null;

if (file_exists($local)) {
    require $local;
} elseif ($external && file_exists($external)) {
    require $external;
    spl_autoload_register(function (string $class) {
        $prefix = 'CacheDigital\\Core\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }, true, true);
} else {
    fwrite(STDERR, "Rode `composer install` no SDK antes dos testes.\n");
    exit(1);
}
