@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Verify Your Email Address') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    {{ __('Before proceeding, please check your email for a verification code.') }}
                    {{ __('If you did not receive the email') }},
                    <form class="d-inline" method="POST" action="{{ url('verify-otp') }}">
                        @csrf
                        <input id="verify" type="text" class="form-control" name="otp" placeholder="Enter the OTP sent to your Email Address" required><br>
                        <input id="email" type="hidden" class="form-control" name="email" value="{{$user}}" required>
                        <button type="submit" class="btn btn-primary p-2 m-0 align-baseline">{{ __('Verify') }}</button><br><br>
                    </form>
                    
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <input id="email" type="hidden" class="form-control" name="email" value="{{$user}}" required>
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
