
# Scythe View

_pronounced [sahyth]_

A clean implementation of Laravel Blade syntax for rendering blade views into a PSR-7 Response object. 
It has no dependencies, fits into one smallish file and it works great with Slim Framework 3.


While this covers 80% or more of the Laravel Blade syntax it does not aim for feature parity. 



# Installation

Install with [Composer](http://getcomposer.org):

    composer require ignition/scythe-view


## Use with Slim 3

Add Scythe to your container:

```php
$container['view'] = function($c) {
    return new \Slim\Views\Scythe([
        'views_path' => 'path/to/views',
        'cache_path' => 'path/to/cache',
    ]);
}
```

And use it as follows:

```php
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->view->render($response, "hello.blade.php", $args);
});
```

It is planned to add custom directives and namespaces through the constructor soon.

## Use with any PSR-7 Project
```php

$view = new \Slim\Views\Scythe([
    'views_path' => 'path/to/views',
    'cache_path' => 'path/to/cache',
]);

$output = $view->render(new Response(), "/path/to/template.blade.php", $data);

```



# Documentation

## Comments
```php
{{-- Upon rendering all this will be removed --}}
```

## Variables
### echo
`{{ $name }}`

### echo unescaped
**Use this with due caution when displaying user generated data**

`{!! $name !!}`


### set
```php
@set($muppet, 'Kermit')
{{ $muppet }}
<!-- Kermit -->
```

### unset
```php
@unset($muppet)
{{ $muppet or 'Where did he go?' }}
<!-- Where did he go? -->
```

### isset

```php
@isset($muppet)
<p>He is here</p>
@endisset
```

## Variable manipulation
Twig has some really nice string modifiers, we have these ;) 

### upper
```php
@upper($name)
<!-- KERMIT -->
```

### lower
```php
@lower($name)
<!-- kermit -->
```

### ucfirst
```php
@ucfirst($name)
<!-- Kermit -->
```

### ucwords
```php
@ucwords($name)
<!-- Kermit The Frog -->
```

### wrap
```php
@wrap("This is a really long line that should wrap somewhere otherwise it may break the internet into pieces!")
<!-- 
This is a really long line that should wrap somewhere otherwise it may
break the internet into pieces!
-->

<!-- With an optional limit -->
@wrap("This is a really long line that should wrap somewhere otherwise it may break the internet into pieces!", 25)

<!--
This is a really long
line that should wrap 
somewhere otherwise
it may break the
internet into pieces!
-->
```


## Control structures
### if
```php
@if (true)
<p>This will always show</p>
@endif
```

```php
@if (true)
<p>This will always show</p>
@else
<p>This will never show</p>
@endif
```

```php
@if ($i == 1)
<p>i is equal to one</p>
@elseif ($i == 2)
<p>i is equal to two</p>
@else
<p>i is something else entirely</p>
@endif
```

### unless
```php
@unless($bank > 1000000)
<p>Keep on working</p>
@endunless
```

### switch
```php
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
**The loop statements @continue and @break are not yet implemented**

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


## Other useful stuff

### php 
`@php` becomes `<?php`

`@endphp` becomes `?>`

### each
To be implemented

### Directives
To be implemented

Extend Scythe with custom callbacks

<!-- You can add your own custom commands using the addCommand method -->

### Stacks
To be implemented




### Features available in Laravel Blade but not here
#### @inject
This will probably not be implemented. It's not good practice to allow the view to pull in whatever it likes. 
Data and dependencies should be generated in the 'Domain' and injected into the view so the view should already have eveyrthing it needs.
#### The loop variable
It is possible to get the loop variable working for a single loop, however it would not work in nested loops.
#### Custom if statements
This is not planned but it will be possible to add similar functionality through custom Directives

<!-- ### includeif
To be implemented

### includeWhen
To be implemented

### includeFirst
To be implemented -->