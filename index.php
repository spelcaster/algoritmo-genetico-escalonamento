#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use EvilComp\Entities\TaskNode;
use EvilComp\Entities\TaskList;

use EvilComp\CrossoverOperator\Cycle2CrossoverOperator;
use EvilComp\MutationOperator\SwapMutationOperator;

function dumpc($c) {
    var_dump(implode(' ', $c['tasks']));
    var_dump(implode(' ', $c['processors']));
}

function dump_endpoints($linkedLists)
{
    $endpoints = [1, 7, 12, 16, 17];

    foreach ($endpoints as $e) {
        var_dump($e);

        foreach ($linkedLists as $l) {
            $l->dump();
            var_dump(implode(" ", $l->getDependencies($e)));
        }
    }
}

function generateLinkedList(TaskList $list, TaskNode $lastNode, $i, array $tasks, array $paths, &$linkedLists)
{
    $currentTaskId = $lastNode->getTaskId();
    $tmpLinkedList = clone $list;

    // $i > que o total de paths possiveis
    if ($i > count($paths)) {
        $linkedLists[] = $tmpLinkedList;
        return;
    }

    $linkedListsSize = count($linkedLists);
    for ($i; $i < count($paths); $i++) {
        $path = $paths[$i];
        $lastTaskId = $path[0];

        // id da task atual nao pode ser maior que o id da ultima task na lista
        if ($lastTaskId != $currentTaskId) {
            continue;
        }

        $to = $path[1];

        $nextTask = $tasks[$to];
        $deliveryTime = $path[2];

        $newTaskList = clone $tmpLinkedList;

        $currentTaskNode = new TaskNode($to, $nextTask[1], $deliveryTime);
        $newTaskList->addNode($currentTaskNode);

        $j = $i + 1;

        generateLinkedList($newTaskList, $currentTaskNode, $j, $tasks, $paths, $linkedLists);
    }

    if (count($linkedLists) == $linkedListsSize) {
        $linkedLists[] = $tmpLinkedList;
    }
}

function generateChromosome(array $tasks)
{
    $i = 0;

    $chromosome = [
        'tasks' => [],
        'processors' => [],
    ];

    $size = count($tasks) - 1;

    while ($i < $size) {
        $gene = mt_rand(0, $size);

        if (in_array($gene, $chromosome['tasks'])) {
            continue;
        }

        $chromosome['tasks'][$i] = $gene;
        $chromosome['processors'][$i] = mt_rand(0, 1);

        ++$i;
    }

    return $chromosome;
}

function swap(array &$arr, $posA, $posB) {
    $tmp = $arr[$posB];
    $arr[$posB] = $arr[$posA];
    $arr[$posA] = $tmp;
}

function manipulateChromosome(array $chromosome, array $linkedLists) {
    $tasks = $chromosome['tasks'];
    $processors = $chromosome['processors'];

    $i = 0;
    while ($i < count($tasks)) {
        $gene = $tasks[$i];

        $taskDependency = [];
        foreach ($linkedLists as $l) {
            $dependencies = $l->getDependencies($gene);

            if (!$dependencies) {
                continue;
            }

            foreach ($dependencies as $dependency) {
                $taskPos = array_search($dependency, $tasks);

                if ($taskPos < $i) {
                    continue;
                }

                $taskDependency[$taskPos] = $dependency;
            }
        }

        if (!$taskDependency) {
            ++$i;
            continue;
        }

        asort($taskDependency);

        $taskPos = array_search(array_shift($taskDependency), $tasks);
        swap($tasks, $i, $taskPos);
        swap($processors, $i, $taskPos);

        $i = min($i, $taskPos);
    }

    $chromosome['tasks'] = $tasks;
    $chromosome['processors'] = $processors;

    return $chromosome;
}

function hasPendingTasks ($pCores) {
    foreach ($pCores as $core) {
        if (!$core) {
            continue;
        }

        return true;
    }

    return false;
}

