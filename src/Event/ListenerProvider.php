<?php

namespace Fwhy\Blast\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, array<int, array<callable>>> */
    private array $listeners = [];

    /**
     * Add a listener for a specific event class
     *
     * @param string $eventClass
     * @param callable $listener
     * @param int $priority Higher number executes earlier
     * @return void
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][$priority][] = $listener;
    }

    /**
     * @param object $event
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $listeners = [];

        foreach ($this->listeners as $eventClass => $prioritizedListeners) {
            if ($event instanceof $eventClass) {
                // Sort priorities in descending order (highest priority first)
                krsort($prioritizedListeners);
                foreach ($prioritizedListeners as $callables) {
                    foreach ($callables as $listener) {
                        $listeners[] = $listener;
                    }
                }
            }
        }

        return $listeners;
    }
}
