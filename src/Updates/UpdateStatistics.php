<?php

namespace Rubenwouters\CrmLauncher\Updates;

use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Models\Contact;
use Rubenwouters\CrmLauncher\Models\Publishment;
use Rubenwouters\CrmLauncher\Models\Configuration;
use Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent;
use Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent;

class UpdateStatistics {

    /**
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $log;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Configuration
     */
    protected $config;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $contact;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Publishment
     */
    protected $publishment;

    /**
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent
     */
    protected $twitterContent;

    /**
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent
     */
    protected $facebookContent;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Log $log
     * @param Rubenwouters\CrmLauncher\Models\Configuration $config
     * @param Rubenwouters\CrmLauncher\Models\Contact $contact
     * @param Rubenwouters\CrmLauncher\Models\Publishment $publishment
     * @param Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent
     * @param Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent
     */
    public function __construct(
        Log $log,
        Configuration $config,
        Contact $contact,
        Publishment $publishment,
        FetchTwitterContent $twitterContent,
        FetchFacebookContent $facebookContent
    ) {
        $this->log = $log;
        $this->config = $config;
        $this->contact = $contact;
        $this->publishment = $publishment;
        $this->twitterContent = $twitterContent;
        $this->facebookContent = $facebookContent;
    }

    /**
     * Update stats in DB (like count & retweet count)
     * @param  array $tweets
     * @return void
     */
    public function updateTwitterStats()
    {
        $tweets = $this->twitterContent->fetchTwitterStats();

        foreach ($tweets as $key => $tweet) {
            if ($this->publishment->where('tweet_id', $tweet['id_str'])->exists()) {

                $publishment = $this->publishment->where('tweet_id', $tweet['id_str'])->first();
                $publishment->twitter_likes = $tweet['favorite_count'];
                $publishment->twitter_retweets = $tweet['retweet_count'];
                $publishment->save();
            }
        }
        $this->log->updateLog('publishments');
    }

    /**
     * Update stats in DB
     * @param  array $posts
     * @return void
     */
    public function updateFbStats()
    {
        $posts = $this->publishment->orderBy('id', 'DESC')->where('fb_post_id', '!=', '')->get();

        foreach ($posts as $key => $post) {
            $object = $this->facebookContent->fetchFbStats($post);

            if (isset($object->shares)) {
                $post->facebook_shares = $object->shares->count;
            }
            $post->facebook_likes = $object->likes->summary->total_count;
            $post->save();
        }
    }

    /**
     * Update config file with likes
     * @return void
     */
    public function updateFacebookDashboardStats()
    {
        $likes = $this->facebookContent->fetchLikes();

        $config = $this->config->first();
        $config->facebook_likes = $likes['fan_count'];
        $config->save();
    }

    /**
     * Update config file with followers
     * @return void
     */
    public function updateTwitterDashboardStats()
    {
        $followers = $this->twitterContent->fetchFollowers();

        $config = $this->config->first();
        $config->twitter_followers = $followers['followers_count'];
        $config->save();
    }
}
