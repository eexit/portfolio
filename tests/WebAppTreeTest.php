<?php

use Silex\WebTestCase;

class WebAppTreeTest extends WebTestCase
{
    const NB_LINKS_LAYOUT = 4;
    
    public function createApplication()
    {
        return require __DIR__ . '/../src/portfolio.php';
    }
    
    public function testLayoutElements()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
        
        // DOM validation
        $this->assertEquals(1, $crawler->filter('title:contains("Portfolio")')->count());
        $this->assertEquals(1, $crawler->filter('meta[http-equiv="content-type"]')->count());
        $this->assertEquals(1, $crawler->filter('meta[charset]')->count());
        $this->assertEquals(1, $crawler->filter('meta[name="author"]')->count());
        $this->assertEquals(1, $crawler->filter('meta[name="description"]')->count());
        $this->assertEquals(3, $crawler->filter('head > link')->count());
        $this->assertEquals(3, $crawler->filter('header > nav > ul > li')->count());
        $this->assertEquals(1, $crawler->filter('footer')->count());
        $this->assertEquals(self::NB_LINKS_LAYOUT, count($crawler->filter('a')->links()));
        
        // Tests the eexit.net back link
        $client->click($crawler->selectLink('eexit.net')->link());
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('http://www.eexit.net/', $client->getRequest()->getUri());
    }
    
    public function testGetIndex()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
        
        // DOM validation
        $this->assertTrue(1 == $crawler->filter('h1:contains("Portfolio")')->count());
    }
    
    public function testGetAbout()
    {
        $client = $this->createClient();
        $client->request('GET', '/about.html');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testGetContact()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/contact.html');
        $this->assertTrue($client->getResponse()->isOk());
        
        // DOM validation
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals(1, $crawler->filter('form[action][method="post"]')->count());
        $this->assertEquals(1, $crawler->filter('input[type="text"]')->count());
        $this->assertEquals(1, $crawler->filter('input[type="email"]')->count());
        $this->assertEquals(1, $crawler->filter('input[type="submit"]')->count());
        $this->assertEquals(1, $crawler->filter('textarea[rows][cols]')->count());
        $this->assertEquals(3, $crawler->filter('label')->count());
    }
    
    public function testGetSets()
    {
        $client = $this->createClient();
        $client->request('GET', '/sets.html');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testGetSetNotExists()
    {
        $this->markTestSkipped('Missing code business layer!');
        
        $client = $this->createClient();
        $client->request('GET', '/set/foo.html');
        $this->assertTrue($client->getResponse()->isNotFound());
    }
    
    public function testGetSetExists()
    {
        $client = $this->createClient();
        $client->request('GET', '/travels.html');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testGetGalleryNotExists()
    {
        $this->markTestSkipped('Missing code business layer!');
        
        $client = $this->createClient();
        
        $client->request('GET', '/travels.html');
        $this->assertTrue($client->getResponse()->isOk());
        
        $client->request('GET', '/travels/foo.html');
        $this->assertTrue($client->getResponse()->isNotFound());
    }
    
    public function testGetGalleryExists()
    {
        $client = $this->createClient();
        
        $client->request('GET', '/travels.html');
        $this->assertTrue($client->getResponse()->isOk());
        
        $client->request('GET', '/travels/chile.html');
        $this->assertTrue($client->getResponse()->isOk());
    }
}
?>