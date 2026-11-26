<?php

declare(strict_types=1);

// PHPUnit has no coverage threshold option, so CI fails here below 100% of statements and
// methods (handbook: 100% coverage on library code).

$file = $argv[1] ?? 'build/clover.xml';
$xml = simplexml_load_file($file);
if ($xml === false) {
    fwrite(STDERR, "Cannot read {$file}\n");
    exit(1);
}
$metrics = $xml->project->metrics;
$failures = [];
foreach (['statements', 'methods'] as $kind) {
    $total = (int) $metrics["{$kind}"];
    $covered = (int) $metrics["covered{$kind}"];
    if ($covered < $total) {
        $failures[] = sprintf('%s: %d of %d covered', $kind, $covered, $total);
    }
}
if ($failures !== []) {
    fwrite(STDERR, "Coverage below 100%:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "Coverage: 100% of statements and methods.\n";
