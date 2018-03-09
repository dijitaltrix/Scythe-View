{{ $name or 'Anonymous' }}
{{-- Expected
<?php echo (isset($name)) ? htmlentities($name) : 'Anonymous'; ?>
--}}