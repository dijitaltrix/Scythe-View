<?php

namespace Tests;

use Exception;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Response;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaults()
    {
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
        ]);
        
        $this->assertInstanceOf(\Dijix\Views\Scythe::class, $view);
        
    }
    
    public function testSetInvalidView()
    {
        $path = 'a/non/existent/path';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Renderer cannot find view path at '$path'");
        
        $view = new \Dijix\Views\Scythe([
            'views_path' => $path,
            'cache_path' => 'tests',
        ]);
        
    }
    
    public function testSetInvalidCache()
    {
        $path = 'a/non/existent/path';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Renderer cannot find cache path at '$path'");
        
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => $path,
        ]);
        
    }
    
    public function testSetNamespace()
    {
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
            'namespaces' => [
                'muppets' => 'tests',
            ]
        ]);
        
        $this->assertArrayHasKey("muppets", $view->getNamespaces());
        
    }
    
    public function testSetNamespaces()
    {
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
            'namespaces' => [
                'muppets' => 'tests',
                'fraggles' => 'tests',
            ]
        ]);
        
        $this->assertArrayHasKey("muppets", $view->getNamespaces());
        $this->assertArrayHasKey("fraggles", $view->getNamespaces());
        
    }
    
    public function testAddNamespaces()
    {
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
        ]);
        $view->addNamespace('muppets', 'tests');
        
        $this->assertArrayHasKey("muppets", $view->getNamespaces());
        
    }
    
    public function testSetInvalidNamespace()
    {
        $path = 'a/non/existent/path';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Renderer cannot find namespace path at '$path'");
        
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
            'namespaces' => [
                'muppets' => $path,
            ]
        ]);
        
    }
    
    public function testSetDirective()
    {
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
            'namespaces' => [],
            'directives' => [
                '/@now/i' => '<?php echo date("c"); ?>',
            ]
        ]);
        
        $this->assertArrayHasKey('/@now/i', $view->getDirectives());
        
    }
    
    public function testSetDirectives()
    {
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
            'directives' => [
                '/@now/i' => '<?php echo date("c"); ?>',
                '/@date\((.*),\s?(.*)\)/i' => '<?php echo date($2, strtotime($1)); ?>',
            ]
        ]);
        
        $this->assertArrayHasKey('/@now/i', $view->getDirectives());
        $this->assertArrayHasKey('/@date\((.*),\s?(.*)\)/i', $view->getDirectives());
        
    }
    
    public function testAddDirectives()
    {
        $view = new \Dijix\Views\Scythe([
            'views_path' => 'tests',
            'cache_path' => 'tests',
        ]);
        $view->addDirective('/@hero/i', 'Big Hero 6');
        
        $this->assertArrayHasKey('/@hero/i', $view->getDirectives());
        
    }
}
