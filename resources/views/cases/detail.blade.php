@extends('crm-launcher::layouts.master')

@section('title', 'Case detail')
@section('header-title', 'Case detail')

@section('sidebar')
    @parent
@endsection

@section('content')
    {{-- Basic info about customer --}}
    <div class="row case-detail">
        <div class=" col-xs-12 col-md-7 conversation">
            {{-- Message bubbles --}}
            @foreach($case->messages->sortBy('post_date') as $key => $message)
                <div class="message">
                    <div class="hidden-xs col-sm-2 col-md-3 thumb-picture">
                        @if ($message->contact()->exists())
                            <img src="{{ getOriginalImg($message->contact->profile_picture) }}" alt="" />
                        @else
                            <img src="{{ getOriginalImg($case->contact->profile_picture) }}" alt="" />
                        @endif
                    </div>
                    <div class="col-xs-12 col-sm-10 col-md-9 bubble">
                        <h4 class="contact-name">{{ $message->contact->name }}</h4>
                        <div class="reply-inner">
                            <span class="post-date">{{ date('d F, H:i', strtotime($message->post_date)) }}</span>
                            @if ($key != 0 && $case->origin == "Facebook post")
                                <a class="reply" answerTrigger="{{$key}}" replyId="{{$message->fb_post_id}}" href="#!">
                                    <i class="fa fa-reply" aria-hidden="true"></i>
                                </a>
                            @elseif ($key != 0 &&  $case->origin == "Twitter mention")
                                <a class="reply" answerTrigger="{{$key}}" replyId="{{$message->tweet_id}}" screenName="{{$message->contact->twitter_handle}}" href="#!">
                                    <i class="fa fa-reply" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                        @if (count($message->media))
                            <div class="msg">
                                {{ $message->message }}
                            </div>
                            @foreach($message->media as $nr => $pic)
                                <div class="media-item">
                                    <a class="gallery" href="#" data-featherlight="{{$pic->url}}">
                                        <img style="background-image: url({{$pic->url}});" src="" alt="" />
                                    </a>
                                </div>
                            @endforeach
                        @else
                            {{ $message->message }}
                        @endif
                    </div>
                </div>


                @if ($case->origin == 'Facebook post' && count($message->innerComment) && $key != 0)
                    <div id="{{$key}}" class="inner-comments">
                        @foreach($message->innerComment as $nr => $comment)
                            <div class="inner message">
                                <div class="hidden-xs col-xs-2 col-md-3 thumb-picture">
                                    @if ($comment->contact()->exists())
                                        <img src="{{ getOriginalImg($comment->contact->profile_picture) }}" alt="" />
                                    @elseif(strpos($case->origin, 'Twitter') !== false) && $case->contact()->exists())
                                        <img src="{{ getOriginalImg($case->contact->profile_picture) }}" alt="" />
                                    @else
                                        <img src="{{asset("crm-launcher/img/profile_picture.png")}}" alt="" />
                                    @endif
                                </div>
                                @if(! $comment->contact_id)
                                    <div class="col-xs-10 col-md-9 bubble answer">
                                        <h4 class="contact-name">{{ $comment->user->name }}</h4>
                                        {{ $comment->message }}
                                        <div class="delete-answer">
                                            <a href="/crm/case/{{$case->id}}/inner/{{$comment->id}}">
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-xs-10 col-md-9 bubble">
                                        <h4 class="contact-name">{{ $comment->contact->name }}</h4>
                                        {{ $comment->message }}
                                        @if (count($comment->media))
                                            @foreach($comment->media as $nr => $pic)
                                                <div class="media-item">
                                                    <a class="gallery" href="#" data-featherlight="{{$pic->url}}">
                                                        <img style="background-image: url({{$pic->url}});" src="" alt="" />
                                                    </a>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($case->origin == "Facebook post" && $key != 0)
                        <div class="row answer_block answer_{{$key}}">
                            <div class="col-xs-9 col-xs-offset-3 answer_specific inner">
                                {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\CasesController@replyPost', $case->id), 'class' => 'form_' . $key)) !!}
                                    {!! Form::hidden('in_reply_to', '') !!}
                                    {!! Form::textarea('answer_specific', null, ['placeholder' => 'Enter your answer', 'class' => 'specific_answer ' .$key , 'rows' => 2, 'cols' => 40]) !!}
                                {!! Form::close() !!}
                            </div>
                        </div>
                    @endif

                    <div toggle-nr='{{$key}}' class="more">Comments <i class="fa fa-caret-down" aria-hidden="true"></i></div>
                @endif

                @foreach($message->answers as $nr => $answer)
                    <div class="message">
                        <div class="hidden-xs col-sm-2 col-md-3 thumb-picture">
                            <img src="{{asset("crm-launcher/img/profile_picture.png")}}" alt="" />
                        </div>
                        <div class="col-xs-12 col-sm-10  col-md-9 bubble answer" operatorId="{{$answer->user->id}}">
                            <div class="delete-answer">
                                @if($case->origin == "Facebook post")
                                    <a class="reply_own"  answerTrigger="{{$nr}}" replyId="{{$answer->fb_post_id}}" href="#!">
                                        <i class="fa fa-reply" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if(strpos($case->origin, 'Twitter') !== false)
                                    <a href="/crm/case/{{$case->id}}/tweet/{{$answer->id}}">
                                @else
                                    <a href="/crm/case/{{$case->id}}/post/{{$answer->id}}">
                                @endif
                                @if($case->origin != "Facebook private")
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                @endif
                                </a>

                                <span class="post-date">{{ date('d F, H:i', strtotime($answer->post_date)) }}</span>
                            </div>

                            <h4 class="contact-name">{{ $answer->user->name }}</h4>
                            {{ $answer->answer }}
                        </div>
                        <div class="answer-post-date col-xs-offset-2 col-md-offset-3 col-xs-10 col-md-9">
                            <div class="post-date">{{ date('d F, H:i', strtotime($answer->post_date)) }}</div>
                        </div>

                        @if($case->origin == "Facebook post")
                            <div class="row answer_block answer_own_{{$nr}}">
                                <div class="col-xs-9 col-xs-offset-3 answer_specific">
                                    {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\CasesController@replyPost', $case->id), 'class' => 'form_' . $key)) !!}
                                        {!! Form::hidden('in_reply_to', '') !!}
                                        {!! Form::textarea('answer_specific', null, ['placeholder' => 'Enter your answer', 'class' => 'specific_answer ' .$key , 'rows' => 2, 'cols' => 40]) !!}
                                    {!! Form::close() !!}
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($case->origin == 'Facebook post' && count($answer->innerComment))
                        <div id="answer{{$nr}}" class="inner-comments-answers">
                            @foreach($answer->innerComment as $comment)
                                <div class="inner message">
                                    <div class="hidden-xs col-xs-2 col-md-3 thumb-picture">
                                        @if ($comment->contact()->exists())
                                            <img src="{{ getOriginalImg($comment->contact->profile_picture) }}" alt="" />
                                        @elseif(strpos($case->origin, 'Twitter') !== false) && $case->contact()->exists())
                                            <img src="{{ getOriginalImg($case->contact->profile_picture) }}" alt="" />
                                        @else
                                            <img src="{{asset("crm-launcher/img/profile_picture.png")}}" alt="" />
                                        @endif
                                    </div>
                                    @if(! $comment->contact_id)
                                        <div class="col-xs-10 col-md-9 bubble answer">
                                            <h4 class="contact-name">{{ $comment->user->name }}</h4>
                                            {{ $comment->message }}
                                            <div class="delete-answer">
                                                <a href="/crm/case/{{$case->id}}/inner/{{$comment->id}}">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-xs-10 col-md-9 bubble">
                                            <h4 class="contact-name">{{ $comment->contact->name }}</h4>
                                            {{ $comment->message }}
                                            @if (count($comment->media))
                                                @foreach($comment->media as $nr => $pic)
                                                    <div class="media-item">
                                                        <a class="gallery" href="#" data-featherlight="{{$pic->url}}">
                                                            <img style="background-image: url({{$pic->url}});" src="" alt="" />
                                                        </a>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div toggle-nr-answer='{{$nr}}' class="more-answer">Comments <i class="fa fa-caret-down" aria-hidden="true"></i></div>
                    @endif
                @endforeach
            @endforeach

            @if($case->status != '2')
                @if($case->origin == "Facebook post")
                    {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\CasesController@replyPost', $case->id))) !!}
                @elseif ($case->origin == "Facebook private")
                    {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\CasesController@replyPrivate', $case->id))) !!}
                @else
                    {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\CasesController@replyTweet', $case->id))) !!}
                @endif
                    <div class="col-lg-12 answer-textarea static-parent">
                        @if($case->origin == 'Twitter mention')
                            <div class="word-count">{{ strlen($handle) + 2}}/140</div>
                            {!! Form::hidden('in_reply_to', '') !!}
                            {!! Form::textarea('answer', '@' . $handle . ' ' , ['placeholder' => 'Write your message..', 'rows' => 4, 'cols' => 40, 'class' => 'maxed']) !!}
                        @else
                            {!! Form::textarea('answer', null, ['placeholder' => 'Write your message..', 'rows' => 4, 'cols' => 40]) !!}
                        @endif
                    </div>

                    <div class="col-lg-12 submit">
                        {!! Form::submit('Send message') !!}
                    </div>
                {!! Form::close() !!}
            @endif
        </div>

        <div class="col-xs-12 col-md-4 tiles">

            <div class="col-xs-6 open-since tile">
                @if($case->origin == 'Twitter mention')
                    <i class="fa fa-twitter" aria-hidden="true"></i>
                    Mention
                @elseif($case->origin == 'Twitter direct')
                    <i class="fa fa-twitter" aria-hidden="true"></i>
                    Message
                @elseif($case->origin == 'Facebook post')
                    <i class="fa fa-facebook" aria-hidden="true"></i>
                    Post
                @elseif($case->origin == 'Facebook private')
                    <i class="fa fa-facebook" aria-hidden="true"></i>
                    Message
                @endif
            </div>
            <div class="col-xs-6 open-since tile">
                @if($case->origin == 'Twitter mention' || $case->origin == 'Facebook post')
                    <i class="fa fa-unlock-alt" aria-hidden="true"></i>
                    Public
                @elseif($case->origin == 'Twitter direct' || $case->origin == 'Facebook private')
                    <i class="fa fa-lock" aria-hidden="true"></i>
                    Private
                @endif
            </div>

            <div class="col-xs-6 case-state tile @if ($case->status == 0) new @elseif ($case->status == 1) open @else closed  @endif">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                @if ($case->status == 0)
                    {{ trans('crm-launcher::cases.new_case') }}
                @elseif ($case->status == 1)
                    {{ trans('crm-launcher::cases.open_case') }}
                @else
                    {{ trans('crm-launcher::cases.closed_case') }}
                @endif
            </div>
            <div class="col-xs-6 open-since tile">
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                {{ date('d F Y, H:i', strtotime($case->messages->first()->post_date)) }}
            </div>

            @if($case->status != 0)
                <a href="/crm/case/{{$case->id}}/close">
                    <div class="col-xs-6 toggle-state @if($case->status == 2) re-open @endif tile">

                            @if($case->status == 1)
                                <i class="fa fa-check" aria-hidden="true"></i>
                                {{ trans('crm-launcher::cases.close_case') }}
                            @elseif($case->status == 2)
                                <i class="fa fa-repeat" aria-hidden="true"></i>
                                {{ trans('crm-launcher::cases.reopen_case') }}
                            @endif
                    </div>
                </a>
            @endif

            @if(strpos($case->origin, 'Twitter') !== false)
                <a href="/crm/case/{{$case->id}}/follow">
                    <div class="col-xs-6 toggle-follow tile">
                            @if($case->contact->following)
                                <i class="fa fa-minus" aria-hidden="true"></i>
                                {{ trans('crm-launcher::cases.unfollow') }}
                            @else
                                <i class="fa fa-plus" aria-hidden="true"></i>
                                {{ trans('crm-launcher::cases.follow') }}
                            @endif
                    </div>
                </a>
            @endif
        </div>

        {{-- Case summary --}}
        <div class="col-xs-12 col-md-4 summary">

            <div class="col-xs-12">
                <h2>{{ trans('crm-launcher::cases.case_summary') }}</h2>
                <h3>{{ trans('crm-launcher::cases.origin') }}</h3>
                @if(strpos($case->origin, 'Twitter') !== false)
                    <span>Twitter</span>
                @else
                    <span>Facebook</span>
                @endif
            </div>

            <div class="col-xs-12 isPublic">
                This is a

                @if($case->origin == 'Twitter mention')
                    <span>public</span> tweet.
                @elseif($case->origin == 'Twitter direct')
                    <span>private</span> tweet.
                @elseif($case->origin == 'Facebook post')
                    <span>public</span> post
                @elseif($case->origin == 'Facebook private')
                    <span>private</span> post.

                @endif
            </div>

            <div class="col-xs-12 co-operators">
                <h3>{{ trans('crm-launcher::cases.co_operators') }}</h3>
                <ul>
                    @foreach($case->users as $key => $user)
                        <li>{!! Form::label('operator_' . $user->id, $user->name) !!}</li>
                        {!! Form::checkbox('operator[]', 'value', false, ['class' => 'operator', 'id' => 'operator_' . $user->id]) !!}
                    @endforeach
                    @if(! count($case->users))
                        {{ trans('crm-launcher::cases.no_co_ops') }}
                    @endif
                </ul>
            </div>

            <div class="col-xs-12 summary-block">
                <h3>{{ trans('crm-launcher::cases.summary') }}</h3>

                @foreach($summaries->take(1) as $key => $summary)
                    <div class="summary-area visible">
                        <h4>
                            Posted on {{ date('d F, H:i', strtotime($summary->created_at)) }}, by <span>{{$summary->user->name}}</span>
                            @if(Auth::user()->name == $summary->user->name)
                                <span class="delete-summary">
                                    <a href="/crm/case/{{$case->id}}/summary/{{$summary->id}}/delete">
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </a>
                                </span>
                            @endif
                        </h4>
                        {{ $summary->summary }}
                    </div>
                @endforeach

                <div class="hidden-summaries">
                    @foreach($summaries as $key => $summary)

                        @if($key != (count($summaries) - 1))
                            <div class="summary-area">
                                <h4>
                                    Posted on {{ date('d F, H:i', strtotime($summary->created_at)) }}, by <span>{{$summary->user->name}}</span>
                                    @if(Auth::user()->name == $summary->user->name)
                                        <span class="delete-summary">
                                            <a href="/crm/case/{{$case->id}}/summary/{{$summary->id}}/delete">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </a>
                                        </span>
                                    @endif
                                </h4>
                                {{ $summary->summary }}
                            </div>
                        @endif
                    @endforeach
                </div>

                @if(! count($summaries))
                    {{ trans('crm-launcher::cases.no_summary') }}
                @endif
            </div>

            <div class="col-xs-12">
                <a href="#!">
                    <div class="@if(count($case->summaries) > 1) col-xs-12 col-sm-6 col-md-12 col-lg-6 @else col-xs-12 empty @endif sum add-summary"> <span>Add summary</span> </div>
                </a>
                @if(count($case->summaries) > 1)
                    <a href="#!">
                        <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6 sum more-summaries"> <span>More summaries</span> </div>
                    </a>
                @endif
            </div>

            <div class="col-xs-12 add-summary-block">
                <h3>Add new summary</h3>
                {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\SummaryController@addSummary', $case->id))) !!}
                    <div class="col-lg-12 summary-textarea">
                        {!! Form::textarea('summary',null,['placeholder' => 'Enter your summary', 'rows' => 4, 'cols' => 40]) !!}
                    </div>

                    <div class="col-lg-12 submit">
                        {!! Form::submit('Save summary') !!}
                    </div>
                {!! Form::close() !!}
            </div>
        </div>


    </div>
@endsection
