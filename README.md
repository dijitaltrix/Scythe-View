# Scythe View

_pronounced [sahyth] - Olde English word for a curved blade for cutting by hand_

[![Build Status](https://travis-ci.org/dijitaltrix/Scythe-View.svg?branch=master)](https://travis-ci.org/dijitaltrix/Scythe-View)

A simple implementation of Laravel Blade for rendering blade syntax in views into a PSR-7 Response object. 
This has no dependencies and it works great with Slim Framework 3.

If you're unfamiliar with Blade as a view renderer it's main advantage is it's *lightness*, it is essentially a simple wrapper around PHP.
The syntax behaves as a PHP programmer would expect it to, so for those familar with PHP there is very little mental juggling required to understand Blade.

This implementation does not aim for feature parity, that is beyond the scope of this project.
It does offer some useful methods and the ability to add custom directives through callbacks.
If you need full compatibility you can use the full Laravel Blade package via [philo/laravel-blade](https://packagist.org/packages/philo/laravel-blade).

**Note:** Currently unsupported features `@verbatim` `@component` `@hasSection` `@auth`* `@guest`*

*These could be added using a directive callback to suit your framework

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
    * [addDirective](#addDirective\($placeholder,_$callback\))
    * [addNamespace](#addNamespace\($name,_$path\))
    * [exists](#exists\($template\))
    * [renderString](#renderString\($string,_$data=[]\))
* [To do or not to do](#to_do_or_not_to_do)

# Installation

Install with [Composer](http://getcomposer.org):

```sh
composer require dijitaltrix/scythe-view
```

## Getting started

### Use with Slim 3

Add Scythe to your container, passing the required parameters `views_path` and `cache_path` 

```php
$container['view'] = function($c) {
    return new \Dijitaltrix\Views\Scythe([
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

$view = new \Dijitaltrix\Views\Scythe([
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
```php
{{ $name }}
// <?php echo htmlentities($name); ?>
```

### echo raw
Use this with caution when displaying user generated data
```php
{!! $name !!}
// <?php echo $name; ?>
```

### echo with a default
Displays 'Anonymous' if $name is not set
```php
{{ $name or 'Anonymous' }}
// <?php (isset($name)) ? htmlentities($name) : 'Anonymous'; ?>
```

### set
Sets the value of a variable
```php
@set($muppet, 'Kermit')
// <?php $muppet = 'Kermit'; ?>
```

### unset
Removes a variable from the scope
```php
@unset($muppet)
// <?php unset($muppet); ?>
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
This is just a wrapper around the sprintf function, to help your muscle memory you may also call it as @sprintf
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
A more compact version of the foreach loop that adds the @include command
```php
@each('muppets/profile', $muppets, 'muppet')

// @foreach ($muppets as $muppet)
//   @include('muppets/profile')
// @endforeach
```
It also supports the generation of the forelse structure if you pass a view to display when there are no records
```php
@each('muppets/profile', $muppets, 'muppet', 'muppets/profile-missing')

// @forelse ($muppets as $muppet)
//   @include('muppets/profile')
// @empty
//   @include('muppets/profile-missing')
// @endforelse

```

#### Loop variable
A loop helper is available, this works identically to the Blade $loop helper

**Property**       | **Description**   
-------------------|----------------                                        
$loop->index	   | The index of the current loop iteration (starts at 0).    
$loop->iteration   | The current loop iteration (starts at 1).                 
$loop->remaining   | The iteration remaining in the loop.                      
$loop->count	   | The total number of items in the array being iterated.    
$loop->first	   | Whether this is the first iteration through the loop.     
$loop->last	       | Whether this is the last iteration through the loop.      
$loop->depth	   | The nesting level of the current loop.                    
$loop->parent*	   | When in a nested loop, the parent's loop variable.        

*Not implemented yet

## Inheritance

### extends
Calls a parent template to insert sections into
```html
@extends('muppets/cast/list')
```

### section
Sections define areas of content to be inserted into a parent template
```html
@section('title', 'Muppets cast listing')

@section('head')
<h1>Muppets cast listing</h1>
@endsection
```

#### parent
Sections can be merged between templates using the @parent directive.
```html
<!-- parent.blade.php -->
@section('sidebar')
    <h3>This is the sidebar content</h3>
@show

<!-- child.blade.php -->
@section('sidebar')
    @parent
    <ul>
        <li>This is a list item</li>
        <li>And so is this</li>
        <li>Another item in the list</li>
    </ul>
@endsection
```

Will display as

```html
<h3>This is the sidebar content</h3>
<ul>
    <li>This is a list item</li>
    <li>And so is this</li>
    <li>Another item in the list</li>
</ul>
```


### replace or yield
Replaced with the contents of the child template section definition
```html
<title>@replace('title')</title>
<!-- OR -->
<title>@yield('title')</title>
<!-- <title>Muppets cast listing</title> -->
```

### include
Include another blade template, all variables will be available to the included template
```php
@section('body')
<ul>
@foreach ($muppets as $muppet)
    @include('muppets/cast/member')
@endforeach
</ul>
@endsection
```


## Directives
Define your own placeholders that insert content via callbacks
```php
// output a string
$view->addDirective('/@hello/i', 'Hello world');

// output the result of a callback
$view->addDirective('/@hello/i', function(){
    return 'Hello world';
});
//TODO pass parameters from directive to callback
``` 

<!-- You can add your own custom commands using the addCommand method -->

## Stacks
Push to stacks from your child templates, then they will be appended to the @stack directive in your parent template 
```php
// child.blade.php
@extends('parent')

@section('title', 'The title')

@push('head')
<link rel="stylesheet" href="/css/child.css" type="text/css" media="screen">
@endpush

@push('scripts')
<script src="child.js"></script>
<script type="text/javascript">
    alert('Some alert');
</script>
@endpush

// parent.blade.php
<html>
<head>
    @stack('head')
</head>
<body>
    @yield('body')
    @stack('scripts')
</body>
</html>
    

```


# Methods

In addition to the Blade syntax, the renderer instance offers the following methods

### addDirective($placeholder, $callback)

```php
$view->addDirective('@rand', function() {
    if (rand(1,10) > 5) {
        throw new Exception("This is pointless");
    }
});

```

### addNamespace($name, $path)
Namespaces allow you to add view paths outside the default views_path. 
Use them for external packages or to help organise your code into modules. 

```php
// add the namespace name first, followed by the path to the root of the namespace
$view->addNamespace("admin", "/src/Admin/views");

// namespaces are referenced with '::'
// for example
$view->exists('admin::user/dashboard');

// this will load the file at src/Admin/views/user/profile.blade.php
$view->render($response, 'admin::user/profile', $args);

```

### exists($template)
The `exists()` function will return `true` if the template file is in the view path, or `false` if not. 
```php
$view->exists('admin/dashboard')

// Namespaces are specified as follows
$view->exists('admin::user/dashboard')
```

### renderString($string, $data=[])
The `renderString()` function will parse a string converting any blade commands . 
```php
$blade = "<h1>{{ $title }}</h1>";

$view->renderString($blade, ['title'=>'The Muppet Show']);

// <h1>The Muppet Show</h1>

```


# To do or not to do
### Components and slots
No plans to introduce this, use @include as an alternative.

### @inject
No plans to implement this I feel it's not good practice to allow the view to pull in whatever data classes etc.. it likes. 
Data and dependencies should be generated in the 'Domain' and injected into the view Response so the view should already have everything it needs.

As the template renders down to plain php you are of course able to execute in any code you like between PHP tags.

### Custom if statements
It is possible to add similar functionality through custom Directives
