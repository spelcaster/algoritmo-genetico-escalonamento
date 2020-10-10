<?php

namespace EvilComp\MutationOperator;

/**
 * Class SwapMutationOperator
 */
class SwapMutationOperator implements MutationOperatorInterface
{
    public function mutation(array $chromosome)
    {
        $pos = [mt_rand(0, count($chromosome) - 1)];

        $newPos = mt_rand(0, count($chromosome) - 1);
        while (in_array($newPos, $pos)) {
            $newPos = mt_rand(0, count($chromosome) - 1);
        }

        $pos[] = $newPos;

        $tmp = $chromosome[$pos[0]];
        $chromosome[$pos[0]] = $chromosome[$pos[1]];
        $chromosome[$pos[1]] = $tmp;

        return $chromosome;
    }
}
