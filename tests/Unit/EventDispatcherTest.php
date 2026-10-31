<?php

use Fwhy\Blast\Builder;
use Fwhy\Blast\Event\AfterBuildPagesEvent;
use Fwhy\Blast\Event\AfterLoadHooksEvent;
use Fwhy\Blast\Event\AfterRenderEvent;
use Fwhy\Blast\Event\BeforeRenderEvent;
use Fwhy\Blast\Event\EventDispatcher;
use Fwhy\Blast\Event\ListenerProvider;
use Fwhy\Blast\Event\PostBuildEvent;
use Psr\EventDispatcher\StoppableEventInterface;

test('listener is called', function () {
    $provider = new ListenerProvider();
    $dispatcher = new EventDispatcher($provider);

    $called = false;
    $receivedEvent = null;

    $provider->addListener(AfterBuildPagesEvent::class, function (AfterBuildPagesEvent $event) use (&$called, &$receivedEvent) {
        $called = true;
        $receivedEvent = $event;
    });

    $builder = new Builder();
    $event = new AfterBuildPagesEvent($builder);
    $result = $dispatcher->dispatch($event);

    expect($called)->toBeTrue()
        ->and($result)->toBe($event)
        ->and($receivedEvent)->toBe($event);
});

test('priority execution order', function () {
    $provider = new ListenerProvider();
    $dispatcher = new EventDispatcher($provider);

    $executionOrder = [];

    $provider->addListener(BeforeRenderEvent::class, function () use (&$executionOrder) {
        $executionOrder[] = 'low_priority';
    }, 10);

    $provider->addListener(BeforeRenderEvent::class, function () use (&$executionOrder) {
        $executionOrder[] = 'high_priority';
    }, 100);

    $provider->addListener(BeforeRenderEvent::class, function () use (&$executionOrder) {
        $executionOrder[] = 'default_priority';
    }, 0);

    $builder = new Builder();
    $dispatcher->dispatch(new BeforeRenderEvent($builder));

    expect($executionOrder)->toBe(['high_priority', 'low_priority', 'default_priority']);
});

test('stoppable event', function () {
    $provider = new ListenerProvider();
    $dispatcher = new EventDispatcher($provider);

    $stoppableEvent = new class implements StoppableEventInterface {
        public bool $stopped = false;
        public int $callCount = 0;

        public function isPropagationStopped(): bool
        {
            return $this->stopped;
        }
    };

    $provider->addListener(get_class($stoppableEvent), function ($event) {
        $event->callCount++;
        $event->stopped = true;
    }, 100);

    $provider->addListener(get_class($stoppableEvent), function ($event) {
        $event->callCount++;
    }, 10);

    $dispatcher->dispatch($stoppableEvent);

    expect($stoppableEvent->callCount)->toBe(1);
});

test('builder events dispatching', function () {
    $builder = new Builder();
    $dispatcher = $builder->getEventDispatcher();

    expect($dispatcher)->toBeInstanceOf(EventDispatcher::class);
    /** @var EventDispatcher $dispatcher */
    $provider = $dispatcher->getListenerProvider();
    expect($provider)->toBeInstanceOf(ListenerProvider::class);
    /** @var ListenerProvider $provider */

    $dispatchedEvents = [];

    $provider->addListener(AfterLoadHooksEvent::class, function () use (&$dispatchedEvents) {
        $dispatchedEvents[] = 'AfterLoadHooksEvent';
    });

    $provider->addListener(AfterBuildPagesEvent::class, function () use (&$dispatchedEvents) {
        $dispatchedEvents[] = 'AfterBuildPagesEvent';
    });

    $provider->addListener(BeforeRenderEvent::class, function () use (&$dispatchedEvents) {
        $dispatchedEvents[] = 'BeforeRenderEvent';
    });

    $provider->addListener(AfterRenderEvent::class, function () use (&$dispatchedEvents) {
        $dispatchedEvents[] = 'AfterRenderEvent';
    });

    $provider->addListener(PostBuildEvent::class, function () use (&$dispatchedEvents) {
        $dispatchedEvents[] = 'PostBuildEvent';
    });

    $builder->loadConfig();
    $builder->createDirectories();
    $builder->loadHooks();
    $builder->buildPages();
    $builder->render();
    $builder->output();

    expect($dispatchedEvents)->toContain('AfterLoadHooksEvent')
        ->and($dispatchedEvents)->toContain('AfterBuildPagesEvent')
        ->and($dispatchedEvents)->toContain('BeforeRenderEvent')
        ->and($dispatchedEvents)->toContain('AfterRenderEvent')
        ->and($dispatchedEvents)->toContain('PostBuildEvent');
});
