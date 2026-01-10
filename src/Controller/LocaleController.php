<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale_switch', requirements: ['locale' => 'en|fr|ar'], methods: ['GET'])]
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $locale);
        }

        $request->setLocale($locale);

        $referer = $request->headers->get('referer');
        if (is_string($referer) && $this->isSafeReferer($request, $referer)) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToRoute('app_home');
    }

    private function isSafeReferer(Request $request, string $referer): bool
    {
        $refererHost = parse_url($referer, PHP_URL_HOST);

        if ($refererHost === null) {
            return true;
        }

        return $refererHost === $request->getHost();
    }
}

