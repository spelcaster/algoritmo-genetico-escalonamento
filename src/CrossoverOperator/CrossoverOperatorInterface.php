<?php

namespace EvilComp\CrossoverOperator;

/**
 * Interface CrossoverOperatorInterface
 * @author yourname
 */
interface CrossoverOperatorInterface
{
    public function crossover(array $parentA, array $parentB);
}
