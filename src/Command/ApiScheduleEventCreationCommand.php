<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\SynchronizedEntity;
use App\Event\EventEvent;
use App\Events;

class ApiScheduleEventCreationCommand extends ApiScheduleEntityCreationCommand
{
    protected function configure()
    {
        $this
            ->setName('app:sync:events')
            ->setDescription('Schedule Events for synchronization with api')
        ;
    }

    protected function getEntityClassname(): string
    {
        return Event::class;
    }

    protected function scheduleCreation(SynchronizedEntity $event): void
    {
        $this->dispatcher->dispatch(new EventEvent(null, $event), Events::EVENT_CREATED);
    }
}
