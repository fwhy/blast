<?php

namespace Fwhy\Blast\Event;

use Fwhy\Blast\Builder;

abstract class BlastEvent
{
    public function __construct(
        public readonly Builder $builder
    ) {
    }
}
