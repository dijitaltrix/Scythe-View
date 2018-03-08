@unless ($muppet == 'Miss Piggy')
<p>Muppet is not Miss Piggy</p>
@endunless
{{-- Expected
<?php if ( ! $muppet == 'Miss Piggy'): ?>
<p>Muppet is not Miss Piggy</p>
<?php endif; ?>
--}}