@has($muppet->name)
<p>Displays muppet name</p>
@endhas
{{-- Expected
<?php if (isset($muppet->name) && ! empty($muppet->name)): ?>
<p>Displays muppet name</p>
<?php endif; ?>
--}}