@extends('crm-launcher::layouts.master')

@section('title', 'Facebook permissions')
@section('header-title', 'Facebook permission')

@section('sidebar')
    @parent
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 ask-permission">
            @if ($filledOut)
                <h2>{{ trans('crm-launcher::dashboard.not_there_yet') }}</h2>
                <h3>{{ trans('crm-launcher::dashboard.facebook_login') }}</h3>

                <a href="/facebook"><img class="login-btn" src="{{asset("crm-launcher/img/fb_login.png")}}" alt="" /></a>
            @else
                <div class="error-message"><span>{{ trans('crm-launcher::dashboard.env_file_empty') }}</span></div>
            @endif
        </div>
    </div>
@endsection
