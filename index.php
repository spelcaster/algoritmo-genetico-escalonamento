#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use EvilComp\Entities\TaskNode;
use EvilComp\Entities\TaskList;

use EvilComp\CrossoverOperator\Cycle2CrossoverOperator;
use EvilComp\MutationOperator\SwapMutationOperator;

use EvilComp\Handlers\TaskHandler;
use EvilComp\Handlers\PathHandler;
use EvilComp\Handlers\TaskListHandler;
use EvilComp\Entities\Chromosome;
use EvilComp\FitnessOperator\TaskSchedulingFitnessOperator;


$pathHandler = new PathHandler('resources/paths.csv');

$taskHandler = new TaskHandler('resources/tasks.csv');

$taskListHandler = new TaskListHandler($taskHandler, $pathHandler);
$taskListHandler->generate([0]);

$chromosome = Chromosome::factory($taskHandler);
$chromosome->correct($taskListHandler);

$fitnessOperator = new TaskSchedulingFitnessOperator();
$fitnessOperator->calculate($chromosome, $taskHandler, $taskListHandler);
