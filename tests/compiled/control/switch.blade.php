@switch ($i)
@case ('abc')
<p>It's abc</p>
@break
@case ('def')
<p>It's def</p>
@break
@default
<p>It's something else</p>
@break
@endswitch
{{-- Expected
<?php switch ($i): ?>
<?php case 'abc': ?>
<p>It's abc</p>
<?php break; ?>
<?php case 'def': ?>
<p>It's def</p>
<?php break; ?>
<?php default: ?>
<p>It's something else</p>
<?php break; ?>
<?php endswitch; ?>
--}}