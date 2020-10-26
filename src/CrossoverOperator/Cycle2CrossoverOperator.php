<?php

namespace EvilComp\CrossoverOperator;

use EvilComp\Entities\Chromosome;

/**
 * Class Cycle2CrossoverOperator
 * @author yourname
 */
class Cycle2CrossoverOperator implements CrossoverOperatorInterface
{
    public function crossover(Chromosome $parentA, Chromosome $parentB)
    {
        $pos = mt_rand(0, $parentA->getSize() - 1);

        $offspring1 = $this->getOffspring($parentA, $parentB, $pos);
        $offspring2 = $this->getOffspring($parentB, $parentA, $pos);

        return [
            $offspring1,
            $offspring2
        ];
    }

    public function getOffspring(Chromosome $pA, Chromosome $pB, $pos)
    {
        $offspring = clone $pB;
        $tasksOffspring = $offspring->getTasks();

        $tasksB = $pB->getTasks();

        $tasksA = $pA->getTasks();
        $processorsA = $pA->getProcessors();

        $firstGene = $tasksB[$pos];

        while ($tasksA[$pos] != $firstGene) {
            $gene = $tasksA[$pos];

            $offspring->addTask($pos, $gene, $processorsA[$pos]);

            for ($i = 0; $i < $offspring->getSize(); $i++) {
                if (($tasksOffspring[$i] == $gene) && ($i == $pos)) {
                    continue;
                } else if ($tasksOffspring[$i] != $gene) {
                    continue;
                }

                $pos = $i;

                break;
            }
        }

        $offspring->addTask($pos, $tasksA[$pos], $processorsA[$pos]);

        return $offspring;
    }
}
