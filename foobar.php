<?php

$output = [];

foreach (range(1, 100) as $number) {
    if ($number % 15 === 0) {
        $output[] = 'foobar';
        continue;
    }
    if ($number % 5 === 0) {
        $output[] = 'bar';
        continue;
    }

    if ($number % 3 === 0) {
        $output[] = 'foo';
        continue;
    }
    $output[] = $number;
}

fwrite(STDOUT, implode(", ", $output));