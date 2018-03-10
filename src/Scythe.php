<?php
/**
 * Scythe Renderer
 * A simple implementation of Laravel Blade for rendering blade views into a PSR-7 Response object.
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
        $settings = array_merge([
            'views_path' => null,
            'cache_path' => null,
            'namespaces' => [],
            'directives' => [],
        ], $settings);

        $this->setViewsPath($settings['views_path']);
        $this->setCachePath($settings['cache_path']);
        $this->setNamespaces($settings['namespaces']);
        $this->setDirectives($settings['directives']);

        $this->_build = [];

    }

    /**
     * Set views_path
     * @param string $path
     * @throws Exception if path does not exist or is not readable
     */
    public function setViewsPath($path)
    {
        if ( ! is_dir($path)) {
            throw new Exception("Renderer cannot find view path at '$path'");
        }
        if ( ! is_readable($path)) {
            throw new Exception("Renderer cannot read from views at '$path'");
        }

        $this->views_path = rtrim($path, '/');

    }

    /**
     * Set cache_path
     * @param string $path
     * @throws Exception if path does not exist or is not writeable
     */
    public function setCachePath($path)
    {
        if ( ! is_dir($path)) {
            throw new Exception("Renderer cannot find cache path at '$path'");
        }
        if ( ! is_readable($path) OR  ! is_writeable($path)) {
            throw new Exception("Renderer cannot read or write from cache at '$path', please set permissions to 0775");
        }

        $this->cache_path = rtrim($path, '/');
    }

    /**
     * Set namespaces
     * @param array $namespaces
     */
    public function setNamespaces(array $namespaces)
    {
        foreach ($namespaces as $name=>$path) {
            $this->addNamespace($name, $path);
        }
    }

    /**
     * Returns defined namespaces as array
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Set directives
     * @param array $directives
     */
    public function setDirectives(array $directives)
    {
        foreach ($directives as $match=>$callable) {
            $this->addDirective($match, $callable);
        }
    }

    /** TODO
     * Add a user defined directive
     *
     * @param string $match
     * @param callable $callback
     * @return void
     */
    public function addDirective($match, $callback)
    {
        $this->directives[$name] = $callback;
    }

    /**
     * Add a view namespace
     *
     * @param string $name
     * @param string $folder
     * @return void
     */
    public function addNamespace($name, $path)
    {
        if ( ! is_dir($path)) {
            throw new Exception("Renderer cannot find namespace path at '$path'");
        }
        if ( ! is_readable($path)) {
            throw new Exception("Renderer cannot read from namespace views at '$path'");
        }

        $this->namespaces[$name] = $path;

    }

    /**
     * Check that $template exists in the view path or
     * any namespaced paths
     *
     * @param string $template
     * @return boolean
     */
    public function exists($template)
    {
        return ($this->getTemplateFilepath($template) === false) ? false : true;

    }

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
        // check template exists
        if ( ! $this->exists($template)) {
            throw new Exception("Renderer cannot find template '$template'");
        }

        // compile template and dependencies as required
        $this->compile($template);

        // populate compiled template with data
        $out = $this->populate($template, $data);

        $this->reset();

        // add to response
        $response->getBody()->write($out);

        return $response;

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
        $out = $this->compileString($blade);

        $this->reset();

        return $this->populateCompiledString($out, $data);

    }

    /**
     * Checks for a compiled version of the $template file
     * If cache version does not exist or is stale
     * it will compile the template and it's dependencies
     * then store it in cache
     *
     * @param string $template
     * @return boolean
     */
    private function compile($template)
    {
        if ( ! $this->isCompiled($template, $newer=false)) {
            // fetch blade template
            $str = $this->getTemplateContents($template);
            // compile blade template
            $str = $this->compileString($str);
            // store compiled template in cache
            $this->storeCompiled($template, $str);
        }

    }

    /**
     * Populate a compiled template with $data
     *
     * @param string $str
     * @param array $data
     * @return string
     */
    private function populate($template, $data)
    {
        return $this->processInIsolation($this->getCompiledFilepath($template), $data);
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
        // while this has directives - multi pass?
        if (substr($str, 0, 9) == '@extends(') {
            $str = $this->handleExtends($str);
        }
        $str = $this->handleIncludes($str);
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

    /**
     * Reset the build area
     *
     * @return void
     */
    private function reset()
    {
        $this->_build = [];
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

    /**
     * Handles extends
     *
     * @param string $str
     * @return string
     */
    private function handleExtends($str)
    {
        // capture section(name, value) placeholders
		foreach ($this->getMatches('/\@section\([(\'|\")]([a-z]+)[(\'|\")]\s*,\s*[(\'|\")]([[:print:]]+)[(\'|\")]\)/i', $str) as $section) {
            $this->addBuild('sections', $section);
		}

		# capture section(name) placeholders
		foreach ($this->getMatches('/\@section\(\s*[\'|\"]([a-z0-9\-\_\.]+)[\'|\"]\s*\)\s*(.*)\s*\@endsection/sU', $str) as $section) {
            $this->addBuild('sections', $section);
		}

		# load compiled parent template
		foreach ($this->getMatches('/\@extends\(\s*[\'|\"]([a-z0-9\-\_\/\.]+)[\'|\"]\s*\)/i', $str) as $match) {
			$str = $this->getCompiledContents($match);
		}

		# merge child sections into parent
		foreach ($this->getBuild('sections') as $name => $content) {
			$str = $this->replaceSection($str, $name, $content);
        }

        return $str;

    }

    /**
     * Handles includes
     *
     * @param string $str
     * @return string
     */
    private function handleIncludes($str)
    {
		foreach ($this->getMatches('/\@include\(\s*[\'|\"]([a-z0-9\-\_\/\.]+)[\'|\"]\s*\)/i', $str) as $match) {
            $str = $this->replaceTag(sprintf("#\@include\([(\'\")]%s[(\'\")]\)#is", $match), $this->getCompiledContents($match), $str);
        }

		foreach ($this->getMatches('/\@includeif\(\s*[\'|\"]([a-z0-9\-\_\/\.]+)[\'|\"]\s*\)/i', $str) as $match) {
            if ($this->exists($match)) {
                $str = $this->replaceTag(sprintf("#\@includeif\([(\'\")]%s[(\'\")]\)#is", $match), $this->getCompiledContents($match), $str);
			} else {
			    $str = $this->replaceTag(sprintf("#\@includeif\([(\'\")]%s[(\'\")]\)#is", $match), null, $str);
			}
		}

        return $str;
    }

    /**
     * Returns $area stored in the build area
     *
     * @return array
     */
	private function getBuild($area)
	{
        if (isset($this->_build[$area])) {
            return $this->_build[$area];
        }

        return [];

	}

    /**
     * Add content to the build area for insertion into a parent
     * template
     *
     * @param array $section
     * @return void
     */
	private function addBuild($area, $section)
	{
        list($name, $str) = $section;
		$this->_build[$area][$name] = $this->compileString($str);

	}

    /**
     * Returns matches from $content between the tags in $expr
     *
     * @param string $expr
     * @param string $content
     * @return array
     */
    function getMatches($expr, $str)
    {
        $out = [];
        preg_match_all($expr, $str, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (count($match) == 2) {
                $out[] = $match[1];
            } elseif (count($match) == 3) {
                $out[] = [$match[1], $match[2]];
            }
        }

        return $out;

    }

    /**
     * Swaps content in a string
     *
     * @param string $expr
     * @param string $replace
     * @param string $content
     * @return string
     */
	private function replaceTag($expr, $replace, $content)
	{
		return preg_replace($expr, $replace, $content);
	}

    /**
     * Matches the two types of section tag to be replaced
     *
     * @param string $name
     * @param string $replace
     * @param string $content
     * @return string
     */
	private function replaceSection($str, $name, $replace)
	{
        $matches = [
            sprintf("/\@section\([(\'\")]%s[(\'\")]\)(.*?)\@(stop|show|endsection)/is", $name),
            sprintf("/\@yield\([(\'\")]%s[(\'\")]\)/is", $name),
        ];
        foreach ($matches as $expr) {
            $str = $this->replaceTag($expr, $replace, $str);
        }

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

			# echo with a default
			'/(\s*){{\s*(.+?)\s*or\s*(.+?)\s*}}/i' => '$1<?php echo (isset($2)) ? htmlentities($2) : $3; ?>',

			# echo an escaped variable
			'/(\s*){{\s*(.+?)\s*}}/i' => '$1<?php echo htmlentities($2); ?>',

			# echo an unescaped variable
			'/(\s*){!!\s*(.+?)\s*!!}/i' => '$1<?php echo $2; ?>',

            # variable display mutators, wrap these in htmlentities as necessary
            '/(\s*)@json\s*\((.*?)\)/i' => '$1<?php echo json_encode($2); ?>',
            '/(\s*)@lower\s*\((.*?)\)/i' => '$1<?php echo htmlentities(strtolower($2)); ?>',
            '/(\s*)@upper\s*\((.*?)\)/i' => '$1<?php echo htmlentities(strtoupper($2)); ?>',
            '/(\s*)@ucfirst\s*\((.*?)\)/i' => '$1<?php echo htmlentities(ucfirst(strtolower($2))); ?>',
            '/(\s*)@ucwords\s*\((.*?)\)/i' => '$1<?php echo htmlentities(ucwords(strtolower($2))); ?>',
            '/(\s*)@(format|sprintf)\s*\((.*?)\)/i' => '$1<?php echo htmlentities(sprintf($3)); ?>',


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

            # has statement
			'/(\s*)@has\s*\((.*?)\)/i' => '$1<?php if (isset($2) && ! empty($2)): ?>',
			'/(\s*)@endhas(\s*)/i' => '$1<?php endif; ?>',

			# handle special unless statement
			'/(\s*)@unless\s*\((.+?)\)/i' => '$1<?php if ( ! $2): ?>',
			'/(\s*)@endunless(\s*)/i' => '$1<?php endif; ?>',

            # each statements
            # eachelse matches first
            '/(\s*)@each\s*\((.*)\s*,\s*(.*)\s*,\s*[(\'|\")](.*)[(\'|\")],\s*(.*)\s*\)/i' => "$1@forelse ($3 as \$$4)\n@include($2)\n@empty\n@include($5)\n@endforelse",
            '/(\s*)@each\s*\((.*)\s*,\s*(.*)\s*,\s*[(\'|\")](.*)[(\'|\")]\s*\)/i' => "$1<?php foreach ($3 as \$$4): ?>\n@include($2)\n<?php endforeach; ?>",

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
        ob_start();

        extract($data);
        include $filepath;

        return ob_get_clean();

    }

    /**
     * Returns the namespace name part of the template path
     *
     * @param string $template
     * @return string
     */
    private function getNamespaceName($template)
    {
        $parts = explode('::', $template);

        return $parts[0];

    }

    /**
     * Returns the path to $template.blade.php if it is found
     * in the curent view path or user defined namespace(s)
     *
     * @param string $template
     * @return string or false
     */
    private function getTemplateFilepath($template)
    {
        if (strpos($template, '::')) {
            $name = $this->getNamespaceName($template);
            if ( ! isset($this->namespaces[$name])) {
                throw new Exception("View namespace '$name' is not defined, please define it with addNamespace()");
            }
            $filepath = sprintf("%s/%s%s", $this->namespaces[$name], ltrim(str_replace("$name::", "", $template), '/'), '.blade.php');

        } else {
            $filepath = sprintf("%s/%s%s", $this->views_path, ltrim($template, '/'), '.blade.php');
        }

        if (file_exists($filepath)) {
            return $filepath;
        }

        return false;

    }

    /**
     * Returns the contents of the $template file
     * searches views folder and all namespaces
     *
     * @param string $template
     * @return string
     */
    private function getTemplateContents($template)
    {
        // check for namespace
        // search view_path
        if ($this->exists($template))
        {
            return $this->getContents($this->getTemplateFilepath($template));
        }

        throw new Exception("Cannot find template '$template'");

    }

    /** TODO check template is compiled and not stale
     * Check for a compiled version of $template in the cache
     * Optionally compare timestamps to determine if cache is stale
     *
     * @param string $template
     * @param string $check_timestamps
     * @return boolean
     */
    private function isCompiled($template, $check_timestamps=false)
    {
        return false;
    }

    /**
     * Returns the path to the compiled version of $template in the cache
     *
     * @param string $template
     * @return string
     */
    private function getCompiledFilepath($template)
    {
        return $this->cache_path.'/'.md5($template);
    }

    /**
     * Returns the contents of the compiled $template
     * file from the cache folder
     *
     * @param string $template
     * @return string or boolean false
     */
    private function getCompiledContents($template)
    {
        $this->compile($template);
        $filepath = $this->getCompiledFilepath($template);
        if (file_exists($filepath))
        {
            return $this->getContents($filepath);
        }

        return false;

    }

    /** TODO exceptions
     * Stores the cmpiled template in the cache folder
     *
     * @param string $template
     * @param string $contents
     * @return boolean
     */
    private function storeCompiled($template, $contents)
    {
        $filepath = $this->getCompiledFilepath($template);
        return file_put_contents($filepath, $contents);

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
