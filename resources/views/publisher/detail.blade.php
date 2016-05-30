@extends('crm-launcher::layouts.master')

@section('title', 'Detail publishment')
@section('header-title', 'Detail publishment')

@section('sidebar')
    @parent
@endsection

@section('content')

    <div class="container">

        <div class="row publishments detail">
            <div class="col-md-6 col-md-offset-3">
                <div class="date">{{ date('l d M Y', strtotime($publishment->created_at)) }}</div>
                <div class="col-md-12 published-content">
                    {{ rawurldecode($publishment->content) }}
                    <div class="col-xs-12 icons">
                        @if($publishment->tweet_id != '')
                            <div class="@if($publishment->fb_post_id == '')  col-xs-6  @else col-xs-3 col-lg-3 @endif">
                                {{ $publishment->twitter_likes }} <span class="icon"><i class="fa fa-heart" aria-hidden="true"></i></span>
                            </div>
                            <div class="@if($publishment->fb_post_id == '') col-xs-6  @else col-xs-3 @endif">
                                {{ $publishment->twitter_retweets }} <span class="icon"><i alt='Retweets' class="fa fa-retweet" aria-hidden="true"></i></span>
                            </div>
                        @endif
                        @if($publishment->fb_post_id != '')
                            <div class="@if($publishment->tweet_id == '')  col-xs-6  @else col-xs-3 col-lg-3 @endif">
                                {{ $publishment->facebook_likes }} <span class="icon"><i class="fa fa-thumbs-up" aria-hidden="true"></i></span>
                            </div>
                            <div class="@if($publishment->tweet_id == '') col-xs-6 @else col-xs-3 @endif">
                                {{ $publishment->facebook_shares }} <span class="icon"><i class="fa fa-share" aria-hidden="true"></i></span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-6 conversation">
                @if(count($tweets) > 0)
                    @foreach($tweets as $key => $message)

                        <div class="message">
                            <div class="col-xs-12 col-md-12 bubble @if($message->user_id == 0) answer @endif">
                                @if (count($message->media))
                                    @foreach($message->media as $nr => $pic)
                                        <div class="media-item">
                                            {{ $message->message }}
                                            <a class="gallery" href="#" data-featherlight="{{$pic->url}}">
                                                <img style="background-image: url({{$pic->url}});" src="" alt="" />
                                            </a>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="reply-inner">
                                        <span class="post-date">{{ date(' d/m/Y, H:i', strtotime($message->post_date)) }}</span>
                                        <a class="reply {{$key}}" answerTrigger="{{$key}}" replyId="{{$message->tweet_id}}" href="#!">
                                            <i class="fa fa-reply" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                    <h4 class="screen-name {{$key}}">{{$message->screen_name}}</h4>
                                    {{ $message->message }}
                                @endif
                            </div>
                        </div>
                        <div class="row answer_block answer_{{$key}}">
                            <div class="col-xs-12 answer_specific">
                                {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\PublishController@replyTweet', $publishment->id), 'class' => 'form_' . $key)) !!}
                                    {!! Form::hidden('in_reply_to', '') !!}
                                    {!! Form::textarea('answer', null, ['placeholder' => 'Enter your answer', 'class' => 'specific_answer fb ' . $key , 'rows' => 2, 'cols' => 40]) !!}
                                {!! Form::close() !!}
                            </div>
                        </div>
                    @endforeach

                    {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\PublishController@replyTweet', $publishment->id))) !!}
                        <div class="col-lg-12 answer-textarea pub static-parent">
                            <div class="word-count">0/140</div>
                            {!! Form::textarea('answer', null, ['placeholder' => 'Write your message..', 'rows' => 4, 'cols' => 40, 'class' => 'maxed']) !!}
                        </div>

                        <div class="col-lg-12 submit pub">
                            {!! Form::submit('Send message') !!}
                        </div>
                    {!! Form::close() !!}
                @endif
            </div>
            <div class="col-md-6 conversation">
                @if(count($posts) > 0)
                    @foreach($posts as $key => $post)
                        <div class="message">
                            <div class="col-xs-12 col-md-12 bubble @if($post->user_id != 0) answer @endif">
                                @if (count($post->media))
                                    @foreach($post->media as $nr => $pic)
                                        <div class="reply-inner">
                                            <span class="post-date">{{ date(' d/m/Y, H:i', strtotime($post->post_date)) }}</span>
                                            <a class="reply_post" answerTrigger="{{$key}}" replyId="{{$post->fb_post_id}}" href="#!">
                                                <i class="fa fa-reply" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                        <div class="media-item">
                                            {{ $post->message }}
                                            <a class="gallery" href="#" data-featherlight="{{$pic->url}}">
                                                <img style="background-image: url({{$pic->url}});" src="" alt="" />
                                            </a>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="reply-inner">
                                        <span class="post-date">{{ date(' d/m/Y, H:i', strtotime($post->post_date)) }}</span>
                                        <a class="reply_post" answerTrigger="{{$key}}" replyId="{{$post->fb_post_id}}" href="#!">
                                            <i class="fa fa-reply" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                    {{ $post->message }}
                                @endif
                            </div>
                        </div>

                        @foreach($post->innercomments as $nr => $comment)
                            <div class="message inner">
                                <div class="col-xs-12 col-md-12 bubble @if($comment->user_id != 0) answer @endif">
                                    @if (count($comment->media))
                                        @foreach($comment->media as $nr => $pic)
                                            <div class="reply-inner">
                                                <span class="post-date">{{ date(' d/m/Y, H:i', strtotime($comment->post_date)) }}</span>
                                            </div> 
                                            <div class="media-item">
                                                {{ $comment->message }}
                                                <a class="gallery" href="#" data-featherlight="{{$pic->url}}">
                                                    <img style="background-image: url({{$pic->url}});" src="" alt="" />
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="reply-inner">
                                            <span class="post-date">{{ date(' d/m/Y, H:i', strtotime($comment->post_date)) }}</span>
                                        </div>
                                        {{ $comment->message }}
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div class="row answer_block answer_post_{{$key}}">
                            <div class="col-xs-12 answer_specific">
                                {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\PublishController@replyPost', $publishment->id), 'class' => 'form_' . $key)) !!}
                                    {!! Form::hidden('in_reply_to', '') !!}
                                    {!! Form::textarea('answer', null, ['placeholder' => 'Enter your answer', 'class' => 'specific_answer ' .$key , 'rows' => 2, 'cols' => 40]) !!}
                                {!! Form::close() !!}
                            </div>
                        </div>
                    @endforeach

                    {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\PublishController@replyPost', $publishment->id))) !!}
                        <div class="col-lg-12 answer-textarea pub static-parent">
                            {!! Form::textarea('answer', null, ['placeholder' => 'Write your message..', 'rows' => 4, 'cols' => 40]) !!}
                        </div>

                        <div class="col-lg-12 submit pub">
                            {!! Form::submit('Send message') !!}
                        </div>
                    {!! Form::close() !!}
                @endif
            </div>
        </div>

    </div>

@endsection
