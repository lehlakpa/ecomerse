@extends('layouts.app')
@section('title', 'Welcome back')
@section('content')
<div class="auth-layout"><section class="auth-intro"><span class="eyebrow">A LITTLE MORE YOU</span><h1>Your favorites.<br>Your everyday.</h1><p>Sign in and keep discovering the things you love.</p><a href="{{ route('products.index') }}">Explore the collection ?</a></section><section class="panel auth-panel"><span class="eyebrow">WELCOME BACK</span><h2>Make yourself at home.</h2><p class="muted">Sign in to your Ecomerse account.</p><form class="stack" method="post" action="{{ route('login') }}">@csrf<label>Username<input name="username" value="{{ old('username') }}" autocomplete="username" required autofocus></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button>Sign in ?</button></form><p class="fine-print">New here? <a href="{{ route('register') }}">Create an account</a></p></section></div>
@endsection
