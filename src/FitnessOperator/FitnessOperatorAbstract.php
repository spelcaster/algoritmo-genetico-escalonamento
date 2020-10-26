<?php

namespace EvilComp\FitnessOperator;

use EvilComp\Handlers\TaskHandler;
use EvilComp\Handlers\TaskListHandler;

/**
 * Class FitnessOperatorAbstract
 * @author yourname
 */
abstract class FitnessOperatorAbstract
{
    protected $taskHandler;
    protected $taskListHandler;

    public function setTaskHandler(TaskHandler $taskHandler)
    {
        $this->taskHandler = $taskHandler;

        return $this;
    }

    public function getTaskHandler()
    {
        return $this->taskHandler;
    }

    public function setTaskListHandler(TaskListHandler $taskListHandler)
    {
        $this->taskListHandler =  $taskListHandler;

        return $this;
    }

    public function getTaskListHandler()
    {
        return $this->taskListHandler;
    }
}
