@extends('crm-launcher::layouts.master')

@section('title', 'Add user')
@section('header-title', 'Add user')

@section('sidebar')
    @parent
@endsection

@section('content')

    @if (count($errors) > 0)
        <div class="error-message"><span>{{ $errors->first() }}</span></div>
    @endif

    <div class="row">
        <div class="col-md-6 col-md-offset-3 add-user">
            {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\UsersController@addUser'))) !!}
                <div class="col-xs-12">
                    {!! Form::label('name', 'Name') !!}
                </div>
                <div class="col-xs-12">
                    {!! Form::text('name') !!}
                </div>

                <div class="col-xs-12">
                    {!! Form::label('email', 'Email') !!}
                </div>
                <div class="col-xs-12">
                    {!! Form::text('email') !!}
                </div>

                <div class="col-xs-12">
                    {!! Form::label('password', 'Password') !!}
                </div>
                <div class="col-xs-12">
                    {!! Form::password('password') !!}
                </div>

                <div class="col-xs-12 submit">
                    {!! Form::submit('Create user') !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>

@endsection
