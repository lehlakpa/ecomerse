<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Everyday essentials') ? Ecomerse</title>
    <meta name="description" content="Discover your everyday essentials at Ecomerse. Browse the collection and order in a few simple steps.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="announcement">Good finds. Everyday essentials.</div>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ route('home') }}"><span class="brand-icon" aria-hidden="true">e.</span> ecomerse<span class="brand-dot">.</span></a>
        <button class="menu-toggle secondary" type="button" aria-controls="navigation" aria-expanded="false">Menu <span aria-hidden="true">?</span></button>
        <nav id="navigation" class="navigation" aria-label="Main navigation">
            <a class="{{ request()->routeIs('home', 'products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Shop collection</a>
            @auth
                @if(auth()->user()->isAdmin())
                    <a class="{{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Admin workspace</a>
                @endif
                <span class="muted">Hi, {{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="post">@csrf<button class="secondary small">Sign out</button></form>
            @else
                <a href="{{ route('login') }}">Sign in</a>
                <a class="button small" href="{{ route('register') }}">Create account <span aria-hidden="true">?</span></a>
            @endauth
        </nav>
    </div>
</header>
<main id="main" class="container main-content">
    @if(request()->routeIs('admin.*'))
        <nav class="admin-tabs" aria-label="Admin navigation">
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Overview</a>
            <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">Products</a>
            <a class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">Orders</a>
        </nav>
    @endif
    @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert error" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
<footer class="container site-footer"><a class="brand" href="{{ route('home') }}">ecomerse.</a><p>Considered essentials. Simply yours.</p><span>? {{ date('Y') }} Ecomerse</span></footer>
</body>
</html>
