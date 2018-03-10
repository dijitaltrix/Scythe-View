@for ($i = 0; $i < 10; $i++)
    @continue
    @continue($jessica == 'Rabbitt')
    {!! $i !!}
    @break ($rabbitt == 'Roger')
    @break(2)
    @break
@endfor
{{-- Expected
<?php for ($i = 0; $i < 10; $i++): ?>
    <?php continue; ?>
    <?php if ($jessica == 'Rabbitt'): continue; endif; ?>
    <?php echo $i; ?>
    <?php if ($rabbitt == 'Roger'): break; endif; ?>
    <?php break(2); ?>
    <?php break; ?>
<?php endfor; ?>
--}}