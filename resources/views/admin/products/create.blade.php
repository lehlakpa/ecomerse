@extends('admin.layouts.app')
@section('title', 'Add product')
@section('content')
<div class="section-heading"><div><span class="eyebrow">GROW YOUR COLLECTION</span><h1>Add product</h1></div><a class="back-link" href="{{ route('admin.products.index') }}">? All products</a></div>
<form class="panel product-editor stack" action="{{ route('admin.products.store') }}" method="post" enctype="multipart/form-data">@csrf @include('admin.products.form')<div class="actions"><button>Create product ?</button><a class="button secondary" href="{{ route('admin.products.index') }}">Cancel</a></div></form>
@endsection
