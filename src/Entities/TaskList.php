<?php

namespace EvilComp\Entities;

/**
 * Class TaskList
 * @author yourname
 */
class TaskList
{
    protected $firstNode;

    /**
     * @param $taskId
     * @param $executionTime
     */
    public function __construct($taskId, $executionTime)
    {
        $this->firstNode = new TaskNode($taskId, $executionTime, 0);
    }

    public function __clone()
    {
        $tmp = $this->firstNode;
        $cFirstNode = clone $this->firstNode;

        $currentNode = $cFirstNode;

        $node = $this->firstNode;
        while ($node->getChild()) {
            $node = $node->getChild();

            $cNode = clone $node;

            $currentNode->setChild($cNode);
            $currentNode = $cNode;
        }

        $this->firstNode = $cFirstNode;
    }

    public function dump()
    {
        $node = $this->firstNode;

        $node->dump();

        $identationCounter = 1;
        while ($node->getChild()) {
            $node = $node->getChild();

            print(" -> ");

            $node->dump();
        }

        print("\n");
    }

    public function countNodes()
    {
        $i = 1;

        $node = $this->firstNode;
        while ($node->getChild()) {
            $node = $node->getChild();
            ++$i;
        }

        return $i;
    }

    /**
     * undocumented function
     *
     * @return void
     */
    public function getLastNodeDependency($taskId)
    {
        $lastNode = null;
        $node = $this->firstNode;

        while ($node->getChild()) {
            if ($node->getTaskId() == $taskId) {
                return $lastNode;
            }

            $lastNode = $node;
            $node = $node->getChild();
        }

        if ($node->getTaskId() == $taskId) {
            return $lastNode;
        }

        return null;
    }

    public function getDependencies($taskId)
    {
        $dependencies = [];

        $node = $this->firstNode;

        while ($node->getChild()) {
            if ($node->getTaskId() == $taskId) {
                return $dependencies;
            } else if ($node->getTaskId() != $taskId) {
                $dependencies[] = $node->getTaskId();
            }

            $node = $node->getChild();
        }

        if ($node->getTaskId() == $taskId) {
            return $dependencies;
        }

        return [];
    }

    public function addNode(TaskNode $node)
    {
        $lastNode = $this->getLastNode();
        $lastNode->setChild($node);
    }

    protected function getLastNode()
    {
        return $this->rGetLastNode($this->firstNode);
    }

    protected function rGetLastNode(TaskNode $node)
    {
        if (!$node->getChild()) {
            return $node;
        }

        return $this->rGetLastNode($node->getChild());
    }

    public function search($taskId)
    {
        return $this->rSearch($this->firstNode, $taskId);
    }

    protected function rSearch($node, $taskId)
    {
        if (!$node) {
            return false;
        } else if ($node->getTaskId() == $taskId) {
            return $node;
        }

        return $this->rSearch($node->getChild(), $taskId);
    }
}
