<?php

namespace EvilComp\Entities;

use EvilComp\Handlers\TaskListHandler;
use EvilComp\Handlers\TaskHandler;

/**
 * Class Chromosome
 * @author yourname
 */
class Chromosome
{
    protected $tasks;
    protected $processors;
    protected $size;

    public function __construct(array $tasks = [], array $processors = [])
    {
        $this->size = count($tasks);
        $this->tasks = $tasks;
        $this->processors = $processors;
    }

    public function dump() {
        var_dump(
            implode(' ', $this->tasks),
            implode(' ', $this->processors)
        );
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    public function hasTask($taskId)
    {
        return in_array($taskId, $this->tasks);
    }

    public function getProcessors()
    {
        return $this->processors;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getProcessorsTasks()
    {
        $pCores = [];

        for ($i = 0; $i < $this->getSize(); $i++) {
            $pCores[$this->processors[$i]][] = $this->tasks[$i];
        }

        return $pCores;
    }

    public function addTask($index, $taskId, $coreId)
    {
        if (isset($this->tasks[$index])) {
            $this->tasks[$index] = $taskId;
            $this->processors[$index] = $coreId;
            return $this;
        }

        $this->tasks[$index] = $taskId;
        $this->processors[$index] = $coreId;
        ++$this->size;
        return $this;
    }

    public function correct(TaskListHandler $taskListHandler)
    {
        $executedTasks = [];

        $pCores = $this->getProcessorsTasks();
        $blockedCores = $this->unblockProcessor($pCores, []);

        $core = 0;
        while ($this->hasPendingTasks($pCores)) {
            if (!$pCores[$core]) {
                $core = ($core + 1) % 2;
                continue;
            }

            $taskId = $pCores[$core][0];

            if ($this->hasDeadlock($blockedCores)) {
                break;
            }

            foreach ($taskListHandler->getTaskLists() as $taskList) {
                $dependencies = $taskList->getDependencies($taskId);

                if (!$dependencies) {
                    continue;
                }

                foreach ($dependencies as $dependency) {
                    if (isset($executedTasks[$dependency])) {
                        continue;
                    } else if ($blockedCores[$core] === false) {
                        $blockedCores[$core] = $dependency;
                        break;
                    }

                    $blockedTask = $blockedCores[$core];

                    if ($dependency < $blockedTask) {
                        $blockedCores[$core] = $dependency;
                    }

                    break;
                }
            }

            if ($blockedCores[$core] !== false) {
                $core = ($core + 1) % 2;
                continue;
            }

            $executedTasks[$taskId] = true;
            array_shift($pCores[$core]);

            // must be done after shift
            $blockedCores = $this->unblockProcessor($pCores, $blockedCores);
            continue;
        }

        if ($this->hasDeadlock($blockedCores)) {
            rsort($blockedCores);

            $blockedTaskPos = null;
            foreach ($blockedCores as $taskId) {
                if ($core === false) {
                    continue;
                } else if (is_null($blockedTaskPos)) {
                    $blockedTaskPos = array_search($taskId, $this->tasks);
                    continue;
                }

                $pos = array_search($taskId, $this->tasks);

                if ($pos < $blockedTaskPos) {
                    $blockedTaskPos = $pos;
                }
            }

            $lowestTaskId = current($blockedCores);

            $currentTaskPos = null;
            foreach ($pCores as $tasks) {
                if (!$tasks) {
                    continue;
                }

                $currentTask = current($tasks);

                $pos = array_search($currentTask, $this->tasks);

                if (is_null($currentTaskPos)) {
                    $currentTaskPos = $pos;
                } else if ($pos < $currentTaskPos) {
                    $currentTaskPos = $pos;
                }
            }

            $this->swap($currentTaskPos, $blockedTaskPos);

            $this->correct($taskListHandler);
        }
    }

    protected function unblockProcessor(array $pCores, array $blockedCores)
    {
        foreach ($pCores as $key => $tasks) {
            if (!$tasks) {
                unset($blockedCores[$key]);
                continue;
            }

            $blockedCores[$key] = false;
        }

        return $blockedCores;
    }

    public function hasDeadlock(array $blockedCores)
    {
        if (!$blockedCores) {
            return false;
        }

        $result = true;

        foreach ($blockedCores as $core) {
            $result &= ($core !== false);
        }

        return $result;
    }

    public function hasPendingTasks(array $cores)
    {
        foreach ($cores as $core) {
            if (!$core) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getTaskListDependency($taskId, TaskListHandler $taskListHandler, array $executedTasks)
    {
        $taskListDependency = [];

        foreach ($taskListHandler->getTaskLists() as $taskList) {
            $dependencies = $taskList->getDependencies($taskId);

            if (!$dependencies) {
                continue;
            }

            foreach ($dependencies as $dependency) {
                if (!isset($executedTasks[$dependency])) {
                    return false;
                }
            }

            $taskListDependency[] = $taskList;
        }

        return $taskListDependency;
    }

    public function swap($posA, $posB)
    {
        $tmp = $this->tasks[$posB];
        $this->tasks[$posB] = $this->tasks[$posA];
        $this->tasks[$posA] = $tmp;

        $tmp = $this->processors[$posB];
        $this->processors[$posB] = $this->processors[$posA];
        $this->processors[$posA] = $tmp;
    }

    public static function factory(TaskHandler $taskHandler)
    {
        $i = 0;

        $chromosome = new self();

        $size = $taskHandler->getSize();
        $maxBoundary = $size - 1;

        while ($i < $size) {
            $gene = mt_rand(0, $maxBoundary);

            if ($chromosome->hasTask($gene)) {
                continue;
            }

            $chromosome->addTask($i, $gene, mt_rand(0, 1));

            ++$i;
        }

        return $chromosome;
    }
}
