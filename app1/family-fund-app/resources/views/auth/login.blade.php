@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="card-group">
    <div class="card p-4">
        <div class="card-body">
            <form method="post" action="{{ url('/login') }}">
                @csrf
                <h1>Login</h1>
                <p class="text-muted">Sign In to your account</p>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="icon-user"></i>
                        </span>
                    </div>
                    <input type="email" class="form-control {{ $errors->has('email')?'is-invalid':'' }}" name="email" value="{{ old('email') }}" placeholder="Email">
                    @if ($errors->has('email'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="icon-lock"></i>
                        </span>
                    </div>
                    <input type="password" class="form-control {{ $errors->has('password')?'is-invalid':'' }}" placeholder="Password" name="password">
                    @if ($errors->has('password'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="row">
                    <div class="col-6">
                        <button class="btn btn-primary px-4" type="submit">Login</button>
                    </div>
                    <div class="col-6 text-right">
                        <a class="btn btn-link px-0" href="{{ url('/password/reset') }}">
                            Forgot password?
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card text-white bg-primary py-5 d-md-down-none" style="width:44%">
        <div class="card-body text-center">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="Family Fund" class="mb-3 rounded-circle" style="width: 80px; height: 80px; object-fit: cover; box-shadow: 0 8px 16px rgba(0,0,0,0.2);">
                <h2>Sign up</h2>
                <p>Join Family Fund to manage your family's financial future with confidence.</p>
                <a class="btn btn-primary active mt-3" href="{{ url('/register') }}">Register Now!</a>
            </div>
        </div>
    </div>
</div>
@endsection
