<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
</head>
<body>
    <div class="main">
        <h1>@yield('title')</h1>
        @yield('main')
    </div>
    <div class="side">
        @yield('sidebar')
    </div>
</body>
</html>