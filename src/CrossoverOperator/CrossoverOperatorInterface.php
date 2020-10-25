<?php

namespace EvilComp\CrossoverOperator;

use EvilComp\Entities\Chromosome;

/**
 * Interface CrossoverOperatorInterface
 * @author yourname
 */
interface CrossoverOperatorInterface
{
    public function crossover(Chromosome $parentA, Chromosome $parentB);
}
