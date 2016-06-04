<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Http\Request;

use Illuminate\Routing\Controller;
use App\Http\Requests;
use Socialite;
use Carbon\Carbon;
use Rubenwouters\CrmLauncher\Models\Configuration;

class FacebookLoginController extends Controller
{
    /**
     * Ask permission on Facebook account.
     * @return view
     */
    public function askFbPermissions()
    {
        return Socialite::with('facebook')->scopes(['publish_pages', 'manage_pages', 'read_page_mailboxes'])->redirect();
    }

    /**
     * Handles redirect by Facebook after login. Inserts Facebook page Access token
     * @return view
     */
    public function fbCallback()
    {
        try {
            $fbUser = Socialite::with('facebook')->user();
            $token = $fbUser->token;
            $pageAccessToken = $this->getPageAccessToken($token);

            if ($pageAccessToken) {
                $this->insertFbToken($pageAccessToken);
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            getErrorMessage($e->getResponse()->getStatusCode());
        }

        return redirect('/crm/dashboard');
    }

    /**
     * Uses user access token to become never-expiring page access token.
     * @param  string $userToken
     * @return string (page access token)
     */
    private function getPageAccessToken($userToken)
    {
        $fb = initFb();

        try {
            $response = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '?fields=access_token', $userToken);
            $response = json_decode($response->getBody());
        } catch (Exception $e) {
            getErrorMessage('permission');
            return false;
        }

        return $response->access_token;
    }

    /**
     * Insert Facebook access token
     * @param  string $token
     * @return void
     */
    private function insertFbToken($token)
    {
        if (count(Configuration::find(1)) < 1) {
            $config = new Configuration();
        } else {
            $config = Configuration::find(1);
        }

        $config->linked_facebook = 1;
        $config->facebook_access_token = $token;
        $config->save();
    }
}
