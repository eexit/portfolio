<?php

use Silex\WebTestCase;

class WebAppTreeTest extends WebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../src/portfolio.php';
    }
    
    public function testGetIndex()
    {
        $client = $this->createClient();
        $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testGetAbout()
    {
        $client = $this->createClient();
        $client->request('GET', '/about.html');
        $this->assertTrue($client->getResponse()->isOk());
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