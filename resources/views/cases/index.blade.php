@extends('crm-launcher::layouts.master')

@section('title', 'Cases')
@section('header-title', 'Cases')

@section('sidebar')
    @parent
@endsection

@section('content')

    {{-- Filter --}}
    {!! Form::open(array('url' => 'crm/cases/filter', 'method' => 'GET')) !!}
        <div class="row filter">
            <div class="col-xs-6 col-sm-3 my-cases @if((isset($actives) && ! empty($actives) && in_array('my_cases', $actives))) active  @endif">
                {{ trans('crm-launcher::cases.my_cases') }}
                {!! Form::checkbox('cases[]', 'my_cases', (isset($actives) && ! empty($actives) && in_array('my_cases', $actives)) ? 'checked="checked" ' : '' , ['id' => "mine"]) !!}
            </div>
            <div class="col-xs-6 col-sm-3 new-cases @if((isset($actives) && ! empty($actives) && in_array('0', $actives))) active @endif">
                {{ trans('crm-launcher::cases.new_case') }}
                {!! Form::checkbox('cases[]', '0', (isset($actives) && !empty($actives) && in_array('0', $actives)) ? 'checked="checked" ' : '' , ['id' => "new"]) !!}
            </div>
            <div class="col-xs-6 col-sm-3 open-cases @if((isset($actives) && ! empty($actives) && in_array('1', $actives))) active  @endif">
                {{ trans('crm-launcher::cases.open_case') }}
                {!! Form::checkbox('cases[]', '1', (isset($actives) && !empty($actives) && in_array('1', $actives)) ? 'checked="checked" ' : '' , ['id' => "open"]) !!}
            </div>
            <div class="col-xs-6 col-sm-3 closed-cases @if((isset($actives) && ! empty($actives) && in_array('2', $actives))) active  @endif">
                {{ trans('crm-launcher::cases.closed_case') }}
                {!! Form::checkbox('cases[]', '2', (isset($actives) && !empty($actives) && in_array('2', $actives)) ? 'checked="checked" ' : '' , ['id' => "closed"]) !!}
            </div>
        </div>

    <div class="row search">

        @if (isset($searchResult) && $searchResult['keyword'] != "" && (! $searchResult['bool'] || count($cases) < 1))
            <h3>No results where found for: {{$searchResult['keyword']}}</h3>
        @endif

        <div class="col-xs-12 search-block">
                {!! Form::text('keywords', null, ['placeholder' => 'Search case by id, name, keyword, date or social media ', 'class' => 'search-case-bar']) !!}
        </div>
    </div>
    {!! Form::close() !!}

    {{-- Cases overview --}}
    <div class="row cases-overview">

        @foreach($cases as $key => $case)
            <a href="/crm/case/{{$case->id}}">
                <div class="col-xs-12 col-sm-6 col-md-4 case-block">
                    <div class="@if(strpos($case->origin, 'Twitter') !== false) twitter @else facebook @endif message">
                        <i class="fa @if(strpos($case->origin, 'Twitter') !== false) fa-twitter @else fa-facebook @endif" aria-hidden="true"></i>
                        <div class="profile-picture visible-lg">
                            <img src="{{getOriginalImg($case->contact->profile_picture)}}" alt="" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 profile-info">
                            <h2>{{$case->contact->name}}</h2>
                            <h3>
                                @if(strpos($case->origin, 'Twitter') !== false)
                                    &commat;{{$case->contact->twitter_handle}} -
                                    <span class="time-ago">{{ $case->updated_at->diffForHumans() }}</span>
                                @else
                                    <span class="time-ago">{{ $case->updated_at->diffForHumans() }}</span>
                                @endif
                            </h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 last-message">
                            {{ str_limit($case->messages->sortByDesc('id')->first()->message , $limit = 139, $end = '...') }}
                        </div>
                    </div>
                </div>
            </a>
        @endforeach

        <div class="pagination-centered">
            {!! $cases->appends(Request::all())->render() !!}
        </div>
    </div>
@endsection
