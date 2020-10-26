<?php

namespace EvilComp\MutationOperator;

use EvilComp\Entities\Chromosome;

/**
 * Class MutationOperatorInterface
 */
interface MutationOperatorInterface
{
    public function mutation(Chromosome $chromosome);
}
