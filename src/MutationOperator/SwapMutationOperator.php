<?php

namespace EvilComp\MutationOperator;

use EvilComp\Entities\Chromosome;

/**
 * Class SwapMutationOperator
 */
class SwapMutationOperator implements MutationOperatorInterface
{
    public function mutation(Chromosome $chromosome)
    {
        $pos = [mt_rand(0, $chromosome->getSize() - 1)];

        $newPos = mt_rand(0, $chromosome->getSize() - 1);
        while (in_array($newPos, $pos)) {
            $newPos = mt_rand(0, $chromosome->getSize() - 1);
        }

        $pos[] = $newPos;

        $chromosome->swap($pos[0], $pos[1]);

        return $chromosome;
    }
}
