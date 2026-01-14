<?php 
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function testApiDocUrlIsSuccessful(): void
    {
        $client = self::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/api/doc');
        
        self::assertResponseIsSuccessful();
        }
        
        public function testApiAccountUrlIsSecure(): void
        {
            $client = self::createClient();
            $client->request('GET', '/api/user/1}');

            self::assertResponseStatusCodeSame(401);

    }
}