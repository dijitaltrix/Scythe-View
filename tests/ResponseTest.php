<?php

namespace Tests;

use Exception;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Response;

class ResponseTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Get configured renderer instance
     *
     * @param array $settings 
     * @return Scythe
     */
    public function getRenderer($settings=[])
    {
        return new \Dijitaltrix\Views\Scythe(array_merge([
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
    
    public function testHelloWorld()
    {
        $response = $this->getResponse("hello", [
            'name' => 'world',
        ]);
        
        $this->assertInstanceOf(Response::class, $response);
        
        $out = $response->getBody()->getContents();
        
        $expected = '<h1>Hello world</h1>';
        
        $this->assertEquals($expected, trim($out));
        
    }
    
    public function testMake()
    {
        $view = $this->getRenderer();

        $str = $view->make("hello", [
            'name' => 'world',
        ]);
        
        $expected = '<h1>Hello world</h1>';
        
        $this->assertEquals($expected, trim($str));
        
    }

}
