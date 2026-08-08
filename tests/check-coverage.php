<?php

declare(strict_types=1);

$report = $argv[1] ?? 'build/coverage.xml';
$minimum = (float) ($argv[2] ?? 100);

if (! is_file($report)) {
    fwrite(STDERR, "Coverage report not found: {$report}\n");
    exit(1);
}

$xml = simplexml_load_file($report);

if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Coverage report is invalid: {$report}\n");
    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percentage = $statements === 0 ? 100.0 : ($covered / $statements) * 100;

printf(
    "Statement coverage: %d/%d (%.2f%%); required: %.2f%%\n",
    $covered,
    $statements,
    $percentage,
    $minimum,
);

if ($percentage + PHP_FLOAT_EPSILON < $minimum) {
    exit(1);
}
