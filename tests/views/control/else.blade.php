@if ($kermit != 'frog')
false
@else
true
@endif
{{-- Expected
<?php if ($kermit != 'frog'): ?>
false
<?php else: ?>
true
<?php endif; ?>
--}}