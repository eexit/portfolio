<?php

use Silex\WebTestCase;
use Symfony\Component\Validator\Constraints;

class ContactFormTest extends WebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../src/portfolio.php';
    }
    
    public function testFormHasLandingPage()
    {
        $client = $this->createClient();
        $client->request('POST', '/contact.html');
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testSubmittedByCrawler()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/contact.html');
        $form = $crawler->selectButton('Send')->form();
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isOk());
    }
    
    public function testAllFieldEmptyValidatorMessages()
    {
        $client = $this->createClient();
        $crawler = $client->request('POST', '/contact.html');
        $not_blank_validator = new Constraints\NotBlank();
        
        $this->assertEquals(1, $crawler->filter('form')->count());
        $this->assertEquals(1, $crawler->filter('ul[class="warning"]')->count());
        $this->assertEquals(3, $crawler->filter('ul[class="warning"] > li')->count());
        
        $node_messages = $crawler->filter('ul[class="warning"] > li')->each(function($node, $i) {
            return $node->nodeValue;
        });
        
        foreach ($node_messages as $message) {
            $this->assertContains($not_blank_validator->message, $message);
        }
    }
    
    public function testNameMinLengthValidatorMessage()
    {
        $client = $this->createClient();
        $minlen_validator = new Constraints\MinLength(3);
        
        $crawler = $client->request('POST', '/contact.html', array(
            'name'      => 'Jo',
            'email'     => 'foobar@baz.tld',
            'message'   => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        ));
        
        $this->assertEquals(1, $crawler->filter('ul[class="warning"] > li')->count());
        $this->assertContains(str_replace('{{ limit }}', 3, $minlen_validator->message), $crawler->filter('ul[class="warning"] > li')->text());
    }
    
    public function testEmailInvalidValidatorMessage()
    {
        $client = $this->createClient();
        $email_validator = new Constraints\Email();
        
        $crawler = $client->request('POST', '/contact.html', array(
            'name'      => 'Joris Berthelot',
            'email'     => 'foobar',
            'message'   => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        ));
        
        $this->assertEquals(1, $crawler->filter('ul[class="warning"] > li')->count());
        $this->assertContains($email_validator->message, $crawler->filter('ul[class="warning"] > li')->text());
    }
}

?>