@each('path/to/template', $muppets, 'muppet', 'path/to/no-results')
{{-- Expected
<?php if ( ! empty($muppets)): ?><?php foreach ($muppets as $muppet): ?>
@include('path/to/template')
<?php endforeach; ?>
<?php else: ?>
@include('path/to/no-results')
<?php endif; ?>
--}}