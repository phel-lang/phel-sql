<?php

declare(strict_types=1);

// Loads the compiled build (run `phel build` first) and checks one query.

use Phel\Lang\Keyword;
use Phel\Lang\TypeFactory;

$entry = dirname(__DIR__, 2) . '/out/index.php';
if (!is_file($entry)) {
    fwrite(STDERR, "Missing out/index.php. Run `vendor/bin/phel build` first.\n");
    exit(1);
}

require $entry;

$type = TypeFactory::getInstance();
$query = $type->persistentMapFromKVs(
    Keyword::create('select'),
    $type->persistentVectorFromArray([Keyword::create('id')]),
    Keyword::create('from'),
    $type->persistentVectorFromArray([Keyword::create('users')]),
    Keyword::create('where'),
    $type->persistentVectorFromArray([Keyword::create('='), Keyword::create('status'), 'active']),
);

$format = Phel::getDefinition('phel.sql', 'format');
$result = $format($query);

$actualSql = $result[0] ?? null;
$actualParams = isset($result[1]) ? iterator_to_array($result[1]) : null;

if ($actualSql !== 'SELECT id FROM users WHERE status = ?' || $actualParams !== ['active']) {
    fwrite(STDERR, 'Unexpected result: ' . json_encode([$actualSql, $actualParams]) . "\n");
    exit(1);
}

echo "OK: compiled format returned the expected tuple\n";
