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

            $taskDependency = $this->getTaskDependencies($taskListHandler, $i, $gene);
            var_dump($taskDependency);

            if (!$taskDependency['on_same_core'] && !$taskDependency['on_other_core']) {
                ++$i;
                continue;
            } else if (!$taskDependency['on_same_core']) {
                asort($taskDependency['on_other_core']);
                var_dump($taskDependency['on_other_core']);

                while($taskDependency['on_other_core']) {
                    $taskPos = key($taskDependency['on_other_core']);
                    array_shift($taskDependency['on_other_core']);

                    $nextTaskPos = $i + 1;
                    $lowerBoundary = min($nextTaskPos, $taskPos);
                    $upperBoundary = max($nextTaskPos, $taskPos);

                    $taskPos = $this->getInnerDependencyPos(
                        $taskListHandler, $this->processors[$i], $lowerBoundary, $upperBoundary
                    );

                    if ($taskPos === false) {
                        var_dump('wololo');
                        continue;
                    }

                    $this->swap($i, $taskPos);
                    $i = min($i, $taskPos);

                    continue 2;
                }

                ++$i;
                continue;
            }

            asort($taskDependency['on_same_core']);
            $taskPos = key($taskDependency['on_same_core']);
            $this->swap($i, $taskPos);
            $i = min($i, $taskPos);
        }

        return $this;
    }

    protected function getTaskDependencies(TaskListHandler $taskListHandler, $currentPos, $currentGene)
    {
        $taskDependency = [
            'on_same_core' => [],
            'on_other_core' => [],
        ];

        foreach ($taskListHandler->getTaskLists() as $taskList) {
            $dependencies = $taskList->getDependencies($currentGene);

            if (!$dependencies) {
                continue;
            }

            foreach ($dependencies as $dependency) {
                $taskPos = array_search($dependency, $this->tasks);
                $proc = $this->processors[$taskPos];

                if ($taskPos < $currentPos) {
                    continue;
                } else if ($proc != $this->processors[$currentPos]) {
                    $taskDependency['on_other_core'][$taskPos] = $dependency;
                    continue;
                }

                $taskDependency['on_same_core'][$taskPos] = $dependency;
            }
        }

        return $taskDependency;
    }

    public function getInnerDependencyPos(TaskListHandler $taskListHandler, $currentProc, $begin, $end)
    {
        for ($i = $begin; $i <= $end; $i++) {
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
