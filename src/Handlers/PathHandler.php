<?php

namespace EvilComp\Handlers;

use RuntimeException;

/**
 * Class PathHandler
 * @author yourname
 */
class PathHandler
{
    protected $paths;

    protected $size;

    /**
     * @param $filepath
     */
    public function __construct($filepath)
    {
        $fp = fopen($filepath, 'r');

        if (!$fp) {
            throw new RuntimeException("File not found in PathHandler");
        }

        $this->size = 0;
        while (!feof($fp)) {
            $line = fgets($fp);

            if (!$line) {
                continue;
            }

            $data = explode(',', $line);

            $data = array_map('trim', $data);

            $this->paths[] = $data;
            ++$this->size;
        }

        fclose($fp);
    }

    public function dump()
    {
        foreach ($this->paths as $path) {
            var_dump(implode(" ", $path));
        }
    }

    public function getSize()
    {
        return $this->size;
    }

    public function at($index)
    {
        if (!isset($this->paths[$index])) {
            return null;
        }

        return $this->paths[$index];
    }
}
