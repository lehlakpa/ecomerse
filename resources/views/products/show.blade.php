@extends('layouts.app')
@section('title', $product->title)
@section('content')
<a class="back-link" href="{{ route('products.index') }}">&larr; Back to collection</a>
<div class="detail-grid">
    <section aria-label="Product images">
        <div class="detail-image">@if($product->images->first())<img id="main-product-image" src="{{ $product->images->first()->image_url }}" alt="{{ $product->title }}">@else<span class="placeholder-mark" aria-hidden="true">e.</span>@endif</div>
        @if($product->images->count() > 1)<div class="thumbnails">@foreach($product->images as $image)<button type="button" class="thumbnail" data-image="{{ $image->image_url }}" aria-label="View image {{ $loop->iteration }}"><img src="{{ $image->image_url }}" alt="{{ $product->title }}, image {{ $loop->iteration }}"></button>@endforeach</div>@endif
    </section>
    <section class="product-details"><span class="eyebrow">YOUR NEXT EVERYDAY FAVORITE</span><h1>{{ $product->title }}</h1>@if($product->rating > 0)<p class="rating">&#9733; {{ $product->rating }} / 5</p>@endif<p class="price">Rs. {{ number_format($product->price, 2) }}</p><p class="description">{{ $product->description }}</p>
        @if(auth()->user()?->isAdmin())
            <div class="panel admin-product-preview stack">
                <span class="eyebrow">ADMIN PREVIEW</span>
                <h2>Product details</h2>
                <p class="muted">Review how this item appears in your collection, or edit its details below.</p>
                <dl class="variant-summary">
                    <div><dt>Available sizes</dt><dd>{{ implode(', ', $product->sizes ?? []) ?: 'One size' }}</dd></div>
                    <div><dt>Available colors</dt><dd>{{ implode(', ', $product->colors ?? []) ?: 'Standard' }}</dd></div>
                </dl>
                <a class="button" href="{{ route('admin.products.edit', $product) }}">Edit product <span aria-hidden="true">&rarr;</span></a>
                <a class="preview-back" href="{{ route('admin.products.index') }}">Manage collection</a>
            </div>
        @else
        <form class="stack order-form" method="post" action="{{ route('orders.store', $product) }}" data-price="{{ $product->price }}">@csrf
            <div class="form-grid">
                @foreach(['size' => $product->sizes, 'color' => $product->colors] as $field => $options)
                    @if(count($options ?? []))<label>{{ ucfirst($field) }}<select name="{{ $field }}" required><option value="">Select {{ $field }}</option>@foreach($options as $option)<option value="{{ $option }}" @selected(old($field) === $option)>{{ $option }}</option>@endforeach</select></label>@endif
                @endforeach
                <label>Quantity<input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="20" required></label>
            </div>
            <div class="form-divider"><h2>Delivery details</h2><p class="muted">No account needed. We will contact you to confirm your order.</p></div>
            <div class="form-grid"><label>Full name<input name="name" autocomplete="name" maxlength="100" value="{{ old('name', auth()->user()?->name) }}" required></label><label>Phone number<input type="tel" name="phone" autocomplete="tel" maxlength="20" value="{{ old('phone', auth()->user()?->phone) }}" required></label></div>
            <label>Delivery address<textarea name="location" autocomplete="street-address" maxlength="255" rows="2" required placeholder="Street, area, city">{{ old('location') }}</textarea></label>
            <div class="order-total"><span>Order total</span><strong data-order-total>Rs. {{ number_format($product->price * (is_numeric(old('quantity', 1)) ? min(20, max(1, old('quantity', 1))) : 1), 2) }}</strong></div>
            <button type="submit">Place order <span aria-hidden="true">&rarr;</span></button><p class="fine-print">This places an order request. No online payment is collected.</p>
        </form>
        @endif
    </section>
</div>
@endsection
