<?php

namespace EvilComp\FitnessOperator;

use EvilComp\Entities\Chromosome;
use EvilComp\Handlers\TaskHandler;
use EvilComp\Handlers\TaskListHandler;

/**
 * Class TaskSchedulingFitnessOperator
 */
class TaskSchedulingFitnessOperator
{
    public function hasPendingTasks($cores)
    {
        foreach ($cores as $core) {
            if (!$core) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function getTaskListDependency($taskId, TaskListHandler $taskListHandler, array $taskTimers)
    {
        $taskListDependency = [];

        foreach ($taskListHandler->getTaskLists() as $l) {
            $dependencies = $l->getDependencies($taskId);

            if (!$dependencies) {
                continue;
            }

            foreach ($dependencies as $dependency) {
                if (!isset($taskTimers[$dependency])) {
                    return false;
                }
            }

            $taskListDependency[] = $l;
        }

        return $taskListDependency;
    }


    public function calculate(
        Chromosome $chromosome,
        TaskHandler $taskHandler,
        TaskListHandler $taskListHandler
    ) {
        $taskTimers = [];
        $processors = $chromosome->getProcessors();
        $tasks = $chromosome->getTasks();

        $pCores = [];
        for ($i = 0; $i < $chromosome->getSize(); $i++) {
            $pCores[$processors[$i]][] = $tasks[$i];
        }

        $pCoreTimers = [];
        for ($i = 0; $i < count($pCores); $i++) {
            $pCoreTimers[$i] = 0;
        }

        $cores = count($pCores);
        $core = 0;

        $procTime = 0;
        while ($this->hasPendingTasks($pCores)) {
            $time = 0;
            $taskExecTime = 0;

            if (!$pCores[$core]) {
                $core = ($core + 1) % 2;
                continue;
            }

            $gene = $pCores[$core][0];

            $taskListDependency = $this->getTaskListDependency(
                $gene, $taskListHandler, $taskTimers
            );

            if ($taskListDependency === false) {
                $core = ($core + 1) % 2;
                continue;
            } else if (!$taskListDependency) {
                $task = $taskHandler->at($gene);
                $taskExecTime = $task[1];

                $time = $pCoreTimers[$core] + $taskExecTime;
                $pCoreTimers[$core] = $time;
                $taskTimers[$gene] = $time;
                $procTime = $time;

                array_shift($pCores[$core]);

                continue;
            }

            $task = $taskHandler->at($gene);
            $taskExecTime = $task[1];

            $dependencyTime = 0;
            $deliveryTime = 0;
            foreach ($taskListDependency as $l) {
                $node = $l->getLastNodeDependency($gene);

                if (!isset($taskTimers[$node->getTaskId()])) {
                    throw new Exception("Something bad happened!");
                } else if ($taskTimers[$node->getTaskId()] < $dependencyTime) {
                    continue;
                }

                $dependencyTime = $taskTimers[$node->getTaskId()];

                $currentNode = $l->search($gene);
                $deliveryTime = $currentNode->getDeliveryTime();
            }

            $time = max($pCoreTimers[$core], $dependencyTime) + $taskExecTime + $deliveryTime;

            $pCoreTimers[$core] = $time;
            $taskTimers[$gene] = $time;
            $procTime = $time;

            array_shift($pCores[$core]);
        }

        return $procTime;
    }

}

