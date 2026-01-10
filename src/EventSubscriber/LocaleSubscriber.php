<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        $locale = $request->attributes->get('_locale');
        if (is_string($locale) && $locale !== '') {
            $session->set('_locale', $locale);
            $request->setLocale($locale);

            return;
        }

        $sessionLocale = $session->get('_locale');
        if (is_string($sessionLocale) && $sessionLocale !== '') {
            $request->setLocale($sessionLocale);
        }
    }
}

