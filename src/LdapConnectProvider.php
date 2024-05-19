<?php

namespace Rafael\LdapConnector;

use Illuminate\Support\ServiceProvider;

class LdapConnectProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfiguration();
    }

    /**
     * Register the publishable LDAP configuration file.
     *
     * @return void
     */
    protected function registerConfiguration()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ldap.php' => config_path('ldap.php'),
            ], 'ldap-config');
        }
    }
}