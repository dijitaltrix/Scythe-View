@if ($kermit == 'frog')
true
@endif
@if ($kermit != "frog")
false
@endif
@if (isset($kermit))
false
@endif
{{-- Expected
<?php if ($kermit == 'frog'): ?>
true
<?php endif; ?>
<?php if ($kermit != "frog"): ?>
false
<?php endif; ?>
<?php if (isset($kermit)): ?>
false
<?php endif; ?>
--}}