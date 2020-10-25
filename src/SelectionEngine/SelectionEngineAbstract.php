<?php

namespace EvilComp\SelectionEngine;

/**
 * Class SelectionEngineAbstract
 */
abstract class SelectionEngineAbstract
{
    protected $allowRepetition;

    protected $population;

    protected $populationSize;

    protected $isGlobal;

    /**
     * @param $allowRepetition
     */
    public function __construct($allowRepetition = false)
    {
        $this->allowRepetition = $allowRepetition;

        $this->population = [];
        $this->isGlobal = false;
        $this->populationSize = 0;
    }

    public function __destruct()
    {
        unset($this->population);
    }

    /**
     * Expected and structure like:
     *   [
     *     [
     *       'chromosome' => [],
     *       'type' => 'parent/offspring',
     *       'fitness' => 0,
     *       'local_relative_fitness' => 0.0,
     *       'global_relative_fitness' => 0.0,
     *     ],
     *     // ...
     *   ]
     *
     */
    public function setPopulation(array $population)
    {
        $this->population = $population;
        $this->populationSize = count($population);

        return $this;
    }

    public function getPopulation()
    {
        return $this->population;
    }

    public function setIsGlobal($isGlobal = false)
    {
        $this->isGlobal = $isGlobal;

        return $this;
    }

    public function getIsGlobal()
    {
        return $this->isGlobal;
    }

    protected function getRelativeFitnessProp()
    {
        if ($this->isGlobal) {
            return 'global_relative_fitness';
        }

        return 'local_relative_fitness';
    }

    protected function validate($selectionLimit)
    {
        if (!$this->population) {
            throw new RuntimeException('Population is not set');
        } else if (!$this->allowRepetition && ($selectionLimit > $this->populationSize)) {
            throw new RuntimeException('If repetition is not allowed, then selection limit must be greater than the population size');
        }
    }

    abstract protected function runSelection($selectionLimit);

    public function select($selectionLimit)
    {
        $this->validate($selectionLimit); // throws in case of error

        return $this->runSelection($selectionLimit);
    }
}
