<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>CRM Launcher - @yield('title')</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" media="screen">
        <link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,700,900,600,300,200' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
        <link rel="stylesheet" href="{{ asset("crm-launcher/bower_components/unslider/dist/css/unslider.css") }}" media="screen">
        <link rel="stylesheet" href="{{ asset("crm-launcher/bower_components/unslider/dist/css/unslider-dots.css") }}" media="screen">
        <link rel="stylesheet" href="{{ asset("crm-launcher/bower_components/featherlight/release/featherlight.min.css") }}" media="screen">
        <link rel="stylesheet" href="{{ asset("crm-launcher/bower_components/featherlight/release/featherlight.gallery.min.css") }}" media="screen">
        <link rel="stylesheet" href="{{ asset("crm-launcher/css/styles.min.css") }}" media="screen">
    </head>

    <body>
        <div class="menu-overlay"></div>
        <div class="row">
            <header class="col-xs-12 col-sm-10 col-sm-offset-2">
                <a class="menu-trigger" href="#!"><i class="fa fa-bars responsive-menu visible-xs" aria-hidden="true"></i></a>
                <h1 class="title">
                    @yield('header-title')
                </h1>
            </header>
        </div>

        @section('sidebar')
            <div class="row">
                <nav class="col-sm-2 col-xs-0 vertical-sidebar">
                    <a href="/crm/dashboard"><img class="logo" src="{{ asset("crm-launcher/img/Logo.png") }}" alt="CRM Launcher logo" /></a>
                    <ul>
                        <li class="@if(strpos($_SERVER['REQUEST_URI'], 'dashboard')) active @endif"><a href="/crm/dashboard">Overview</a></li>
                        <li class="cases @if(strpos($_SERVER['REQUEST_URI'], 'publish')) active @endif" ><a href="/crm/publisher">Publisher</a></li>
                        <li class="cases @if(strpos($_SERVER['REQUEST_URI'], 'case')) active @endif" ><a href="/crm/cases">Cases</a></li>
                        <li class="cases @if(strpos($_SERVER['REQUEST_URI'], 'user')) active @endif" ><a href="/crm/users">Team</a></li>
                        <li><a href="#">Settings</a></li>
                    </ul>
                </nav>
            </div>
        @show

        <div class="col-sm-10 col-sm-offset-2 container">

            @if(Session::has('flash_error'))
                <div class="error-message"><span>{!! session('flash_error') !!}</span></div>
            @endif
            @if(Session::has('flash_success'))
                <div class="success-message"><span>{!! session('flash_success') !!}</span></div>
            @endif
            @yield('content')
        </div>

        <script src="{{ asset("crm-launcher/bower_components/jquery/dist/jquery.min.js") }}" charset="utf-8"></script>
        <script src="{{ asset("crm-launcher/bower_components/unslider/dist/js/unslider-min.js") }}" charset="utf-8"></script>
        <script src="{{ asset("crm-launcher/bower_components/featherlight/release/featherlight.min.js") }}" charset="utf-8"></script>
        <script src="{{ asset("crm-launcher/bower_components/featherlight/release/featherlight.gallery.min.js") }}" charset="utf-8"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" charset="utf-8"></script>
        <script src="{{ asset("crm-launcher/js/app.min.js") }}" charset="utf-8"></script>
    </body>
</html>
