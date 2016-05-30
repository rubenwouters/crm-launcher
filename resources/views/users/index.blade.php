@extends('crm-launcher::layouts.master')

@section('title', 'Team')
@section('header-title', 'Team')

@section('sidebar')
    @parent
@endsection

@section('content')

    <div class="user-management">
        <div class="row">
            <div class="col-xs-12 search-block">
                {!! Form::open(array('method' => 'GET', 'action' => array('\Rubenwouters\CrmLauncher\Controllers\UsersController@searchUser'))) !!}
                    {!! Form::text('keywords', null, ['placeholder' => 'Search users by name or e-mail', 'class' => 'search-bar']) !!}
                {!! Form::close() !!}
            </div>
        </div>

        @if (isset($keywords))
            <h3>Search results by: <span class="keyword"><a href="/crm/users">{{$keywords}}</a></span></h3>
        @endif

        <div class="row user-blocks">

            @if(count($team) > 0)
                <h3>Your team</h3>
                <div class="row">
                    @foreach($team as $key => $user)
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 columns user-block @if($user->canViewCRM) canViewCRM @endif" userId="{{$key}}">
                            <h2 class="initials">{{ ucfirst(substr(explode(' ', $user->name)[0], 0, 1)) }}@if(isset(explode(' ', $user->name)[1])){{ ucfirst(substr(explode(' ', $user->name)[1], 0, 1))}}@endif</h2>
                            <div class="actions">
                                <h3>{{ $user->name }}</h3>
                            </div>

                            {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\UsersController@toggleUser', $user->id))) !!}
                                <input name="user" id="{{$key}}" class="cbUser" type="checkbox" @if($user->canViewCRM) checked="checked" @endif>
                            {!! Form::close() !!}
                        </div>
                    @endforeach
                    <a href="/crm/user/add">
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 columns user-block addUser canViewCRM">
                            <h2 class="initials">
                                <i class="fa fa-user-plus" aria-hidden="true"></i>
                                <div>Add user</div>
                            </h2>
                        </div>
                    </a>
                </div>

                <div class="pagination-centered">
                    {!! $team->appends(Request::all())->links() !!}
                </div>
            @endif



            @if(count($otherUsers) > 0 && ! empty($keywords))
                <h3>Other users</h3>
                <div class="row">
                    @foreach($otherUsers as $key => $user)
                        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 columns user-block @if($user->canViewCRM) canViewCRM @endif" userId="{{$key}}">
                            <h2 class="initials">{{ ucfirst(substr(explode(' ', $user->name)[0], 0, 1)) }}@if(isset(explode(' ', $user->name)[1])){{ ucfirst(substr(explode(' ', $user->name)[1], 0, 1))}}@endif</h2>
                            <div class="actions">
                                <h3>{{ $user->name }}</h3>
                            </div>

                            {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\UsersController@toggleUser', $user->id))) !!}
                                <input name="user" id="{{$key}}" class="cbUser" type="checkbox" @if($user->canViewCRM) checked="checked" @endif>
                            {!! Form::close() !!}
                        </div>
                    @endforeach
                </div>

                <div class="pagination-centered">
                    {!! $otherUsers->appends(Request::all())->links() !!}
                </div>
            @endif
        </div>


    </div>

@endsection
