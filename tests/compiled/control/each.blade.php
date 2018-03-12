@each ('path/to/template', $muppets, 'muppet')
{{-- Expected
<?php $loop->start($muppets); foreach($muppets as $muppet): ?>
@include('path/to/template')
<?php $loop->increment(); endforeach; $loop->end(); ?>
--}}