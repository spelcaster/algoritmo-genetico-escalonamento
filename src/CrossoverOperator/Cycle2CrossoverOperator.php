<?php

namespace EvilComp\CrossoverOperator;

/**
 * Class Cycle2CrossoverOperator
 * @author yourname
 */
class Cycle2CrossoverOperator implements CrossoverOperatorInterface
{
    public function crossover(array $parentA, array $parentB)
    {
        $pos = mt_rand(0, count($parentA) - 1);

        $offspring1 = $this->getOffspring($parentA, $parentB, $pos);
        $offspring2 = $this->getOffspring($parentB, $parentA, $pos);

        return [
            $offspring1,
            $offspring2
        ];
    }

    public function getOffspring(array $pA, array $pB, $pos)
    {
        $offspring = $pB;

        $firstGene = $pB[$pos];

        while ($pA[$pos] != $firstGene) {
            $gene = $pA[$pos];
            $offspring[$pos] = $gene;

            for ($i = 0; $i < count($offspring); $i++) {
                if (($offspring[$i] == $gene) && ($i == $pos)) {
                    continue;
                } else if ($offspring[$i] != $gene) {
                    continue;
                }

                $pos = $i;

                break;
            }
        }

        $offspring[$pos] = $pA[$pos];

        return $offspring;
    }
}
