<?php

namespace EvilComp\SelectionEngine;

/**
 * Class TournamentSelection
 */
class TournamentSelection extends SelectionEngineAbstract
{
    protected $rounds;

    protected $participants;

    public function __construct($allowRepetition = false)
    {
        parent::__construct($allowRepetition);

        $this->participants = 3;
        $this->rounds = 3;
    }

    public function setRounds($rounds)
    {
        $this->rounds = $rounds;

        return $this;
    }

    public function getRounds()
    {
        return $this->rounds;
    }

    public function setParticipants($participants)
    {
        $this->participants = $participants;

        return $this;
    }

    public function getParticipants()
    {
        return $this->participants;
    }

    protected function runSelection($selectionLimit)
    {
        $selected = [];
        $selectedPos = [];

        $i = 0;
        while ($i < $selectionLimit) {
            $chromosome = $this->draw();

            $key = array_keys($chromosome)[0];
            if (!$this->allowRepetition && isset($selectedPos[$key])) {
                continue;
            }

            $selectedPos[$key] = true;
            $selected[] = $chromosome[$key];

            ++$i;
        }

        return $selected;
    }

    protected function draw()
    {
        $rounds = $this->rounds;
        $participants = $this->participants;

        return $this->tournament($rounds, $participants);
    }

    protected function tournament($rounds, $participantLimit, $lastWinner = false)
    {
        if ($rounds == 0) {
            return [
                $lastWinner['index'] => $lastWinner['chromosome'],
            ];
        }

        $participants = [];

        $prop = $this->getRelativeFitnessProp();
        $epsilon = 0.0000000005;

        $limit = $participantLimit;

        if ($lastWinner) {
            --$limit;
            $participants[] = $lastWinner;
        }

        for ($i = 0; $i < $limit; $i++) {
            $pos = mt_rand(0, ($this->populationSize - 1));

            $participant = $this->population[$pos];

            $participant['index'] = $pos;

            $participants[] = $participant;
        }

        usort($participants, function ($a, $b) use ($prop, $epsilon) {
            if (($b[$prop] + $epsilon) > $a[$prop]) {
                return 1;
            }

            return 0;
        });

        return $this->tournament(--$rounds, $participantLimit, $participants[0]);
    }
}
