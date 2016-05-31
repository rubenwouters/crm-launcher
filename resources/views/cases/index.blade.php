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

                <div class="col-xs-10 col-sm-6 col-md-4 col-lg-3 case-block">
                    <div class=" col-xs-12 profile-picture" style="background-image:url({{getOriginalImg($case->contact->profile_picture)}});background-size:cover;background-repeat:no-repeat;height: 300px;-webkit-filter:grayscale(50%);)"></div>
                    <div class=" col-md-12 profile-info">
                        <div class="row">
                            <div class="col-xs-11">
                                <h2>{{$case->contact->name}}</h2>
                                @if(strpos($case->origin, 'Twitter') !== false)
                                    <h3>&commat;{{$case->contact->twitter_handle}}</h3>
                                @endif
                            </div>
                            <div class="col-xs-1 case-id">
                                <h2>#{{$case->id}}</h2>
                            </div>
                        </div>

                        @if($case->status != 0)
                            <div class="row">
                                <div class="col-xs-12 helping">
                                    @if($case->latest_helper)
                                        {{ trans('crm-launcher::cases.last_helped') }}: {{ $case->latest_helper }}
                                    @endif

                                </div>
                                <div class="col-xs-12 last-answer">{{ trans('crm-launcher::cases.last_answer') }} {{ date(' d/m/Y, H:i', strtotime($case->updated_at)) }}u</div>
                            </div>
                        @endif
                    </div>
                </div>


                {{-- <div class="col-xs-10 col-sm-6 col-md-4 col-lg-6 case-block">
                    <div class=" col-md-12 col-lg-3 profile-picture">
                        <img src="{{ getOriginalImg($case->contact->profile_picture) }}" alt="" />
                    </div>
                    <div class=" col-md-12 col-lg-9 profile-info">
                        <div class="row">
                            <div class="col-xs-8">
                                <h2>{{$case->contact->name}}</h2>
                                @if(strpos($case->origin, 'Twitter') !== false)
                                    <h3>&commat;{{$case->contact->twitter_handle}}</h3>
                                @endif
                            </div>
                            <div class="col-xs-4 case-id">
                                <h2>#{{$case->id}}</h2>
                            </div>
                        </div>

                        @if($case->status != 0)
                            <div class="row">
                                <div class="col-xs-12 helping">
                                    @if($case->latest_helper)
                                        {{ trans('crm-launcher::cases.last_helped') }}: {{ $case->latest_helper }}
                                    @endif

                                </div>
                                <div class="col-xs-12 last-answer">{{ trans('crm-launcher::cases.last_answer') }} {{ date(' d/m/Y, H:i', strtotime($case->updated_at)) }}u</div>
                            </div>
                        @endif
                    </div>
                </div> --}}

            </a>
        @endforeach

        <div class="pagination-centered">
            {!! $cases->appends(Request::all())->render() !!}
        </div>
    </div>
@endsection
