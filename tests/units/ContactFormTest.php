<?php

use Silex\WebTestCase;
use Symfony\Component\Validator\Constraints;

class ContactFormTest extends WebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../../src/portfolio.php';
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
    
    public function testSucceedMessage()
    {
        $this->_createsMailTester();
        $client = $this->createClient();
        $crawler = $client->request('POST', '/contact.html', array(
           'name'       => 'Joris Berthelot',
           'email'      => 'admin@eexit.net',
           'message'    => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        ));
        
        $this->assertContains('Your message has been successfully sent!', $crawler->text());
    }
    
    public function testAllFieldEmptyValidatorMessages()
    {
        $client = $this->createClient();
        $crawler = $client->request('POST', '/contact.html');
        $not_blank_validator = new Constraints\NotBlank();
        
        $this->assertEquals(3, $crawler->filter('div[class*="error"]')->count());
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($not_blank_validator->message, $crawler->filter('div[class*="error"] span[class="help-block"]')->eq($i)->text());
        }
    }
    
    public function testNameMinLengthValidatorMessage()
    {
        $client = $this->createClient();
        $minlen_validator = new Constraints\MinLength(3);
        
        $crawler = $client->request('POST', '/contact.html', array(
            'name'      => 'Jo', // Must be under 3 chars
            'email'     => 'foobar@baz.tld',
            'message'   => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        ));
        
        $this->assertContains(str_replace('{{ limit }}', 3, $minlen_validator->message), $crawler->filter('div[class*="error"] span[class="help-block"]')->eq(0)->text());
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
        
        $this->assertContains($email_validator->message, $crawler->filter('div[class*="error"] span[class="help-block"]')->eq(0)->text());
    }
    
    public function testMailIsSent()
    {
        $this->_createsMailTester();
        $client = $this->createClient();
        $client->request('POST', '/contact.html', array(
            'name'      => 'Joris Berthelot',
            'email'     => 'admin@eexit.net',
            'message'   => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        ));
        
        var_dump($this->app['mailer.logger']->dump());
    }
    
    protected function _createsMailTester()
    {
        $this->app['swiftmailer.transport'] = new \Swift_Transport_NullTransport($this->app['swiftmailer.transport.eventdispatcher']);
        $this->app['mailer.logger'] = $this->app->share(function() {
            return new \Swift_Plugins_LoggerPlugin(new \Swift_Plugins_Loggers_ArrayLogger());
        });
        $this->app['mailer']->registerPlugin($this->app['mailer.logger']);
    }
}

?>