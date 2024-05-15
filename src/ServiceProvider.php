<?php

namespace LdapConnector\Laravel;

use Illuminate\Support\ServiceProvider;

class ServiceProvider extends ServiceProvider
{
      /**
     * Register the publishable LDAP configuration file.
     */
    protected function registerConfiguration(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ldap.php' => config_path('ldap.php'),
            ], 'ldap-config');
        }
    }
}