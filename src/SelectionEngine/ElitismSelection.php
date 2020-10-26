<?php

namespace EvilComp\SelectionEngine;

/**
 * Class ElitismSelection
 */
class ElitismSelection extends SelectionEngineAbstract
{
    protected $elitismRate;

    public function __construct($allowRepetition = false)
    {
        parent::__construct($allowRepetition);

        $this->elitismRate = 20 / 100;
    }

    public function setElitismRate($elitismRate)
    {
        $this->elitismRate = $elitismRate;

        return $this;
    }

    protected function runSelection($selectionLimit = 0)
    {
        $prop = $this->getRelativeFitnessProp();
        $epsilon = 0.0000000005;

        usort($this->population, function ($a, $b) use ($prop, $epsilon) {
            if (($b[$prop] + $epsilon) > $a[$prop]) {
                return 1;
            }

            return 0;
        });

        $selectSize = (int) ($this->elitismRate * $this->populationSize);

        if ($selectionLimit !== 0) {
            $selectSize = $selectionLimit;
        }

        $selected = array_slice($this->population, 0, $selectSize);

        return array_column($selected, 'chromosome');
    }
}
