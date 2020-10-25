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

$crossoverOperator = new Cycle2CrossoverOperator();

$selectionEngine = new TournamentSelection();

$mutationOperator = new SwapMutationOperator();

$ga = new GeneticAlgorithm(10);

$ga->setCrossoverOperator($crossoverOperator)
   ->setMutationOperator($mutationOperator)
    ->setSelectionEngine($selectionEngine)
    ->setFitnessOperator($fitnessOperator);

$ga->run($taskHandler, $taskListHandler);
