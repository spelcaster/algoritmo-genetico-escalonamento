#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use EvilComp\CrossoverOperator\Cycle2CrossoverOperator;
use EvilComp\Entities\Chromosome;
use EvilComp\FitnessOperator\TaskSchedulingFitnessOperator;
use EvilComp\Handlers\PathHandler;
use EvilComp\Handlers\TaskHandler;
use EvilComp\Handlers\TaskListHandler;
use EvilComp\MutationOperator\SwapMutationOperator;
use EvilComp\SelectionEngine\TournamentSelection;
use EvilComp\GeneticAlgorithm;

$pathHandler = new PathHandler('resources/paths.csv');

$taskHandler = new TaskHandler('resources/tasks.csv');

$taskListHandler = new TaskListHandler($taskHandler, $pathHandler);
$taskListHandler->generate([0]);

$fitnessOperator = new TaskSchedulingFitnessOperator();

$fitnessOperator->setTaskHandler($taskHandler)
    ->setTaskListHandler($taskListHandler);

$c = new Chromosome(
    [11, 0, 3, 17, 6, 2, 10, 4, 8, 12, 9, 5, 1, 13, 15, 16, 14, 7],
    [1, 0, 1, 1, 1, 0, 1, 0, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1]
);

$c->dump();
$c->correct($taskListHandler);
$c->dump();
die();
$fitness = $fitnessOperator->calculate($c);
var_dump($fitness);

die();

$crossoverOperator = new Cycle2CrossoverOperator();

$selectionEngine = new TournamentSelection();

$mutationOperator = new SwapMutationOperator();

//$ga = new GeneticAlgorithm(10);

//$ga->setCrossoverOperator($crossoverOperator)
   //->setMutationOperator($mutationOperator)
    //->setSelectionEngine($selectionEngine)
    //->setFitnessOperator($fitnessOperator);

//$ga->run($taskHandler, $taskListHandler);
