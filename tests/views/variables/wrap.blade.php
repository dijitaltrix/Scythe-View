@wrap($str)
@wrap ($str, 80)
{{-- Expected
<?php echo htmlentities(wordwrap($str)); ?>
<?php echo htmlentities(wordwrap($str, 80)); ?>
--}}