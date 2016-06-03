@extends('crm-launcher::layouts.master')

@section('title', 'Overview')
@section('header-title', 'Overview')

@section('sidebar')
    @parent
@endsection

@section('content')

    {{-- First block of statistics --}}
    <div class="row first-block">
        <div class="col-md-6 col-lg-4 open-cases">
            <div class="case-type">{{ trans('crm-launcher::cases.open_cases') }}</div>
            <div class="number">{{ count($openCases) }}</div>
        </div>
        <div class="col-md-6 col-lg-4 new-cases">
            <div class="case-type">{{ trans('crm-launcher::cases.new_cases') }}</div>
            <div class="number">{{ count($newCases) }}</div>
        </div>
        <div class=" col-md-12 col-lg-4 closed-cases">
            <div class="case-type">{{ trans('crm-launcher::cases.closed_cases') }}</div>
            <div class="number">{{ count($closedCases) }}</div>
        </div>
    </div>

    {{-- Second block of statistics --}}
    <div class="row second-block">
        <div class="col-md-6 col-lg-3 waiting-time">
            <div class="average-title">Avg. waiting</div>
            <div class="number">{{ $avgWaitTime }}</div>
            <div class="extra">minutes</div>
        </div>
        <div class="col-md-6 col-lg-3 messages">
            <div class="average-title">Avg. messages</div>
            <div class="number">{{ $avgMessages }}</div>
            <div class="extra">per case</div>
        </div>
        <div class="col-md-6 col-lg-3 helpers">
            <div class="average-title">Avg. helpers</div>
            <div class="number">{{ $avgHelpers }}</div>
            <div class="extra">per case</div>
        </div>
        <div class="col-md-6 col-lg-3 new-cases">
            <div class="average-title">Answers</div>
            <div class="number">{{ $todaysMessages }}</div>
            <div class="extra">today</div>
        </div>
    </div>

    {{-- Third block: social media --}}
    <div class="row third-block">
        @if (isTwitterLinked())
            <div class="@if(isTwitterLinked() && ! isFacebookLinked()) col-md-12 @else col-md-6 @endif twitter">
                <i class="fa fa-twitter circle"></i> <span class="tweets-count">{{ $followers }} @if($followers == 1)follower @else followers @endif</span>
            </div>
        @endif
        @if (isFacebookLinked())
            <div class="@if(!isTwitterLinked() && isFacebookLinked()) col-md-12 @else col-md-6 @endif facebook">
                <i class="fa fa-facebook circle"></i> <span class="posts-count">{{ $likes }} @if($likes == 1)like @else likes @endif</span>
            </div>
        @endif
    </div>

@endsection
