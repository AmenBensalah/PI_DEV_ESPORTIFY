<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseCharsetSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type');

        if ($contentType === null) {
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
            return;
        }

        if (stripos($contentType, 'text/html') !== false) {
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        }
    }
}
