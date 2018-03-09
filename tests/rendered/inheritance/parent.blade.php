<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
</head>
<body>
    <div class="main">
        <h1>@yield('title')</h1>
        <p>This is part of the parent template</p>
    </div>
    <div class="side">
        @section('sidebar')
        This is the parent part of the sidebar
        @show
    </div>
</body>
</html>