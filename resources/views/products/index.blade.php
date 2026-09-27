@extends('layouts.app')
@section('title', 'Shop the collection')
@section('content')
@if(auth()->user()?->isAdmin())
<section class="catalog-heading">
    <div><span class="eyebrow">YOUR STOREFRONT</span><h1>A collection worth<br>coming back to.</h1><p>Preview your products and keep every detail up to date.</p></div>
    <a class="button" href="{{ route('admin.products.create') }}">+ Add product</a>
</section>
@else
<section class="hero">
    <div class="hero-copy"><span class="eyebrow">THE EVERYDAY COLLECTION</span><h1>Little finds.<br>Big possibilities<span>.</span></h1><p>Make room for your next favorite. Explore essentials that fit your life and your style.</p><a class="button" href="#collection">Explore the collection <span aria-hidden="true">&rarr;</span></a><div class="hero-note"><span class="tiny-dot"></span> Browse. Choose. Make it yours.</div></div>
    <div class="hero-art" aria-hidden="true"><div class="art-orbit"></div><div class="art-label">CURATED FOR<br><strong>your everyday.</strong></div><div class="shopping-bag"><span>e.</span></div><div class="art-tag">A little refresh</div><div class="art-caption">THE ART OF EVERYDAY LIVING</div></div>
</section>
<div class="benefits"><span><b>01</b> Discover your favorites</span><span><b>02</b> Pick your size & color</span><span><b>03</b> Order in a few simple steps</span></div>
@endif
<section id="collection" class="collection">
    <div class="section-heading"><div><span class="eyebrow">FIND SOMETHING YOU LOVE</span><h2>The collection<span class="count">{{ $products->total() }}</span></h2></div><form class="search-form" method="get" action="{{ route('products.index') }}"><label class="sr-only" for="search">Search products</label><input id="search" name="search" type="search" placeholder="Search the collection..." value="{{ $search }}" maxlength="100"><button type="submit">Search</button></form></div>
    @if($search)<p class="muted">Results for &ldquo;{{ $search }}&rdquo; &middot; <a href="{{ route('products.index') }}">Clear search</a></p>@endif
    <div class="product-grid">
        @forelse($products as $product)
            <article class="product-card">
                <a class="product-image" href="{{ route('products.show', $product) }}" aria-label="View {{ $product->title }}">
                    @if($product->images->first())<img src="{{ $product->images->first()->image_url }}" alt="{{ $product->title }}" loading="lazy">@else<span class="placeholder-mark" aria-hidden="true">e.</span>@endif
                    <span class="view-product" aria-hidden="true">&rarr;</span>
                </a>
                <div class="product-meta"><span class="eyebrow">EVERYDAY ESSENTIAL</span>@if($product->rating > 0)<span class="rating">&#9733; {{ $product->rating }}</span>@endif</div>
                <h3><a href="{{ route('products.show', $product) }}">{{ $product->title }}</a></h3>
                <div class="product-bottom"><strong>Rs. {{ number_format($product->price, 2) }}</strong><span class="muted">{{ count($product->colors ?? []) ? count($product->colors).' colors' : 'Discover more' }}</span></div>
            </article>
        @empty
            <div class="empty-state"><span class="empty-icon" aria-hidden="true">&rarr;</span><h3>{{ $search ? 'No matches just yet' : 'Something good is on its way' }}</h3><p>{{ $search ? 'Try another product name or a shorter search.' : 'Our collection is being prepared. Check back soon for your next favorite.' }}</p>@if($search)<a class="button secondary" href="{{ route('products.index') }}">View all products</a>@endif</div>
        @endforelse
    </div>
    <div class="pagination">{{ $products->links() }}</div>
</section>
@endsection
