@isset($muppet)
<p>Muppet is set</p>
@endisset
{{-- Expected
<?php if (isset($muppet)): ?>
<p>Muppet is set</p>
<?php endif; ?>
--}}