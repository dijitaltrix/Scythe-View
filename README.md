# Scythe View

_pronounced [sahyth]_

A simple implementation of Laravel Blade for rendering blade syntax in views into a PSR-7 Response object. 
This has no dependencies and it works great with Slim Framework 3.

This implementation does not aim for feature parity, that is beyond the scope of this project.
It aims to be a lightweight, simple and fast wrapper around PHP.
It does offer some useful methods and the ability to add custom directives through callbacks.
If you need full compatibility you can use a package such as [philo/laravel-blade](https://packagist.org/packages/philo/laravel-blade).

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
    return new \Ignition\Scythe([
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

$view = new \Ignition\Scythe([
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
This is just a wrapper around the handy sprintf function, you can also use @sprintf!
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
Not yet implemented

### yield
```html
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
Extend Scythe with custom callbacks, to be implemented

<!-- You can add your own custom commands using the addCommand method -->

## Stacks
To be implemented



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
### @inject
This will probably not be implemented I feel it's too easy to abuse and it's not good practice to allow the view to pull in whatever it likes. 
Data and dependencies should be generated in the 'Domain' and injected into the view so the view should already have eveyrthing it needs.
As the template renders down to plain php you are of course able to execute in any code you like.

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
