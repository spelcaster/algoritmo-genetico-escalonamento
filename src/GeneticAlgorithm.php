<?php

namespace EvilComp;

use EvilComp\CrossoverOperator\CrossoverOperatorInterface;
use EvilComp\Entities\Chromosome;
use EvilComp\FitnessOperator\FitnessOperatorAbstract;
use EvilComp\MutationOperator\MutationOperatorInterface;
use EvilComp\SelectionEngine\ElitismSelection;
use EvilComp\SelectionEngine\SelectionEngineAbstract;
use EvilComp\Handlers\TaskHandler;
use EvilComp\Handlers\TaskListHandler;

/**
 * Class GeneticAlgorithm
 */
class GeneticAlgorithm
{
    protected $population;

    protected $fitnessOperator;

    protected $crossoverOperator;

    protected $mutationOperator;

    protected $populationSize;

    protected $generationLimit;

    protected $crossoverRate;

    protected $mutationRate;

    protected $elitismPreserveRate;

    public function __construct(
        $populationSize = 100,
        $generationLimit = 50,
        $crossoverRate = 80,
        $mutationRate = 2,
        $elitismPreserveRate = 20
    ) {
        $this->populationSize = $populationSize;
        $this->generationLimit = $generationLimit;
        $this->crossoverRate = $crossoverRate / 100;
        $this->mutationRate = $mutationRate / 100;
        $this->elitismPreserveRate = $elitismPreserveRate / 100;
        $this->population = [];
    }

    protected function reset()
    {
        $this->population = [];
    }

    public function __destruct()
    {
        unset($this->population);
    }

    public function setSelectionEngine(SelectionEngineAbstract $engine)
    {
        $this->selectionEngine = $engine;

        return $this;
    }

    public function setCrossoverOperator(CrossoverOperatorInterface $operator)
    {
        $this->crossoverOperator = $operator;

        return $this;
    }

    public function setMutationOperator(MutationOperatorInterface $operator)
    {
        $this->mutationOperator = $operator;

        return $this;
    }

    public function setFitnessOperator(FitnessOperatorAbstract $operator)
    {
        $this->fitnessOperator = $operator;

        return $this;
    }

    protected function generatePopulation(
        TaskHandler $taskHandler,
        TaskListHandler $taskListHandler
    ) {
        $this->population = [];

        for ($i = 0; $i < $this->populationSize; $i++) {
            $chromosome = Chromosome::factory($taskHandler);
            $chromosome->correct($taskListHandler);
            $this->population[$i] = $chromosome;
        }
    }

    protected function selection($population, $selectionLimit)
    {
        var_dump('selection');
        $this->selectionEngine->setPopulation($population);

        return $this->selectionEngine->select($selectionLimit);
    }

    protected function crossover(
        TaskListHandler $taskListHandler, array $selectedPopulation, $size
    ) {
        var_dump('crossover');
        $offspring = [];
        for ($i = 0; $i < $size; $i+=2) {
            $j = $i + 1;

            $newOffspring = $this->crossoverOperator->crossover(
                $selectedPopulation[$i], $selectedPopulation[$j]
            );

            if (!is_array($newOffspring)) {
                throw new RuntimeException("Crossover Operator failed to generate pair of children");
            }

            foreach ($newOffspring as $chromosome) {
                $chromosome->correct($taskListHandler);
            }

            $offspring = array_merge($offspring, $newOffspring);
        }

        return $offspring;
    }

    protected function mutation(
        TaskListHandler $taskListHandler,
        array &$offspring,
        $size,
        $totalMutation
    ) {
        var_dump("mutation");
        $mutationCache = [];

        $i = 0;
        while ($i < $totalMutation) {
            $pos = mt_rand(0, ($size - 1));

            if (isset($mutationCache[$pos])) { // same chromosome cannot suffer multiple mutation
                continue;
            }

             $chromosome = $this->mutationOperator->mutation(
                $offspring[$pos]
             );

            $chromosome->correct($taskListHandler);

            $offspring[$pos] = $chromosome;

            ++$i;
        }
    }

    protected function updatePopulation(array $population, array $offspring)
    {
        $selectionMethod = new ElitismSelection();

        var_dump('prepare next generation');
        foreach ($population as $p) {
            $p['chromosome']->dump();
        }

        $selectionMethod->setPopulation(array_merge($population, $offspring))
                        ->setIsGlobal(true);

        $nextGen = $selectionMethod->select($this->populationSize);

        shuffle($nextGen); // shuffle next generation

        var_dump('next generation');
        foreach ($nextGen as $p) {
            $p->dump();
        }

        $this->population = $nextGen;
    }

    public function run(
        TaskHandler $taskHandler,
        TaskListHandler $taskListHandler
    ) {
        $epsilon = 0.0000000001;

        $offspringSize = (int) ($this->crossoverRate * $this->populationSize);
        $mutationLimit = (int) ($this->mutationRate * $this->populationSize);

        $this->generatePopulation($taskHandler, $taskListHandler);

        $generation = 0;
        while ($generation < $this->generationLimit) {
            var_dump("Generation #{$generation}");
            $globalFitness = 0;

            //population fitness
            $populationFitness = [];
            $populationFitnessSum = 0;
            for ($i = 0; $i < $this->populationSize; $i++) {
                $chromosome = $this->population[$i];

                $fitness = $this->fitnessOperator->calculate($chromosome);

                $populationFitness[$i] = [
                    'chromosome' => $chromosome,
                    'fitness' => $fitness,
                    'local_relative_fitness' => 0.0,
                    'global_relative_fitness' => 0.0,
                    'type' => 'parent',
                ];

                $populationFitnessSum += $fitness;
            }

            for ($i = 0; $i < $this->populationSize; $i++) {
                $populationFitness[$i]['local_relative_fitness'] = $populationFitness[$i]['fitness'] / $populationFitnessSum;
            }

            $selectedParents = $this->selection($populationFitness, $offspringSize);

            $offspring = $this->crossover($taskListHandler, $selectedParents, $offspringSize);

            $this->mutation($taskListHandler, $offspring, $offspringSize, $mutationLimit);

            //offspring fitness
            $offspringFitness = [];
            $offspringFitnessSum = 0;
            for ($i = 0; $i < $offspringSize; $i++) {
                $chromosome = $offspring[$i];

                $fitness = $this->fitnessOperator->calculate($chromosome);

                $offspringFitness[$i] = [
                    'chromosome' => $chromosome,
                    'fitness' => $fitness,
                    'local_relative_fitness' => 0.0,
                    'global_relative_fitness' => 0.0,
                    'type' => 'offspring',
                ];

                $offspringFitnessSum += $fitness;
            }

            $globalFitnessSum = $populationFitnessSum + $offspringFitnessSum;

            for ($i = 0; $i < $offspringSize; $i++) {
                $offspringFitness[$i]['local_relative_fitness'] = $offspringFitness[$i]['fitness'] / $offspringFitnessSum;
                $offspringFitness[$i]['global_relative_fitness'] = $offspringFitness[$i]['fitness'] / $globalFitnessSum;
            }

            for ($i = 0; $i < $this->populationSize; $i++) {
                $populationFitness[$i]['global_relative_fitness'] = $populationFitness[$i]['fitness'] / $globalFitnessSum;
            }

            $this->updatePopulation($populationFitness, $offspringFitness);

            unset($offspring);
            unset($offspringFitness);
            unset($populationFitness);

            ++$generation;
        }
    }
}
