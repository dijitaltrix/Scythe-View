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
        $scythe = $this->getRenderer($settings);

        $response = $scythe->render(
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
     * Get only the response body
     *
     * @param string $template 
     * @param array $payload 
     * @param array $settings 
     * @return strong
     */
    public function getBody($template, $payload=[], $settings=[])
    {
        $response = $this->getResponse('hello', [
            'name' => 'World'
        ]);

        return $response->getBody()->getContents(); 
        
    }
    
    public function testResponse()
    {
        $response = $this->getResponse("hello", [
            'name' => 'world',
        ]);
        
        $this->assertInstanceOf(Response::class, $response);
        
        $out = $response->getBody()->getContents();
        
        $expected = '<h1>Hello world</h1>';
        
        $this->assertEquals($expected, trim($out));
        
    }
    
    // test cache is invalidated when template timestamp updated
    
    // test everything on one page to ensure it's not clobbered
    
    /**
     * Returns the blade part of a test template
     *
     * @param string $str 
     * @return string
     */
    private function getBlade($str)
    {
        return preg_replace("/^{{--(.*)--}}/ms", "", $str);
    }
    
    /**
     * Returns the expected output part of a test template
     * the lines between {{-- Expected\n and \n--}}
     * @param string $str 
     * @return string
     */
    private function getExpected($str)
    {
        preg_match("/^{{--\sExpected(.*)--}}/ms", $str, $matches);
        if (isset($matches[1])) {
            return trim($matches[1]);
        }
        
        throw new Exception("Cannot find expected output in test, did you add it?");

    }
    

}
