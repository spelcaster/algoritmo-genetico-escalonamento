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

//$c = new Chromosome(
    //[9,17,3,0,10,13,12,6,7,2,15,8,11,14,16,1,5,4],
    //[1,0,0,0,1,0,0,0,1,1,0,1,0,1,1,0,1,1]
//);

//$c->dump();
//$c->correct($taskListHandler);
//$c->dump();

//$fitness = $fitnessOperator->calculate($c);
//var_dump($fitness);

//die();

$crossoverOperator = new Cycle2CrossoverOperator();

$selectionEngine = new TournamentSelection(true);

$mutationOperator = new SwapMutationOperator();

$ga = new GeneticAlgorithm(10);

$ga->setCrossoverOperator($crossoverOperator)
   ->setMutationOperator($mutationOperator)
    ->setSelectionEngine($selectionEngine)
    ->setFitnessOperator($fitnessOperator);

$ga->run($taskHandler, $taskListHandler);
