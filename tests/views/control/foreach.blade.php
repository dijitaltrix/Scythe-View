@foreach ($array as $i)
{!! $i !!}
@endforeach
{{-- Expected
<?php foreach ($array as $i): ?>
<?php echo $i; ?>
<?php endforeach; ?>
--}}