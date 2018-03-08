<?php
/**
 * Scythe Renderer
 * A clean implementation of Laravel Blade for rendering blade views into a PSR-7 Response object.
 * With no dependencies it works well with Slim Framework 3.
 *
 * NB: Not all Laravel Blade features are supported
 *
 * @link      https://github.com/ignition/Scythe-View
 * @copyright Copyright (c) 2018 Ian Grindley
 * @license   https://github.com/ignition/Scythe-View/blob/master/LICENSE.md (MIT License)
 */

namespace Slim\Views;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;


class Scythe
{
    /**
     * Location of the views folder 
     * @var string
     */
    protected $views_path;

    /**
     * Location of the cache folder 
     * @var string
     */
    protected $cache_path;
    
    /**
     * Holds user defined namespaces 
     * @var array
     */
    protected $namespaces;

    /**
     * Holds user defined directives 
     * @var array
     */
    protected $directives;

    /**
     * Holds contents during template construction 
     * @var array
     */
    private $_build;



    /**
     * Constructor.
     *
     * @param array $settings
     */
    public function __construct($settings=[])
    {
        //TODO move these into attributes so they don't get clobbered
        $settings = array_merge([
            'views_path' => null,
            'cache_path' => null,
            'namespaces' => [],
            'directives' => []
        ], $settings);
        
        foreach ($settings as $k=>$v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
        
        // normalise view paths
        $this->views_path = rtrim($this->views_path, '/');
        $this->cache_path = rtrim($this->cache_path, '/');
        
        //TODO disallow setting this in settings above
        $this->_build = [];
        
    }
    
    /**
     * //TODO getters and setters
     * setNamespace
     * setDirective
    **/

    /**
     * Render a template
     *
     * @param ResponseInterface $response
     * @param string $template
     * @param array $data
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, $template, array $data = [])
    {
        // compile template and dependencies as required
        $this->compile($template);
        
        // populate compiled template with data
        $out = $this->populate($template, $data);
        
        // add to response
        $response->getBody()->write($out);

        return $response;

    }

    /**
     * Checks for a compiled version of the $template file
     * If cache version does not exist or is stale
     * it will compile the template and cache it
     *
     * @param string $template 
     * @return boolean
     */
    private function compile($template)
    {
        return true;
        // $content = $this->getTemplate($template);
    }

    /**
     * Render a blade string, populated with $data
     *
     * @param string $blade
     * @param array $data
     * @return string
     */
    public function renderString($blade, array $data = [])
    {
        $view = $this->compileString($blade);

        return $this->populateCompiledString($view, $data);
    
    }
    
    /**
     * Compile any blade syntax in $str to plain php
     * Returns a plain php file, ready to be populated with data
     * 
     * NB This method must be public to allow renderer unit testing
     *
     * @param string $str 
     * @return string
     */
    public function compileString($str)
    {
        $str = $this->handleSections($str);
        $str = $this->handleExtends($str);
        $str = $this->convertPlaceholders($str);
        $str = $this->handleDirectives($str);
        
        return $str;
    
    }
    
    /**
     * Populate a compiled (plain php) string with $data
     *
     * @param string $str 
     * @param array $data 
     * @return string
     */
    private function populateCompiledString($str, $data=[])
    {
        // temporarily cache string, populate compiled file, delete and return rendered output
        $tmp_filepath = $this->cache_path.'/'.md5(time());
        file_put_contents($tmp_filepath, $str);
        $out = $this->processInIsolation($tmp_filepath, $data);
        unlink($tmp_filepath);

        return $out;

    }
    
    /** TODO
     * Converts blade user directives to plain php
     *
     * @param string $str 
     * @return string
     */
    private function handleDirectives($str)
    {
        return $str;
    }

    /** TODO
     * Handles extends
     *
     * @param string $str 
     * @return string
     */
    private function handleExtends($str)
    {
        return $str;
    }
    
    /** TODO
     * Handles sections
     *
     * @param string $str 
     * @return string
     */
    private function handleSections($str)
    {
        return $str;
    }

    /**
     * Replaces blade placholders with their php equivalents
     * the bulk of the conversion is done here
     *
     * @param string $str 
     * @return string
     */
    private function convertPlaceholders($str)
    {
        return preg_replace(array_keys($this->getReplacements()), array_values($this->getReplacements()), $str);
    }


    /**
     * Returns an array of blade placeholders with their php substitutions
     * These are simple preg swaps
     *
     * @return array
     */
    private function getReplacements()
    {
        return [
            
			# remove blade comments
			'/(\s*){{--\s*(.+?)\s*--}}/i' => '$1',

			# echo an escaped variable 
			'/(\s*){{\s*(.+?)\s*}}/i' => '$1<?php echo htmlentities($2); ?>',

			# echo an unescaped variable
			'/(\s*){!!\s*(.+?)\s*!!}/i' => '$1<?php echo $2; ?>',

			# echo with a default
			'/(\s*){{\s*(.+?)\s*or\s*(.+?)\s*}}/i' => '$1<?php isset($2) ? echo $2 : $3; ?>',
            
            # variable display mutators, wrap these in htmlentities as necessary
            '/(\s*)@json\s*\((.*?)\)/i' => '$1<?php echo json_encode($2); ?>',
            '/(\s*)@lower\s*\((.*?)\)/i' => '$1<?php echo htmlentities(strtolower($2)); ?>',
            '/(\s*)@upper\s*\((.*?)\)/i' => '$1<?php echo htmlentities(strtoupper($2)); ?>',
            '/(\s*)@ucfirst\s*\((.*?)\)/i' => '$1<?php echo htmlentities(ucfirst(strtolower($2))); ?>',
            '/(\s*)@ucwords\s*\((.*?)\)/i' => '$1<?php echo htmlentities(ucwords(strtolower($2))); ?>',
            
            # wordwrap has multiple parameters
            '/(\s*)@wrap\s*\((.*?)\)/i' => '$1<?php echo htmlentities(wordwrap($2)); ?>',
            '/(\s*)@wrap\s*\((.*?)\s*,\s*(.*?)\)/i' => '$1<?php echo htmlentities(wordwrap($2, $3)); ?>',
            '/(\s*)@wrap\s*\((.*?)\s*,\s*(.*?)\s*,\s*(.*?)\)/i' => '$1<?php echo htmlentities(wordwrap($2, $3, $4)); ?>',

			# set and unset statements
			'/(\s*)@set\s*\((.*?)\s*\,\s*(.*)\)/i' => '$1<?php $2 = $3; ?>',
			'/(\s*)@unset\s*\((.*?)\)/i' => '$1<?php unset($2); ?>',
            
            # isset statement
			'/(\s*)@isset\s*\((.*?)\)/i' => '$1<?php if (isset($2)): ?>',
			'/(\s*)@endisset(\s*)/i' => '$1<?php endif; ?>',

			# handle special unless statement
			'/(\s*)@unless\s*\((.+?)\)/i' => '$1<?php if ( ! $2): ?>',
			'/(\s*)@endunless(\s*)/i' => '$1<?php endif; ?>',
            
            # switch statement
            '/(\s*)@switch\s*\((.*?)\)/i' => '$1<?php switch ($2): ?>',
            '/(\s*)@case\s*\((.*?)\)/i' => '$1<?php case $2: ?>',
            '/(\s*)@default(\s*)/i' => '$1<?php default: ?>',
            '/(\s*)@endswitch(\s*)/i' => '$1<?php endswitch: ?>',

			# handle special forelse loop
			'/(\s*)@forelse\s*\(\s*(\S*)\s*as\s*(\S*)\s*\)(\s*)/i' => "$1<?php if ( ! empty($2)): ?>$1<?php foreach ($2 as $3): ?>$4",
			'/(\s*)@empty(\s*)/' => "$1<?php endforeach; ?>$1<?php else: ?>$2",
			'/(\s*)@endforelse(\s*)/' => '$1<?php endif; ?>$2',

			# handle loops and control structures
			'/(?(R)\((?:[^\(\)]|(?R))*\)|(?<!\w)(\s*)@(if|elseif|foreach|for|while)(\s*(?R)+))/i' => '$1<?php $2$3: ?>$4',
			'/(\s*)@(else)(\s*)/i' => '$1<?php else: ?>$3',
			'/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/' => '$1<?php $2; ?>$3',
            
            # swap out @php and @endphp
			'/(\s*)@php(\s*)/i' => '$1<?php',
			'/(\s*)@endphp(\s*)/i' => '$1?>',
			
        ];
        
    }



    /**
     * Render php code from string using data and return as String
     *
     * @param string $str 
     * @param array $data 
     * @return string
     */
    private function processInIsolation($filepath, $data=[])
    {
        extract($data);
        include $filepath;

        return ob_get_clean();
        
    }
    


    /**
     * Returns the contents of the $template file
     * searches views folder and all namespaces
     *
     * @param string $template 
     * @return string
     */
    private function getTemplate($template)
    {
        // check for namespace
        // search view_path
        if ($this->exists($template))
        {
            return $this->getContents($this->findTemplate($template));
        }
        
        throw new Exception("Cannot find template '$template'");

    }

    /**
     * Returns the contents of the compiled $template 
     * file from the cache folder
     *
     * @param string $template 
     * @return string
     */
    private function getCompiled($template)
    {
        $compiled_filepath = $this->cache_path.'/'.md5($template);
        if (file_exists($compiled_filepath))
        {
            return $this->getContents($compiled_filepath);
        }
        
    }
    
    /**
     * Returns the contents of the file at $path
     *
     * @param string $path 
     * @return string
     */
    private function getContents($path)
    {
        return file_get_contents($path);
    }

}
