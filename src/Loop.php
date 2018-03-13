<?php
/**
 * This class is a helper for Scythe loop constructs
 *
 * @author Ian Grindley
 */

    
namespace Dijitaltrix\Views;

use Exception;

class Loop {

    private static $_stack = [];
    
    
    public static function start($items)
    {
        array_unshift(self::$_stack, [
            'count' => count($items),
            'index' => 0,
        ]);
    }
    
    // remove the top item in the stack
    public static function end()
    {
        array_shift(self::$_stack);
    }
    
    public static function increment()
    {
        self::$_stack[0]['index']++;
    }
    
    public static function index()
    {
        return self::stack(0)->index; 
    }
    
    public static function iteration()
    {
        return 1 + self::stack(0)->index; 
    }
    
    public static function first()
    {
        return (self::stack(0)->index === 0); 
    }
    
    public static function last()
    {
        return ((1 + self::stack(0)->index) === self::stack(0)->count); 
    }
    
    public static function remaining()
    {
        return self::stack(0)->count - (1 + self::stack(0)->index); 
    }
    
    public static function depth()
    {
        return count(self::$_stack); 
    }
    
    public static function count()
    {
        return self::stack(0)->count; 
    }
    
    public static function parent()
    {
        throw new Exception("Not implemented");
    }
    
    public static function stack($index)
    {
        if (isset(self::$_stack[$index])) {
            return (object) self::$_stack[$index];
        }
        
        throw new Exception("Attempt to call non existant loop");
        
    }
    
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            return self::$name();
        }
        
        throw new Exception("Call to unknown method or property '$name'");
        
    }
    
}