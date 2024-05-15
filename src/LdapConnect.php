<?php

namespace LdapConnect;

class LdapConnect
{
    private $connection;
    private $port;
    private $host;
    private $user;
    private $password;
    private $base_dn;
    private $protocol_version;


    public function __construct()
    {
        $this->host = env("LDAP_DEFAULT_HOST", "localhost");
        $this->port = env("LDAP_DEFAULT_PORT", 389);
        $this->user = env("LDAP_DEFAULT_USERNAME");
        $this->password = env("LDAP_DEFAULT_PASSWORD");
        $this->base_dn = env("LDAP_DEFAULT_BASE_DN");
        $this->protocol_version = env("LDAP_OPT_PROTOCOL_VERSION", 3);

        $this->createConnection();
    }

     /**
    *--------------------------------------------------------------------------
    * createConnection
    *--------------------------------------------------------------------------
    *
    * Inicializa a conexão com o ldap.
    *
    */
    private function createConnection()
    {

        $this->connection = ldap_connect($this->host, $this->port);
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->protocol_version);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);


    }

    /*
    |--------------------------------------------------------------------------
    | Sanitize
    |--------------------------------------------------------------------------
    |
    | Função para limpar a string na busca para evitar ldpa injection
    |
    */
    private function sanitize(string $value) {
        $sanitize = str_replace(array('\\', '*', '(', ')'), array('\5c', '\2a', '\28', '\29'), $value);
        for ($i = 0; $i<strlen($sanitize); $i++) {
            $char = substr($sanitize, $i, 1);
            if (ord($char)<32) {
                $hex = dechex(ord($char));
                if (strlen($hex) == 1) $hex = '0' . $hex;
                $sanitize = str_replace($char, '\\' . $hex, $sanitize);
            }
        }
        return $sanitize;
    }

    /**
    *--------------------------------------------------------------------------
    * Sanitize
    *--------------------------------------------------------------------------
    *
    * Função para apenas fazer o bind com o servidor ldap para validar usuario.
    *
    * @param  string $username
    * @param  string  $password
    * @return bool
    */
    public function bind(string $username, string $password) {
        if(@ldap_bind($this->connection, "uid=".$username.",".$this->base_dn, $password)) return true;
        return false;
    }

    /**
    *--------------------------------------------------------------------------
    * Buscar Usuário
    *--------------------------------------------------------------------------
    *
    * Função para buscar usuario no ad.
    *
    * @param  string $login
    * @return false|array
    */
    public function findUser(string $login) {
        $type = "uid";
        $sanitizedLogin = $this->sanitize($login);
        if(str_contains($sanitizedLogin, "@")) $type = "mail";

        if(@ldap_bind($this->connection, $this->user, $this->password)){
            $filter = "($type=$sanitizedLogin)";
            $result = @ldap_search($this->connection, $this->base_dn, $filter);
            $entries = @ldap_get_entries($this->connection, $result);
            if(count($entries) > 1) return $entries;

            return [];
        }

        return [];
    }
}
