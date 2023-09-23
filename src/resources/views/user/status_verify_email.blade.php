@extends('layouts.master')
@section('content')
<div class="col-6 mx-auto" style="margin-top: 100px"></div>
    @if($status == true)
    <div class="alert alert-success text-center" role="alert">
        <i class="fa-solid fa-check-double"></i> <span class="ml-2">Your email has been verified !</span> 
    </div>
    @else 
    <div class="alert alert-warning text-center" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i> <span class="ml-2">Token has expired !</span>
    </div>
    @endif
    <div style="display: flex;justify-content: center;">
        <a href="http://localhost:3000/"><button type="button" class="btn btn-outline-primary"><i class="fa-solid fa-house mr-2"></i>Home</button></a>
    </div>
</div>
@endsection
