<?php

namespace EvilComp\FitnessOperator;

use EvilComp\Entities\Chromosome;
use EvilComp\Handlers\TaskListHandler;

/**
 * Class TaskSchedulingFitnessOperator
 */
class TaskSchedulingFitnessOperator extends FitnessOperatorAbstract
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

    protected function getTaskListDependency($taskId, TaskListHandler $taskListHandler, array $executedTasks)
    {
        $taskListDependency = [];

        foreach ($taskListHandler->getTaskLists() as $l) {
            $dependencies = $l->getDependencies($taskId);

            if (!$dependencies) {
                continue;
            }

            foreach ($dependencies as $dependency) {
                if (!isset($executedTasks[$dependency])) {
                    return false;
                }
            }

            $taskListDependency[] = $l;
        }

        return $taskListDependency;
    }


    public function calculate(
        Chromosome $chromosome
    ) {
        $executedTasks = [];
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

        $chromosome->dump();

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
                $gene, $this->getTaskListHandler(), $executedTasks
            );

            if ($taskListDependency === false) {
                $core = ($core + 1) % 2;
                continue;
            } else if (!$taskListDependency) {
                $task = $this->getTaskHandler()->at($gene);
                $taskExecTime = $task[1];

                $time = $pCoreTimers[$core] + $taskExecTime;
                $pCoreTimers[$core] = $time;
                $executedTasks[$gene] = [
                    'time' => $time,
                    'core' => $core,
                ];

                $procTime = $time;

                array_shift($pCores[$core]);

                continue;
            }

            $task = $this->getTaskHandler()->at($gene);
            $taskExecTime = $task[1];

            $dependencyTime = 0;
            $deliveryTime = 0;
            foreach ($taskListDependency as $l) {
                $node = $l->getLastNodeDependency($gene);

                if (!isset($executedTasks[$node->getTaskId()])) {
                    throw new Exception("Something bad happened!");
                } else if ($executedTasks[$node->getTaskId()]['time'] < $dependencyTime) {
                    continue;
                }

                $dependencyTime = $executedTasks[$node->getTaskId()]['time'];

                if ($executedTasks[$node->getTaskId()]['core'] != $core) {
                    $currentNode = $l->search($gene);
                    $deliveryTime = $currentNode->getDeliveryTime();
                }
            }

            $time = max($pCoreTimers[$core], $dependencyTime) + $taskExecTime + $deliveryTime;

            $pCoreTimers[$core] = $time;
            $executedTasks[$gene] = [
                'time' => $time,
                'core' => $core,
            ];

            $procTime = $time;

            array_shift($pCores[$core]);
        }

        if ($procTime <= 41) {
            var_dump($procTime);
            $chromosome->dump();
            exit();
        }

        return $procTime;
    }

    protected function dumpExecutedTasks(array $executedTasks)
    {
        $d = [];
        foreach ($executedTasks as $taskId => $data) {
            $d[] = "$taskId => {$data['time']}, {$data['core']}";
        }

        var_dump(implode("\n", $d));
    }

    protected function dumpCores(array $pCores)
    {
        foreach ($pCores as $core => $tasks) {
            var_dump("{$core}: " . implode(", ", $tasks));
        }
    }
}

