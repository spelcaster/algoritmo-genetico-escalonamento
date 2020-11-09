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
        $i = 0;
        while ($i < $this->getSize()) {
            $gene = $this->tasks[$i];

            $taskDependency = [];
            foreach ($taskListHandler->getTaskLists() as $l) {
                $dependencies = $l->getDependencies($gene);

                if (!$dependencies) {
                    continue;
                }

                foreach ($dependencies as $dependency) {
                    $taskPos = array_search($dependency, $this->tasks);
                    $proc = $this->processors[$taskPos];

                    if ($taskPos < $i) {
                        continue;
                    } else if ($proc != $this->processors[$i]) {
                        $nextTaskPos = $i + 1;
                        $lowerBoundary = min($nextTaskPos, $taskPos);
                        $upperBoundary = max($nextTaskPos, $taskPos);

                        if ($lowerBoundary == $upperBoundary) {
                            continue;
                        }

                        $taskPos = $this->getInnerDependencyPos(
                            $taskListHandler, $this->processors[$i], $lowerBoundary, $upperBoundary
                        );

                        if ($taskPos === false) {
                            continue;
                        }

                        $this->swap($i, $taskPos);
                        $i = min($i, $taskPos);
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

            $taskPos = array_search(array_shift($taskDependency), $this->tasks);

            $this->swap($i, $taskPos);

            $i = min($i, $taskPos);
        }

        return $this;
    }

    public function getInnerDependencyPos(TaskListHandler $taskListHandler, $currentProc, $begin, $end)
    {
        for ($i = $begin; $i < $end; $i++) {
            $gene = $this->tasks[$i];

            foreach ($taskListHandler->getTaskLists() as $l) {
                $dependencies = $l->getDependencies($gene);

                if (!$dependencies) {
                    continue;
                }

                foreach ($dependencies as $dependency) {
                    $taskPos = array_search($dependency, $this->tasks);
                    $proc = $this->processors[$taskPos];

                    if (($taskPos < $i) || ($taskPos > $end)) {
                        continue;
                    } else if ($proc != $this->processors[$i]) {
                        if ($proc == $currentProc) {
                            return $taskPos;
                        }

                        return $i;
                    }
                }
            }
        }

        return false;
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
