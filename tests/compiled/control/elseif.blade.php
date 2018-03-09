@if ($kermit != 'frog')
false
@elseif ($kermit == 'frog')
true
@endif
{{-- Expected
<?php if ($kermit != 'frog'): ?>
false
<?php elseif ($kermit == 'frog'): ?>
true
<?php endif; ?>
--}}