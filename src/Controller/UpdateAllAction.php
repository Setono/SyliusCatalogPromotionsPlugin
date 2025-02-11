<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Controller;

use Setono\SyliusCatalogPromotionPlugin\Message\Command\StartCatalogPromotionUpdate;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UpdateAllAction
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $this->commandBus->dispatch(new StartCatalogPromotionUpdate(triggeredBy: 'Clicking "Update all" button inside admin interface'));

        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add('success', 'setono_sylius_catalog_promotion.update_all_success');
        }

        return new RedirectResponse($this->getUrl($request));
    }

    private function getUrl(Request $request): string
    {
        $referrer = $request->headers->get('referer');
        if (null !== $referrer && '' !== $referrer) {
            return $referrer;
        }

        return $this->urlGenerator->generate('setono_sylius_catalog_promotion_admin_catalog_promotion_index');
    }
}
