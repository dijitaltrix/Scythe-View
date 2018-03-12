@forelse ($array as $i)
{{ $i }}
@empty
empty
@endforelse
{{-- Expected
<?php if ( ! empty($array)): $loop->start($array); foreach ($array as $i): ?>
<?php echo htmlentities($i); ?>
<?php $loop->increment(); endforeach; $loop->end(); ?>
<?php else: ?>
empty
<?php endif; ?>
--}}