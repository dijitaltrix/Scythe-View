@while ($i < 3)
{!! $i !!}
@endwhile
{{-- Expected
<?php while ($i < 3): ?>
<?php echo $i; ?>
<?php endwhile; ?>
--}}