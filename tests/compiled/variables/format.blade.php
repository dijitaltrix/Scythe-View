@format("This %s", 'String')
@format("%d %s %s", 99, 'red', 'ballons')
{{-- Expected
<?php echo htmlentities(sprintf("This %s", 'String')); ?>
<?php echo htmlentities(sprintf("%d %s %s", 99, 'red', 'ballons')); ?>
--}}