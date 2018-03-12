@each('path/to/template', $muppets, 'muppet', 'path/to/no-results')
@each ("path/to/template", $muppets, "muppet", "path/to/no-results")
{{-- Expected
<?php if ( ! empty($muppets)): $loop->start($muppets); foreach ($muppets as $muppet): ?>
@include('path/to/template')
<?php $loop->increment(); endforeach; $loop->end(); ?>
<?php else: ?>
@include('path/to/no-results')
<?php endif; ?>
<?php if ( ! empty($muppets)): $loop->start($muppets); foreach ($muppets as $muppet): ?>
@include("path/to/template")
<?php $loop->increment(); endforeach; $loop->end(); ?>
<?php else: ?>
@include("path/to/no-results")
<?php endif; ?>
--}}