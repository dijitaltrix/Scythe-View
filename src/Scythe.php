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

namespace Dijitaltrix\Views;

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
     * Compact the output, strips unnecessary whitespace
     * @var boolean
     */
    protected $compact;
    
    /**
     * Holds contents during template construction
     * @var array
     */
    private $_build;
    
    /**
     * Holds loop helper class
     * @var object
     */
    private $_loop;


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
            'compact' => false,
            'namespaces' => [],
            'directives' => [],
        ], $settings);

        $this->setViewsPath($settings['views_path']);
        $this->setCachePath($settings['cache_path']);
        $this->setNamespaces($settings['namespaces']);
        $this->setDirectives($settings['directives']);

        $this->_build = [];
        
        $this->_loop = new Loop();

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
     * Returns defined namespaces as array
     *
     * @return array
     */
    public function getNamespaces()
    {
        if (is_array($this->namespaces)) {
            return $this->namespaces;
        }

        return [];

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
     * Returns defined namespaces as array
     *
     * @return array
     */
    public function getDirectives()
    {
        if (is_array($this->directives)) {
            return $this->directives;
        }

        return [];

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
        $this->directives[$match] = $callback;
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
        if ( ! $this->exists($template)) {
            throw new Exception("Renderer cannot find template '$template'");
        }

        $this->compile($template);
        
        $out = $this->populate($template, $data);
        $out = $this->cleanup($out);
        
        if ($this->compact) {
            $out = $this->compact($out);
        }
        
        $this->reset();

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
        $out = $this->populateCompiledString($out, $data);
        $out = $this->cleanup($out);

        if ($this->compact) {
            $out = $this->compact($out);
        }

        $this->reset();

        return $out;

    }

    /**
     * Returns a rendered blade file, populated with $data
     *
     * @param string $blade
     * @param array $data
     * @return string
     */
    public function make($template, array $data = [])
    {
        $str = $this->getCompiledContents($template);

        return $this->renderString($str, $data);

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
            // complete stacks
            $str = $this->includeStacks($str);
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
     * strip unnecessary whitespace from the output
     *
     * @param string $str 
     * @return string
     */
	private function compact($str)
	{
		$str = trim($str);
		$str = preg_replace('/>(\s*?)</is', '><', $str);
		
		return $str;

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

    /**
     * Insert contents of _build stacks into template replacing their placeholders
     * This must be called last thing as stacks can be pushed to at any point in the 
     * compilation process
     *
     * @param string $str 
     * @return string 
     */
    private function includeStacks($str)
    {
        foreach ($this->getBuild('stacks') as $name => $content)
        {
            $str = $this->replaceTag(sprintf("/@stack\s?\(\s*[\'\"]%s[\'\"]\s*\)/is", $name), $content, $str);
        }
        
        return $str;
        
    }
    
    /** 
     * Converts blade user directives to plain php
     *
     * @param string $str
     * @return string
     */
    private function handleDirectives($str)
    {
		foreach ($this->getDirectives() as $match => $callback) {
            if (is_callable($callback)) {
                $str = $this->replaceTag($match, call_user_func($callback), $str);
            } else {
                $str = $this->replaceTag($match, $this->compileString($callback), $str);
            }
        }
        
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

        # capture push sections for stacks
		foreach ($this->getMatches('/\@push\(\s*[\'|\"]([a-z0-9\-\_\.]+)[\'|\"]\s*\)\s*(.*)\s*\@endpush/sU', $str) as $stack) {
            $this->addBuild('stacks', $stack, $append=true);
		}

		# load compiled parent template
		foreach ($this->getMatches('/\@extends\(\s*[\'|\"]([a-z0-9:\-\_\/\.\]+)[\'|\"]\s*\)/i', $str) as $match) {
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
		foreach ($this->getMatches('/\@include\s?\(\s*[\'|\"]([a-z0-9:\-\_\/\.]+)[\'|\"]\s*,\s*\[(.*?)\]\s*\)/is', $str) as $match) {
            $extract = sprintf('<?php extract([%s]); ?>', $match[1]);
            $str = $this->replaceTag(sprintf("#@include\s?\([(\'\")]%s[(\'\")]\s*,\s*\[%s\]\s*\)#is", $match[0], $match[1]), $extract.$this->getCompiledContents($match[0]), $str);
        }
        
		foreach ($this->getMatches('/\@include\s?\(\s*[\'|\"]([a-z0-9:\-\_\/\.]+)[\'|\"]\s*\)/i', $str) as $match) {
            $str = $this->replaceTag(sprintf("#@include\s?\([(\'\")]%s[(\'\")]\)#is", $match), $this->getCompiledContents($match), $str);
        }

		foreach ($this->getMatches('/\@includeif\s?\(\s*[\'|\"]([a-z0-9:\-\_\/\.]+)[\'|\"]\s*\)/i', $str) as $match) {
            if ($this->exists($match)) {
                $str = $this->replaceTag(sprintf("#@includeif\s?\([(\'\")]%s[(\'\")]\)#is", $match), $this->getCompiledContents($match), $str);
			} else {
			    $str = $this->replaceTag(sprintf("#@includeif\s?\([(\'\")]%s[(\'\")]\)#is", $match), null, $str);
			}
		}

		foreach ($this->getMatches('/@includeWhen\s?\(\s*(.*?)\s*,\s*[\'\"](.*?)[\'\"]\s*\)/i', $str) as $match) {
            $if = sprintf('<?php if (%s): ?>', $match[0]);
            $endif = '<?php endif; ?>';
            $str = $this->replaceTag(sprintf('#@includeWhen\s?\(\s*%s\s*,\s*[(\'\")]%s[(\'\")]\s*\)#is', str_replace('$', '\$', $match[0]), $match[1]), $if.$this->getCompiledContents($match[1]).$endif, $str);
        }
        /*TODO put includeWhen with 4th param back in
		foreach ($this->getMatches('/@includeWhen\s?\(\s*(.*?)\s*,\s*[\'\"](.*?)[\'\"]\s*,\s*\[(.*?)\]\)/i', $str) as $match) {
            $if = sprintf('<?php if (%s): ?>', $match[0]);
            $extract = sprintf('<?php extract([%s]); ?>', $match[2]);
            $endif = '<?php endif; ?>';
            $str = $this->replaceTag(sprintf("#@includeWhen\s?\(\s*%s\s*,\s*[(\'\")]%s[(\'\")]\)#is", $match[0], $match[1]), $if.$this->getCompiledContents($match[1]).$endif, $str);
        }
        */
        return $str;
    }
    
    /**
     * Removes unused directives so they don't appear in output
     *
     * @param string $str 
     * @return string
     */
    private function cleanup($str)
    {
        $str = preg_replace('/@(yield|stack)\s*\((.*?)\)/', '', $str);
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
     * Add content to the build area for insertion into a parent template
     * when append flag is false content will be replaced,
     * when true it will be appended
     *
     * @param string $name
     * @param array $section
     * @param boolean $append
     * @return void
     */
	private function addBuild($area, $section, $append=false)
	{
        list($name, $str) = $section;
        if ($append) {
            if ( ! isset($this->_build[$area][$name])) {
                $this->_build[$area][$name] = null;
            }
    		$this->_build[$area][$name].= $this->compileString($str);
        } else {
    		$this->_build[$area][$name] = $this->compileString($str);
        }

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
     * @param string $str - the matched content to be replaced
     * @param string $name - the section placeholder name
     * @param string $replace - the content to replace with
     * @return string
     */
	private function replaceSection($str, $name, $replace)
	{
        $matches = [
            sprintf("/\@section\([(\'\")]%s[(\'\")]\)(.*?)\@(stop|show|endsection)/is", $name),
            sprintf("/\@(yield|replace)\([(\'\")]%s[(\'\")]\)/is", $name),
        ];
        foreach ($matches as $expr) {
            // if we have @parent then merge content in, otherwise replace it
            if (stristr($replace, '@parent')) {
                foreach ($this->getMatches($expr, $str) as $match) {
                    // fetch section content from $str and merge with $replace
                    $replace = str_replace('@parent', $match[0], $replace);
                }
            } 
            $str = $this->replaceTag($expr, $replace, $str);
        }

        return $str;

	}

    /**
     * Replaces blade placeholders with their php equivalents
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
			'/{{--\s+(.+?)\s+--}}/i' => '',

			# echo with a default
			'/{{\s+(.+?)\s+or\s+(.+?)\s+}}/i' => '<?php echo (isset($1)) ? htmlentities($1) : $2; ?>',

			# echo an escaped variable, ignoring @{{ var }} for js frameworks
			'/(?<![@]){{\s*(.*?)\s*}}/i' => '<?php echo htmlentities($1); ?>',
            # output for js frameworks
			'/@{{\s*(.*?)\s*}}/i' => '{{ $1 }}',

			# echo an unescaped variable
			'/{!!\s+(.+?)\s+!!}/i' => '<?php echo $1; ?>',

            # variable display mutators, wrap these in htmlentities as necessary
            '/@json\s?\((.*?)\)/i' => '<?php echo json_encode($1); ?>',
            '/@lower\s?\((.*?)\)/i' => '<?php echo htmlentities(strtolower($1)); ?>',
            '/@upper\s?\((.*?)\)/i' => '<?php echo htmlentities(strtoupper($1)); ?>',
            '/@ucfirst\s?\((.*?)\)/i' => '<?php echo htmlentities(ucfirst(strtolower($1))); ?>',
            '/@ucwords\s?\((.*?)\)/i' => '<?php echo htmlentities(ucwords(strtolower($1))); ?>',
            '/@(format|sprintf)\s?\((.*?)\)/i' => '<?php echo htmlentities(sprintf($2)); ?>',


            # wordwrap has multiple parameters
            '/@wrap\s?\((.*?)\)/i' => '<?php echo htmlentities(wordwrap($1)); ?>',
            '/@wrap\s?\((.*?)\s*,\s*(.*?)\)/i' => '<?php echo htmlentities(wordwrap($1, $2)); ?>',
            '/@wrap\s?\((.*?)\s*,\s*(.*?)\s*,\s*(.*?)\)/i' => '<?php echo htmlentities(wordwrap($1, $2, $3)); ?>',

			# set and unset statements
			'/@set\s?\((.*?)\s*\,\s*(.*)\)/i' => '<?php $1 = $2; ?>',
			'/@unset\s?\((.*?)\)/i' => '<?php unset($1); ?>',

            # isset statement
			'/@isset\s?\((.*?)\)/i' => '<?php if (isset($1)): ?>',
			'/@endisset/i' => '<?php endif; ?>',

            # has statement
			'/@has\s?\((.*?)\)/i' => '<?php if (isset($1) && ! empty($1)): ?>',
			'/@endhas/i' => '<?php endif; ?>',

			# handle special unless statement
			'/@unless\s?\((.+?)\)/i' => '<?php if ( ! $1): ?>',
			'/@endunless/i' => '<?php endif; ?>',

            # special empty statement
            '/@empty\s?\((.*?)\)/i' => '<?php if (empty($1)): ?>',
            '/@endempty/i' => '<?php endif; ?>',

            # switch statement
            '/@switch\s?\((.*?)\)/i' => '<?php switch ($1): ?>',
            '/@case\s?\((.*?)\)/i' => '<?php case $1: ?>',
            '/@default/i' => '<?php default: ?>',
            '/@continue\s?\(\s*(.*)\s*\)/i' => '<?php if ($1): continue; endif; ?>',
            '/@continue/i' => '<?php continue; ?>',
            '/@break\s?\(\s*([0-9])\s*\)/i' => '<?php break($1); ?>',
            '/@break\s?\(\s*(.*)\s*\)/i' => '<?php if ($1): break; endif; ?>',
            '/@break/i' => '<?php break; ?>',
            '/@endswitch/i' => '<?php endswitch; ?>',
            
			# handle loops and control structures
			'/@foreach ?\( *(.*?) *as *(.*?) *\)/i' => '<?php $loop->start($1); foreach($1 as $2): ?>',
			'/@endforeach/' => '<?php $loop->increment(); endforeach; $loop->end(); ?>',

			# handle special forelse loop
			'/@forelse\s?\(\s*(\S*)\s*as\s*(\S*)\s*\)(\s*)/i' => "<?php if ( ! empty($1)): \$loop->start($1); foreach ($1 as $2): ?>\n",
			'/@empty/' => "<?php \$loop->increment(); endforeach; \$loop->end(); ?>\n<?php else: ?>",
			'/@endforelse/' => '<?php endif; ?>',

            # each statements
            # eachelse matches first
            '/@each\s?\((.*)\s*,\s*(.*)\s*,\s*[(\'|\")](.*)[(\'|\")],\s*(.*)\s*\)/i' => "<?php if ( ! empty($2)): \$loop->start($2); foreach ($2 as \$$3): ?>\n@include($1)\n<?php \$loop->increment(); endforeach; \$loop->end(); ?>\n<?php else: ?>\n@include($4)\n<?php endif; ?>",
            '/@each\s?\((.*)\s*,\s*(.*)\s*,\s*[(\'|\")](.*)[(\'|\")]\s*\)/i' => "<?php \$loop->start($2); foreach($2 as \$$3): ?>\n@include($1)\n<?php \$loop->increment(); endforeach; \$loop->end(); ?>",

            # control structures
			'/@(if|elseif|for|while)\s*\((.*)\)/i' => '<?php $1 ($2): ?>',
			'/@else/i' => '<?php else: ?>',
			'/@(endif|endfor|endwhile)/' => '<?php $1; ?>',

            # swap out @php and @endphp
			'/@php/i' => '<?php',
			'/@endphp/i' => '?>',

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

        extract($data + ['loop'=>$this->_loop]);
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
        $template_filepath = $this->getTemplateFilepath($template);
        $compiled_filepath = $this->getCompiledFilepath($template);
        if (file_exists($compiled_filepath)) {
            if (filemtime($compiled_filepath) >= filemtime($template_filepath)) {
                return true;
            }
        }
        
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
        //TODO set property and refer to that, reset will remove
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
