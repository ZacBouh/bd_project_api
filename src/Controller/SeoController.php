<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
final class SeoController extends AbstractController
{
    public function __construct(
        public string $APP_URL, // param: app.front_base_url
    ) {}

    public function __invoke(): Response
    {
        $urls = [];

        // Home (publique)
        $urls[] = [
            'loc' => rtrim($this->APP_URL, '/') . '/',
            'changefreq' => 'daily',
        ];

        // Pages dynamiques publiques quand elle seront rendues accessibles sans connexion
        // foreach ($titles as $t) {
        //     $urls[] = [
        //         'loc' => sprintf('%s/titles/%d', rtrim($this->frontBaseUrl, '/'), $t->getId()),
        //         'changefreq' => 'weekly',
        //         'lastmod' => $t->getUpdatedAt()?->format('Y-m-d'),
        //     ];
        // }

        $xml = $this->renderView('sitemap.xml.twig', ['urls' => $urls]);

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'max-age=86400',
        ]);
    }
}
