<?php

namespace Tests;

use Exception;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Response;

class CompiledTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     * Get configured renderer instance
     *
     * @param array $settings 
     * @return Scythe
     */
    public function getRenderer($settings=[])
    {
        return new \Dijix\Views\Scythe(array_merge([
            'views_path' => 'tests/rendered',
            'cache_path' => 'tests/rendered/cache',
        ], $settings));
    }

    /**
     * Get the compiled (cached) template as string
     *
     * @param string $template 
     * @param array $payload 
     * @param array $settings 
     * @return String
     */
    public function getCompiled($template, $settings=[]) 
    {
        $view = $this->getRenderer($settings);
        return $view->getCompiled($template);

    }
    
    public function testCompiledControlStructures()
    {
        $view = $this->getRenderer();
        
        $folder = './tests/compiled/control';
        // loop through each file in control structures
        $files = array_filter(scandir($folder), function($elem) use ($folder) {
            if (is_file("$folder/$elem") && strstr($elem, '.blade.php')) {
                return true;
            }
        });
        
        foreach ($files as $template) 
        {
            $content = file_get_contents("$folder/$template");
            // extract blade code and expected output
            $blade = $this->getBlade($content);
            $expected = $this->getExpected($content);
            // compile to string, but do not render (populated with vars)
            $out = $view->compileString($blade);

            $this->assertEquals($expected, trim($out));
            
        }

    }

    public function testCompiledVariables()
    {
        $view = $this->getRenderer();
        
        $folder = './tests/compiled/variables';
        // loop through each file in control structures
        $files = array_filter(scandir($folder), function($elem) use ($folder) {
            if (is_file("$folder/$elem") && strstr($elem, '.blade.php')) {
                return true;
            }
        });
        
        foreach ($files as $template) 
        {
            $content = file_get_contents("$folder/$template");
            // extract blade code and expected output
            $blade = $this->getBlade($content);
            $expected = $this->getExpected($content);
            // compile to string, but do not render (populated with vars)
            $out = $view->compileString($blade);

            $this->assertEquals($expected, trim($out));
            
        }

    }
    
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
