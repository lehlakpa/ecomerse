@extends('admin.layouts.app')
@section('title', 'Edit product')
@section('content')
<div class="section-heading"><div><span class="eyebrow">REFINE YOUR COLLECTION</span><h1>Edit product</h1></div><a class="back-link" href="{{ route('admin.products.index') }}">? All products</a></div>
<form class="panel product-editor stack" action="{{ route('admin.products.update', $product) }}" method="post" enctype="multipart/form-data">@csrf @method('PUT') @include('admin.products.form')<div class="actions"><button>Save changes ?</button><a class="button secondary" href="{{ route('admin.products.index') }}">Cancel</a></div></form>
@endsection
