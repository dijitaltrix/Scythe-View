@foreach ($array as $i)
{!! $i !!}
@endforeach
{{-- Expected
<?php $loop->start($array); foreach($array as $i): ?>
<?php echo $i; ?>
<?php $loop->increment(); endforeach; $loop->end(); ?>
--}}