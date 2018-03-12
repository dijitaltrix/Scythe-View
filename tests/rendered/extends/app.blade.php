<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
    @stack('head')
</head>
<body>
    <div class="main">
        <h1>@yield('title')</h1>
        @replace('main')
    </div>
    <div class="side">
        @yield('sidebar')
        <div class="static">
        @section('static')
            <h3>Some content pulled from child</h3>
        @show
        </div>
    </div>
    @stack('scripts')
</body>
</html>