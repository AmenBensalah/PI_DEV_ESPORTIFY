<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FeedControllerTest extends WebTestCase
{
    public function testFilRouteLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fil');

        self::assertResponseRedirects('/login');
    }
}
