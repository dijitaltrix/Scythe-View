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
        return new \Dijitaltrix\Views\Scythe(array_merge([
            'views_path' => 'tests/rendered',
            'cache_path' => 'tests/rendered/cache',
        ], $settings));
    }
    
    /**
     * Create a call to the renderer with the necessary gubbins 
     *
     * @param string $template
     * @param array $payload
     * @return Response
     */
    public function callRenderer($template, $payload=[])
    {
        $response = $this->view->render(
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

    /*
    public function testDirectives()
    {
        $this->view = $this->getRenderer($settings);
        
        $this->view->addDirective('/@now/i', function(){
            return "<?php echo date('Y-m-d H:i:s'); ?>";
        });
        $this->view->addDirective('/@date\((.*),\s?(.*)\)/i', function(){
            return "<?php echo date($1, strtotime($2)); ?>";
        });
        
        $template = '/directives/all';
        $payload = [];

        $response = $this->callRenderer($template, $payload);
        
        $out = $this->getBodyContents($response);
        
        $expected = file_get_contents("tests/rendered/directives/expected/all.html");
        
        $this->assertEquals($expected, trim($out));
        
    }
    */
    
    public function testInclude()
    {
        $template = '/include/include';
        $response = $this->getResponse($template);
        
        $out = $this->getBodyContents($response);
        
        $expected = file_get_contents("tests/rendered/include/expected/include.html");
        
        $this->assertEquals($expected, trim($out));
        
    }

    public function testIncludeIf()
    {
        $template = '/include/includeif';
        $response = $this->getResponse($template);
        
        $out = $this->getBodyContents($response);
        
        $expected = file_get_contents("tests/rendered/include/expected/includeif.html");
        
        $this->assertEquals($expected, trim($out));
        
    }
    
    public function testIncludeWhen()
    {
        $template = '/include/includewhen';
        $payload = [
            'muppet' => 'Kermit',
        ];
        $response = $this->getResponse($template, $payload);
        
        $out = $this->getBodyContents($response);
        
        $expected = file_get_contents("tests/rendered/include/expected/includewhen.html");
        
        $this->assertEquals($expected, trim($out));
        
    }

    public function testExtends()
    {
        $template = 'extends/child';
        $response = $this->getResponse($template);

        $out = $this->getBodyContents($response);

        $expected = file_get_contents("tests/rendered/extends/expected/child.html");

        $this->assertEquals($expected, trim($out));

    }

    public function testNamespacePassedInConstructor()
    {
        $payload = [
            'muppets' => [
                ['name' => 'Kermit'],
                ['name' => 'Miss Piggy'],
                ['name' => 'Fozzy Bear'],
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
            'muppets' => [
                ['name' => 'Kermit'],
                ['name' => 'Miss Piggy'],
                ['name' => 'Fozzy Bear'],
            ]
        ];
        
        $view = new \Dijitaltrix\Views\Scythe([
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
    
    // test cache is invalidated when template timestamp updated
    
    // test everything on one page to ensure it's not clobbered
    

    

}
