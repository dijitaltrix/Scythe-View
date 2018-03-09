# Scythe View

_pronounced [sahyth]_

A simple implementation of Laravel Blade syntax for rendering blade views into a PSR-7 Response object. 
No dependencies, works great with Slim Framework 3.

This implementation does not aim for feature parity, that is beyond the scope of this project.
If you need more you can use a complete Blade package such as [philo/laravel-blade](https://packagist.org/packages/philo/laravel-blade).
It does offer some useful methods and the ability to add custom directives through callbacks.

If you're unfamiliar with Blade as a view renderer it's main advantage is it's *lightness*, it is essentially a simple wrapper around PHP.
The syntax behaves as a PHP programmer would expect it to, so for those familar with PHP there is very little mental juggling required to understand Blade.

### Table of Contents  
* [Installation](#installation)
* [Getting Started](#getting_started)
* [Language Reference](#language_reference)
    * [Comments](#comments)
    * [Variables](#variables)
    * [Variable manipulation](#variable_manipulation)
    * [Control Structures](#control_structures)
    * [Loops](#loops)
    * [Inheritance](#inheritance)
    * [Directives](#directives)
    * [Stacks](#stacks)
* [Methods](#methods)
* [To do or not to do](#to_do_or_not_to_do)

# Installation

Install with [Composer](http://getcomposer.org):

```sh
composer require ignition/scythe-view
```

## Getting started

### Use with Slim 3

Add Scythe to your container, passing the required parameters `views_path` and `cache_path` 

```php
$container['view'] = function($c) {
    return new \Slim\Views\Scythe([
        'views_path' => 'path/to/views',
        'cache_path' => 'path/to/cache',
    ]);
}
```

And assuming you set views_path as `app/src/views` you may use it as follows:

```php
// app/routes.php
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->view->render($response, "hello", $args);
});

// app/src/views/hello.blade.php
<h1>Hello {{ $name }}</h1>
```

You may add namespaces and directives using the methods outlined in the Methods section below.

### Use with any PSR-7 Project
```php

$view = new \Slim\Views\Scythe([
    'views_path' => 'path/to/views',
    'cache_path' => 'path/to/cache',
]);

$response = $view->render(new Response(), "/path/to/template", $data);

```


# Language Reference

## Comments
```html
{{-- Upon rendering all this will be removed --}}
```

Opening and closing php tags can be used follows
`@php` `@endphp` 


## Variables
All variable display functions will escape your output using htmlentities() except for echo raw: `{!! $danger !!}`
### echo
Displays the contents of $name
```
{{ $name }}
```

### echo raw
Use this with caution when displaying user generated data
```
{!! $name !!}
```

### echo with a default
Displays 'Anonymous' if $name is not set
```
{{ $name or 'Anonymous' }}
```

### set
Sets the value of a variable
```html
@set($muppet, 'Kermit')
{{ $muppet }}
<!-- Kermit -->
```

### unset
Removes a variable from the scope
```html
@unset($muppet)
{{ $muppet or 'Where did he go?' }}
<!-- Where did he go? -->
```

### isset
Checks whether a variable is set 
```html
@isset($muppet)
<p>He is here</p>
@endisset
```

### has
Checks whether a variable is set and has a value 
```html
@has($muppet->name)
<p>Muppet name is set and is not empty</p>
@endhas
```

## Variable manipulation
Twig has some really nice string modifiers, we have these ;) All examples below are wrapped with htmlentities()

### upper
Converts the string to uppercase
```html
@upper($name)
<!-- KERMIT -->
```

### lower
Converts the string to lowercase
```html
@lower($name)
<!-- kermit -->
```

### ucfirst
Converts the string to lowercase, then capitalises the first word
```html
@ucfirst($name)
<!-- Kermit -->
```

### ucwords
Converts the string to lowercase, then capitalises each word
```html
@ucwords($name)
<!-- Kermit The Frog -->
```

### format
This is just a wrapper around the handy sprintf function
```html
@format("There were %s in the %s", "dogs", "yard")
<!-- There were dogs in the yard -->
```


### wrap
```html
@wrap("This is a really long line that should wrap somewhere otherwise it may shatter the internet into pieces!")
<!-- 
This is a really long line that should wrap somewhere otherwise it may
shatter the internet into pieces!
-->

<!-- With an optional limit -->
@wrap("This is a really long line that should wrap somewhere otherwise it may shatter the internet into pieces!", 25)

<!--
This is a really long
line that should wrap 
somewhere otherwise
it may shatter the
internet into pieces!
-->
```


## Control structures
### if
Scythe supports all possible combinations of if structures
```html
@if (true)
<p>This will always show</p>
@endif
```

```html
@if (true)
<p>This will always show</p>
@else
<p>This will never show</p>
@endif
```

```html
@if ($i == 1)
<p>i is equal to one</p>
@elseif ($i == 2)
<p>i is equal to two</p>
@else
<p>i is something else entirely</p>
@endif
```

### unless
If NOT something, then what? 
```html
@unless($bank > 1000000)
<p>Keep on working</p>
@endunless
```

### switch
```html
@switch($i)
    @case(1)
        <p>i is equal to one</p>
        @break
    @case(2)
        <p>i is equal to two</p>
        @break
    @default
        <p>i is something else entirely</p>
@endswitch
```

## Loops
*The loop statements @continue and @break are not yet implemented*

### for
```php
@for ($i = 0; $i < 11; $i++)
    Currently reading {{ $i }}
@endfor
```

### foreach
```php
@foreach ($muppets as $muppet)
    <p>Say howdy to {{ $muppet->name }}</p>
@endforeach
```

### forelse
```php
@forelse ($muppets as $muppet)
    <p>Say howdy to {{ $muppet->name }}</p>
@empty
    <p>Sadly no muppets can be with us today</p>
@endforelse
```

### while
```php
@while (true)
    <p>One Infinite Loop</p>
@endwhile
```

### each
To be implemented


## Inheritance

### extend
Not yet implemented

### section
Not yet implemented

#### parent
Not yet implemented (yawn)

### yield
Not yet implemented

### include
Not yet implemented


## Directives
Extend Scythe with custom callbacks, to be implemented

<!-- You can add your own custom commands using the addCommand method -->

## Stacks
To be implemented



# Methods

In addition to the Blade syntax, the renderer instance offers the following methods

### Check that a template file exists in the view path
The `exists()` function will return `true` if the template file is in the view path, or `false` if not. 
```php
$this->view->exists('admin/dashboard')

// Namespaces are specified as follows
$this->view->exists('admin::user/dashboard')
```

### Add namespaces
Add the namespace definition to the constructor, or elsewhere in your app using the `addNamespace` method.
It takes two arguments, the first is the namespace, the second is the root path of the namespace.

```php
// add the namespace name first, followed by the path to the root of the namespace
$this->view->addNamespace("admin", "/src/Admin/views");

// namespaces are referenced with '::'
// for example
$this->view->exists('admin::user/dashboard');

// this will load the file at src/Admin/views/user/profile.blade.php
$this->view->render($response, 'admin::user/profile', $args);

```


addDirective($placeholder, $callback)



# To do or not to do
### @inject
This will probably not be implemented. It's not good practice to allow the view to pull in whatever it likes. 
Data and dependencies should be generated in the 'Domain' and injected into the view so the view should already have eveyrthing it needs.

### The loop variable
It is possible to get the loop variable working for a single loop, however it would would be shared in nested loops and strange results would ensue.

### Custom if statements
This is not planned but it will be possible to add similar functionality through custom Directives

<!-- ### includeif
To be implemented

### includeWhen
To be implemented

### includeFirst
To be implemented -->
