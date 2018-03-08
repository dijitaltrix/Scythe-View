@if ($kermit == 'frog')
true
@endif
{{-- Expected
<?php if ($kermit == 'frog'): ?>
true
<?php endif; ?>
--}}