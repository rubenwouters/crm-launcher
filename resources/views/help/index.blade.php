@extends('crm-launcher::layouts.master')

@section('title', 'Help')
@section('header-title', 'Help')

@section('sidebar')
    @parent
@endsection

@section('content')

    <div class="help">
        <div class="row">

            <h2>Not sure where to start?</h2>
            <h3>Here's a quick overview of the features.</h3>

            <div class="col-xs-12 hide-help">
                <a href="/crm/help/disable">Skip this overview.</a>
            </div>

            <div class="col-xs-12 image">
                <div class="col-md-9">
                    <img src="/crm-launcher/img/help/dashboard.png" alt="" />
                </div>
                <div class="col-md-3">
                    <h4>Overview</h4>
                    <p>
                        This page is the hearth of the package.
                        It's there to keep you and your team up-to-date.
                    </p>
                    <p>
                        You'll be able to see a bunch of useful data at a glance.
                    </p>
                </div>
            </div>

            <div class="col-xs-12 image">
                <div class="col-md-9">
                    <img src="/crm-launcher/img/help/cases.png" alt="" />
                </div>
                <div class="col-md-3">
                    <h4>Cases overview</h4>
                    <p>
                        Every conversation started by your customer on Facebook or Twitter will become a case.
                    </p>
                    <p>
                        At the top, you'll find filters:
                    </p>
                    <p>
                        <span class="bold">My cases:</span> all cases you're working on.<br>
                        <span class="bold">New cases:</span> no answers were given yet.<br>
                        <span class="bold">Open cases:</span> answers were already given.<br>
                        <span class="bold">Closed cases:</span> solved.
                    </p>
                    <p>
                        In combination with the filters, there's a powerful search functionality on this page. You'll be able to search by name, keywords, dates (like '12 may' or even 'yesterday').
                    </p>
                </div>
            </div>

            <div class="col-xs-12 image">
                <div class="col-md-9">
                    <img src="/crm-launcher/img/help/case-detail.png" alt="" />
                </div>
                <div class="col-md-3">
                    <h4>Case detail</h4>
                    <p>
                        Once you click on a case, you'll find yourself in de detail page. This page provides you in the first place with the conversation itself.
                    </p>
                    <p>
                        This page comes along with some handy information like the origin of the post (Facebook or Twitter), wheter it's a public or private post and so on.
                    </p>
                    <p>
                        For those who don't want to read the conversation over and over again, there's a summary section added to this page. <br>
                        Here you can read the summaries instead of reading the whole conversation again.
                    </p>
                </div>
            </div>

            <div class="col-xs-12 image">
                <div class="col-md-9">
                    <img src="/crm-launcher/img/help/publisher.png" alt="" />
                </div>
                <div class="col-md-3">
                    <h4>Publisher</h4>
                    <p>
                        Social media is no one way street. You want to be able to post or tweet too. That's why there's publisher.
                    </p>
                    <p>
                        You can publish content to your Twitter followers and/or people who liked your Facebook page.
                    </p>
                    <p>
                        Yes, you hear me right. You can post and tweet in just one effort.
                    </p>
                </div>
            </div>

            <div class="col-xs-12 image">
                <div class="col-md-9">
                    <img src="/crm-launcher/img/help/publish-detail.png" alt="" />
                </div>
                <div class="col-md-3">
                    <h4>Publishment detail</h4>
                    <p>
                        Once you published content, you want to be able to read what people think about it or even react with them. That's possible.
                    </p>
                    <p>
                        Both the Facebook AND Twitter reactions are shown at the same page. No more going back and forth between Facebook and Twitter.
                    </p>
                </div>
            </div>

            <div class="col-xs-12 image">
                <div class="col-md-9">
                    <img src="/crm-launcher/img/help/team.png" alt="" />
                </div>
                <div class="col-md-3">
                    <h4>Team</h4>
                    <p>
                        Last, but not least: your team. Some people will manage their socials on their own and that's perfectly fine. Others may feel the need to add multiple helpers. That's why there's this page.
                    </p>
                    <p>
                        You can easily manage your team: add people or revoke access of people.
                    </p>
                </div>
            </div>


            <div class="col-xs-12 hide-help button">
                <a href="/crm/help/disable">Thanks Houston, I've got it from here.</a>
            </div>
        </div>
    </div>

@endsection
