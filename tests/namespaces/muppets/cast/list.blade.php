<h1>Muppets cast list</h1>
<ul>
@foreach ($muppets as $muppet)
    <li>{{ $muppet['name'] }}</li>
@endforeach
</ul>