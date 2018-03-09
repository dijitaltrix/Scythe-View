<?php

namespace Tests;

use Exception;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Response;

class RenderedTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     * Get configured renderer instance
     *
     * @param array $settings 
     * @return Scythe
     */
    public function getRenderer($settings=[])
    {
        return new \Slim\Views\Scythe(array_merge([
            'views_path' => 'tests/rendered',
            'cache_path' => 'tests/rendered/cache',
        ], $settings));
    }
    
    /**
     * Get a complete response
     *
     * @param string $template 
     * @param array $payload 
     * @param array $settings 
     * @return Response
     */
    public function getResponse($template, $payload=[], $settings=[])
    {
        $view = $this->getRenderer($settings);

        $response = $view->render(
            new Response(
                200,
                new Headers(), 
                new Body(fopen('php://temp', 'r+'))
            ), 
            $template,
            $payload
        );

        $response->getBody()->rewind();

        return $response;
        
    }
    
    /**
     * Get only the response body content
     *
     * @param string $template 
     * @param array $payload 
     * @param array $settings 
     * @return strong
     */
    public function getBodyContents($response)
    {
        return $response->getBody()->getContents(); 
    }
    
    
    public function testHelloWorld()
    {
        $payload = ['name' => 'world'];
        
        $response = $this->getResponse('hello', $payload);
        
        $out = $this->getBodyContents($response);
        
        $expected = '<h1>Hello world</h1>';
        
        $this->assertEquals($expected, trim($out));
        
    }
    
    public function testExtends()
    {
        $template = 'inheritance/child';
        $response = $this->getResponse($template);
        
        $out = $this->getBodyContents($response);
        
        $expected = file_get_contents("tests/rendered/expected/$template.php");
        
        $this->assertEquals($expected, trim($out));
        
    }
    
    /*
    public function testNamespacePassedInConstructor()
    {
        $payload = [
            'muppets' => (object) [
                (object) ['name' => 'Kermit'],
                (object) ['name' => 'Miss Piggy'],
                (object) ['name' => 'Fozzy Bear'],
            ]
        ];
        $settings = [
            'namespaces' => [
                'muppets' => 'tests/namespaces/muppets/',
            ],
        ];

        $response = $this->getResponse("muppets::cast/list", $payload, $settings);
        
        $this->assertInstanceOf(Response::class, $response);
        
        $out = $response->getBody()->getContents();
        
        $expected = '<h1>Muppets cast list</h1>
<ul>
    <li>Kermit</li>
    <li>Miss Piggy</li>
    <li>Fozzy Bear</li>
</ul>';
        
        $this->assertEquals($expected, trim($out));
        
    }

    public function testNamespaceAddMethod()
    {
        $payload = [
            'muppets' => (object) [
                (object) ['name' => 'Kermit'],
                (object) ['name' => 'Miss Piggy'],
                (object) ['name' => 'Fozzy Bear'],
            ]
        ];
        
        $view = new \Slim\Views\Scythe([
            'views_path' => 'tests/rendered',
            'cache_path' => 'tests/rendered/cache',
        ]);
        $view->addNamespace('muppets', 'tests/namespaces/muppets/');

        $response = $view->render(new Response, "muppets::cast/list", $payload);
        $response->getBody()->rewind();
        
        $this->assertInstanceOf(Response::class, $response);
        
        $out = $response->getBody()->getContents();
 
        $expected = '<h1>Muppets cast list</h1>
<ul>
    <li>Kermit</li>
    <li>Miss Piggy</li>
    <li>Fozzy Bear</li>
</ul>';
        
        $this->assertEquals($expected, trim($out));
        
    }
    */
    
    // test cache is invalidated when template timestamp updated
    
    // test everything on one page to ensure it's not clobbered
    

    

}
