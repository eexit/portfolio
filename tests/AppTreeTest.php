<?php

use Silex\WebTestCase;

class AppTreeTest extends WebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../src/portfolio.php';
    }
    
    public function testIndex()
    {
        $client = $this->createClient();
        $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testAbout()
    {
        $client = $this->createClient();
        $client->request('GET', '/about');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testCollection()
    {
        $client = $this->createClient();
        $client->request('GET', '/collections');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testGallery()
    {
        $this->markTestIncomplete('Not yet implemented');
    }
}

?>