<?php

namespace Rubenwouters\CrmLauncher\ApiCalls;

use Datetime;
use Rubenwouters\CrmLauncher\Models\Configuration;

class ValidateTwitter {

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $contact;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Check if twitter settings are valid
     * @return boolean
     */
    public function validTwitterSettings()
    {
        if ($this->config->exists() && $this->config->first()->linked_twitter) {
            return true;
        }

        try {
            $client = initTwitter();
            $verification = $client->get('account/verify_credentials.json');
            $verification = json_decode($verification->getBody(), true);

            if ($this->config->exists() && $this->config->first()->exists()) {
                $this->config->insertTwitterId($verification);
            }

            return true;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getCode() == 429) {
                getErrorMessage($e->getResponse()->getStatusCode());
            }
            return false;
        }
    }
}
