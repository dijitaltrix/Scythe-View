@for ($i = 0; $i < 10; $i++)
{!! $i !!}
@endfor
{{-- Expected
<?php for ($i = 0; $i < 10; $i++): ?>
<?php echo $i; ?>
<?php endfor; ?>
--}}