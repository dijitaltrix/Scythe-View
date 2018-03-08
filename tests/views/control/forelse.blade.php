@forelse ($array as $i)
{{ $i }}
@empty
empty
@endforelse
{{-- Expected
<?php if ( ! empty($array)): ?><?php foreach ($array as $i): ?>
<?php echo htmlentities($i); ?>
<?php endforeach; ?>
<?php else: ?>
empty
<?php endif; ?>
--}}