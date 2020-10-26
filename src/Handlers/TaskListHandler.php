<?php

namespace EvilComp\Handlers;

use EvilComp\Entities\TaskList;
use EvilComp\Entities\TaskNode;
use RuntimeException;

/**
 * Class TaskListHandler
 * @author yourname
 */
class TaskListHandler
{
    protected $pathHandler;
    protected $taskHandler;
    protected $taskLists;

    public function __construct(TaskHandler $taskHandler, PathHandler $pathHandler)
    {
        $this->taskHandler = $taskHandler;
        $this->pathHandler = $pathHandler;
    }

    public function dump()
    {
        foreach ($this->taskLists as $key => $l) {
            print("{$key}: ");
            $l->dump();
        }
    }

    public function dumpEndpoints(array $endpoints)
    {
        foreach ($endpoints as $e) {
            var_dump($e);

            foreach ($this->taskLists as $l) {
                var_dump(implode(" ", $l->getDependencies($e)));
            }
        }
    }

    public function getTaskLists()
    {
        return $this->taskLists;
    }

    public function generate(array $startingPoints)
    {
        $this->taskLists = [];

        for ($i = 0; $i < $this->pathHandler->getSize(); $i++) {
            $path = $this->pathHandler->at($i);

            $from = $path[0];
            $to = $path[1];
            $deliveryTime = $path[2];

            $currentTask = $this->taskHandler->at($from);
            $nextTask = $this->taskHandler->at($to);

            if (is_null($currentTask) || is_null($nextTask)) {
                throw new RuntimeException("Invalid Task ID");
            } else if (!in_array($from, $startingPoints)) {
                break;
            }

            $taskList = new TaskList($currentTask[0], $currentTask[1]);

            $nextTaskNode = new TaskNode($to, $nextTask[1], $deliveryTime);
            $taskList->addNode($nextTaskNode);

            $j = $i + 1;

            $this->generateTaskList($taskList, $nextTaskNode, $j);
        }
    }

    protected function generateTaskList(TaskList $list, TaskNode $lastNode, $i)
    {
        $currentTaskId = $lastNode->getTaskId();
        $tmpLinkedList = clone $list;

        // $i > que o total de paths possiveis
        if ($i > $this->pathHandler->getSize()) {
            $this->taskLists[] = $tmpLinkedList;
            return;
        }

        $linkedListsSize = count($this->taskLists);
        for ($i; $i < $this->pathHandler->getSize(); $i++) {
            $path = $this->pathHandler->at($i);
            $lastTaskId = $path[0];

            // id da task atual nao pode ser maior que o id da ultima task na lista
            if ($lastTaskId != $currentTaskId) {
                continue;
            }

            $to = $path[1];

            $nextTask = $this->taskHandler->at($to);
            $deliveryTime = $path[2];

            $newTaskList = clone $tmpLinkedList;

            $currentTaskNode = new TaskNode($to, $nextTask[1], $deliveryTime);
            $newTaskList->addNode($currentTaskNode);

            $j = $i + 1;

            $this->generateTaskList($newTaskList, $currentTaskNode, $j);
        }

        if (count($this->taskLists) == $linkedListsSize) {
            $this->taskLists[] = $tmpLinkedList;
        }
    }
}

