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
        $this->markTestIncomplete('Not yet implemented');
    }
    
    public function testAbout()
    {
        $this->markTestIncomplete('Not yet implemented');
    }
    
    public function testCollection()
    {
        $this->markTestIncomplete('Not yet implemented');
    }
    
    public function testGallery()
    {
        $this->markTestIncomplete('Not yet implemented');
    }
}

?>