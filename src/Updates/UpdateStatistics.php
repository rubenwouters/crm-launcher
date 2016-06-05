<?php

namespace Rubenwouters\CrmLauncher\Updates;

use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Models\Contact;
use Rubenwouters\CrmLauncher\Models\Publishment;
use Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent;
use Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent;

class UpdateStatistics {

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $log;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $contact;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent
     */
    protected $twitterContent;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent
     */
    protected $facebookContent;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Contact $contact
     * @param Rubenwouters\CrmLauncher\Models\Case $case
     * @param Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent
     * @param Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent
     */
    public function __construct(
        Log $log,
        Contact $contact,
        FetchTwitterContent $twitterContent,
        FetchFacebookContent $facebookContent
    ) {
        $this->log = $log;
        $this->contact = $contact;
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
            if (Publishment::where('tweet_id', $tweet['id_str'])->exists()) {

                $publishment = Publishment::where('tweet_id', $tweet['id_str'])->first();
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
        $posts = Publishment::orderBy('id', 'DESC')->where('fb_post_id', '!=', '')->get();

        foreach ($posts as $key => $post) {
            $object = $this->facebookContent->fetchFbStats($post);

            if (isset($object->shares)) {
                $post->facebook_shares = $object->shares->count;
            }
            $post->facebook_likes = $object->likes->summary->total_count;
            $post->save();
        }
    }

    // /**
    //  * Update config file with likes or followers
    //  * @param  string $type
    //  * @param  integer $nr
    //  * @return void
    //  */
    // public function updateStats($type, $nr)
    // {
    //     if ($type == 'twitter') {
    //
    //         $config = Configuration::first();
    //         $config->twitter_followers = $nr;
    //         $config->save();
    //
    //     } else if ($type == 'facebook') {
    //
    //         $config = Configuration::first();
    //         $config->facebook_likes = $nr;
    //         $config->save();
    //     }
    // }

}