function fitness ($chromosome, $taskExecList, $taskLists) {
    $tasks = $chromosome['tasks'];
    $processors = $chromosome['processors'];

    $size = count($tasks);
    $taskTimers = [];

    $pCores = [];
    for ($i = 0; $i < count($processors); $i++) {
        $pCores[$processors[$i]][] = $tasks[$i];
    }

    $pCoreTimers = [];
    for ($i = 0; $i < count($pCores); $i++) {
        $pCoreTimers[$i] = 0;
    }

    $cores = count($pCores);
    $core = 0;

    $procTime = 0;
    while (hasPendingTasks($pCores)) {
        if (!$pCores[$core]) {
            $core = ($core + 1) % 2;
            continue;
        }

        $time = 0;
        $taskExecTime = 0;
        $gene = $pCores[$core][0];
        $swapCore = false;

        $taskListDependency = [];
        foreach ($taskLists as $l) {
            $dependencies = $l->getDependencies($gene);

            if (!$dependencies) {
                continue;
            }

            foreach ($dependencies as $dependency) {
                if (!isset($taskTimers[$dependency])) {
                    $swapCore = true;

                    break;
                }
            }

            if ($swapCore) {
                $core = ($core + 1) % 2;
                continue 2;
            }

            $taskListDependency[] = $l;
        }

        if (!$taskListDependency) {
            $taskExecTime = $taskExecList[$gene][1];
            $time = $pCoreTimers[$core] + $taskExecTime;
            $pCoreTimers[$core] = $time;
            $taskTimers[$gene] = $time;
            $procTime = $time;
            array_shift($pCores[$core]);

            continue;
        }

        $taskExecTime = $taskExecList[$gene][1];

        $dependencyTime = 0;
        $deliveryTime = 0;
        foreach ($taskListDependency as $l) {
            $node = $l->getLastNodeDependency($gene);

            if (!isset($taskTimers[$node->getTaskId()])) {
                throw new Exception("Something bad happened!");
            }

            if ($taskTimers[$node->getTaskId()] < $dependencyTime) {
                continue;
            }

            $dependencyTime = $taskTimers[$node->getTaskId()];
            $deliveryTime = $node->getDeliveryTime();
        }

        $time = max($pCoreTimers[$core], $dependencyTime) + $taskExecTime + $deliveryTime;
        $pCoreTimers[$core] = $time;
        $taskTimers[$gene] = $time;
        $procTime = $time;
        array_shift($pCores[$core]);
    }

    return $procTime;
}

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

$linkedLists = [];

$startingTaskId = 0;
for ($i = 0; $i < count($paths); $i++) {
    $path = $paths[$i];

    $from = $path[0];
    $to = $path[1];
    $deliveryTime = $path[2];

    if (!isset($tasks[$from]) || !isset($tasks[$to])) {
        throw new \Exception("Invalid Task ID");
    }

    $currentTask = $tasks[$from];
    $nextTask = $tasks[$to];

    if ($from != $startingTaskId) {
        break;
    }

    $linkedList = new TaskList($currentTask[0], $currentTask[1]);

    $nextTaskNode = new TaskNode($to, $nextTask[1], $deliveryTime);
    $linkedList->addNode($nextTaskNode);

    $j = $i + 1;

    generateLinkedList($linkedList, $nextTaskNode, $j, $tasks, $paths, $linkedLists);
}

$c = generateChromosome($tasks);

$c = [
  'tasks' =>[
        0 => 3,
        1 => 7,
        2 => 8,
        3 => 9,
        4 => 14,
        5 => 0,
        6 => 12,
        7 => 11,
        8 => 1,
        9 => 10,
        10 => 13,
        11 => 5,
        12 => 6,
        13 => 4,
        14 => 16,
        15 => 15,
        16 => 2,
        17 => 17,
    ],
    'processors' => [
        0 => 0,
        1 => 1,
        2 => 1,
        3 => 0,
        4 => 1,
        5 => 1,
        6 => 1,
        7 => 0,
        8 => 0,
        9 => 1,
        10 => 0,
        11 => 0,
        12 => 0,
        13 => 0,
        14 => 1,
        15 => 0,
        16 => 0,
        17 => 1,
    ]
];

$c2 = manipulateChromosome($c, $linkedLists);

$f = fitness($c2, $tasks, $linkedLists);

var_dump($f);

