<?php

declare(strict_types=1);

$file = __DIR__ . '/../erp-omd/includes/services/class-reporting-service-v2.php';
$code = file_get_contents($file);
if ($code === false) {
    fwrite(STDERR, "Cannot read file: {$file}\n");
    exit(1);
}

$tokens = token_get_all($code);
$in_reporting_class = false;
$brace_depth = 0;
$method_names = [];
$duplicates = [];

for ($i = 0, $count = count($tokens); $i < $count; $i++) {
    $token = $tokens[$i];

    if (is_array($token) && $token[0] === T_CLASS) {
        $j = $i + 1;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING && $tokens[$j][1] === 'ERP_OMD_Reporting_Service') {
            $in_reporting_class = true;
        }
        continue;
    }

    if (! $in_reporting_class) {
        continue;
    }

    if ($token === '{') {
        $brace_depth++;
        continue;
    }
    if ($token === '}') {
        $brace_depth--;
        if ($brace_depth <= 0) {
            break;
        }
        continue;
    }

    if (! is_array($token) || $token[0] !== T_FUNCTION) {
        continue;
    }

    $j = $i + 1;
    while (
        $j < $count
        && (
            (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)
            || $tokens[$j] === '&'
        )
    ) {
        $j++;
    }

    if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
        $name = (string) $tokens[$j][1];
        if (isset($method_names[$name])) {
            $duplicates[] = $name;
        }
        $method_names[$name] = true;
    }
}

if ($duplicates !== []) {
    $duplicates = array_values(array_unique($duplicates));
    fwrite(STDERR, 'Duplicate method declarations detected: ' . implode(', ', $duplicates) . PHP_EOL);
    exit(1);
}

echo "No duplicate methods detected in ERP_OMD_Reporting_Service.\n";

