@empty ($muppets)
<p>We have no muppets</p>
@endempty

@empty($muppets)
<p>We have no muppets</p>
@endempty
{{-- Expected
<?php if (empty($muppets)): ?>
<p>We have no muppets</p>
<?php endif; ?>

<?php if (empty($muppets)): ?>
<p>We have no muppets</p>
<?php endif; ?>
--}}