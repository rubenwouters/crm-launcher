@extends('crm-launcher::layouts.master')

@section('title', 'Take off')
@section('header-title', 'Take off')

@section('sidebar')
    @parent
@endsection

@section('content')
    <div class="container">
        <div class="row config-checker">
            <div class="col-md-12">
                <div class="socials">
                    <div class="col-md-4">
                        <h2>Ready?</h2>

                        <div class="col-md-12 facebook-icon">
                            <i class="fa fa-facebook @if (isFbEnvFilledOut()) enabled @endif" aria-hidden="true"></i>
                        </div>

                        <h3>
                            @if (isFbEnvFilledOut() && hasFbPermissions())
                                Facebook linked
                            @elseif(isFbEnvFilledOut() && !hasFbPermissions())
                                Login required <br>
                                <a href="/facebook"><img class="login-btn" src="{{asset("crm-launcher/img/fb_login.png")}}" alt="" /></a><br>
                            @else
                                Facebook not linked
                            @endif
                        </h3>
                    </div>


                    <div class="col-md-4">
                        <h2>Set.</h2>

                        <div class="col-md-12 twitter-icon">
                            <i class="fa fa-twitter @if (isTwitterEnvFilledOut()) enabled @endif" aria-hidden="true"></i>
                        </div>

                        <h3>
                            @if (isTwitterEnvFilledOut() && ! $validTwitterSettings)
                                Wrong credentials, check .ENV file.
                            @elseif(isTwitterEnvFilledOut() && $validTwitterSettings)
                                Twitter linked
                            @else
                                Twitter not linked.
                            @endif

                        </h3>
                    </div>

                    <div class="col-md-4">
                        <h2>Go.</h2>

                        <div class="go-btn @if((isFbEnvFilledOut() && ! hasFbPermissions()) || (isTwitterEnvFilledOut() && ! $validTwitterSettings)) disabled @endif">
                            <a href="/crm/launch">Launch</a>
                        </div>

                        <h3 class="launch">
                            @if((isFbEnvFilledOut() && ! hasFbPermissions()) || (isTwitterEnvFilledOut() && ! $validTwitterSettings))
                                Houston, we've got a problem.
                            @else
                                Your customers will love you.
                            @endif
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
