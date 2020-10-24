<?php

namespace EvilComp\Handlers;

use RuntimeException;

/**
 * Class TaskHandler
 * @author yourname
 */
class TaskHandler
{
    protected $tasks;
    protected $size;

    /**
     * @param $filepath
     */
    public function __construct($filepath)
    {
        $fp = fopen($filepath, 'r');

        if (!$fp) {
            throw new RuntimeException("File not found in TaskHandler");
        }

        $this->size = 0;
        while (!feof($fp)) {
            $line = fgets($fp);

            if (!$line) {
                continue;
            }

            $data = explode(',', $line);

            $data = array_map('trim', $data);

            $this->tasks[] = $data;
            ++$this->size;
        }

        fclose($fp);
    }

    public function dump()
    {
        foreach ($this->tasks as $taskId => $task) {
            var_dump($taskId, implode(" ", $task));
        }
    }

    public function getSize()
    {
        return $this->size;
    }

    public function at($index)
    {
        if (!isset($this->tasks[$index])) {
            return null;
        }

        return $this->tasks[$index];
    }
}
