@each ('path/to/template', $muppets, 'muppet')
{{-- Expected
<?php foreach ($muppets as $muppet): ?>
@include('path/to/template')
<?php endforeach; ?>
--}}