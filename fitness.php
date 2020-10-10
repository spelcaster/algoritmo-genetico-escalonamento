#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

// tasks
$tasks = [];

$fp = fopen('tasks', 'r');

if (!$fp) {
    exit(1);
}

while (!feof($fp)) {
    $line = fgets($fp);

    if (!$line) {
        continue;
    }

    $data = explode(',', $line);

    $data = array_map('trim', $data);

    $tasks[] = $data;
}

fclose($fp);

// paths
$paths = [];

$fp = fopen('paths', 'r');

if (!$fp) {
    exit(1);
}

while (!feof($fp)) {
    $line = fgets($fp);

    if (!$line) {
        continue;
    }

    $data = explode(',', $line);

    $data = array_map('trim', $data);

    $paths[] = $data;
}

fclose($fp);

$chromosome = [
    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17
];

$p1 = array_fill(0, 17, 0);

$p2 = [
    0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0
];

function fitness($tasks, $paths, $chromosome, $proc) {
    var_dump($tasks, $paths, $chromosome, $proc);
}

fitness($tasks, $paths, $chromosome, $p1);

