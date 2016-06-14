@extends('crm-launcher::layouts.master')

@section('title', 'Publisher')
@section('header-title', 'Publisher')

@section('sidebar')
    @parent
@endsection

@section('content')

    <div>
        @if (count($errors) > 0)
            <div class="error-message"><span>{{ $errors->first() }}</span></div>
        @endif
        <div class="row publish">

            <h2 class="title">New publishment</h2>
            <div class="col-xs-12 col-md-6 col-md-offset-3">
                {!! Form::open(array('action' => array('\Rubenwouters\CrmLauncher\Controllers\PublishController@publish'))) !!}

                    <div class="col-lg-12 media-choice">
                        @if (isTwitterLinked())
                            <div class="col-xs-6 twitter-btn">
                                {!! Form::checkbox('social[]', 'twitter', false, ['id' => 'twitter']) !!}
                                <label class="twitter" for="twitter">
                                    <i class="fa fa-twitter" aria-hidden="true"></i> Twitter
                                </label>
                            </div>
                        @endif

                        @if (isFacebookLinked())
                            <div class="col-xs-6 fb-btn">
                                {!! Form::checkbox('social[]', 'facebook', false, ['id' => 'facebook']) !!}
                                <label class="facebook" for='facebook'>
                                    <i class="fa fa-facebook" aria-hidden="true"></i> Facebook
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-12 summary-textarea static-parent">
                        <div class="word-count">0/140</div>
                        {!! Form::textarea('content',null,['placeholder' => 'Enter your content', 'rows' => 4, 'cols' => 40]) !!}
                    </div>

                    <div class="col-lg-12 submit">
                        {!! Form::submit('Publish') !!}
                    </div>
                {!! Form::close() !!}
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 publish-now">
                <a href="#!">Add new publishment <i class="fa fa-angle-down" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="row publishments">
            <div class="col-md-6 col-md-offset-3">
                <h2 class="title">Your publishments</h2>
                @foreach($publishments as $key => $publishment)
                    <a href="/crm/publisher/{{$publishment->id}}">
                        <div class="row">
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
                    </a>
                @endforeach

                <div class="pagination-centered">
                    {!! $publishments->links() !!}
                </div>

                @if(! count($publishments))
                    <div class="col-xs-12 no-results">
                        <h3>No publishments yet.</h3>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
