<?php

namespace EvilComp\Entities;

/**
 * Class TaskNode
 * @author yourname
 */
class TaskNode
{
    protected $taskId;
    protected $executionTime;
    protected $deliveryTime;
    protected $child;

    /**
     * @param $taskId
     * @param $executionTime
     * @param $deliveryTime
     */
    public function __construct($taskId, $executionTime, $deliveryTime)
    {
        $this->taskId = $taskId;
        $this->executionTime = $executionTime;
        $this->deliveryTime = $deliveryTime;

        $this->child = null;
    }

    public function dump()
    {
        print($this->taskId . "({$this->executionTime}, {$this->deliveryTime})");
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    public function setChild(TaskNode $child)
    {
        $this->child = $child;
    }

    public function getChild()
    {
        return $this->child;
    }
}
