<?php

namespace RafaCarmo\LdapConnector;

use Illuminate\Support\Facades\Auth;

class LdapConnectLaravel
{

    private $connection;
    private $port;
    private $host;
    private $user;
    private $password;
    private $base_dn;
    private $mail_domain;
    private $protocol_version;
    private $auto_create;


    public function __construct()
    {
        $this->configVariables();
        $this->createConnection();
    }

    /**
    *--------------------------------------------------------------------------
    * Configurar Variáveis
    *--------------------------------------------------------------------------
    *
    * Pega as variáveis de ambiente para a classe
    *
    */
    private function configVariables() {
        if(config('ldap')) {
            $this->host = config("ldap.host");
            $this->port = config("ldap.port");
            $this->user = config("ldap.username");
            $this->password = config("ldap.password");
            $this->base_dn = config("ldap.base_dn");
            $this->mail_domain = config("ldap.mail_domain");
            $this->auto_create = config("ldap.auto_create", false);
            $this->protocol_version = config("ldap.protocol_version", 3);
            return;
        }

        $this->host = env("LDAP_HOST", "localhost");
        $this->port = env("LDAP_PORT", 389);
        $this->user = env("LDAP_USERNAME");
        $this->password = env("LDAP_PASSWORD");
        $this->base_dn = env("LDAP_BASE_DN");
        $this->mail_domain = env("LDAP_DOMAIN");
        $this->auto_create = env("LDAP_AUTO_CREATE", false);
        $this->protocol_version = env("LDAP_OPT_PROTOCOL_VERSION", 3);


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

    /**
    *--------------------------------------------------------------------------
    * Sanitize
    *--------------------------------------------------------------------------
    *
    * Função para limpar a string na busca para evitar ldpa injection.
    *
    * @param  string $login
    * @return string
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
    * Gerar Username
    *--------------------------------------------------------------------------
    *
    * Função para limpar e padronizar o username.
    *
    * @param  string $login
    * @return string
    */
    public function getUsername($login) {
        $username = $login;
        if(str_contains($username, "@")){
            $username = explode("@", $username)[0];
        }
        return $username;
    }

    /**
    *--------------------------------------------------------------------------
    * Gerar String de Login
    *--------------------------------------------------------------------------
    *
    * Função para gerar o tipo de login.
    *
    * @param  string $username
    * @return string
    */

    public function genLoginString($username) {
        if($this->mail_domain) {
            return $username."@".$this->mail_domain;
        }

        return "uid=" . $username. "," . $this->base_dn;
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
    * @return bool|string
    */
    public function bind(string $username, string $password) {
        $username = $this->getUsername($username);
        $username = $this->sanitize($username);
        $username = $this->genLoginString($username);

        if(@ldap_bind($this->connection, $username, $password)) return true;

        if(ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)){
            return $extended_error;
        };
        // if(ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)){
        //     if (strpos($extended_error, 'AcceptSecurityContext error, data 52e') !== false) {
        //         return "Usuário ou senha inválidos";
        //     }
        //     return $extended_error;
        // }

        return "Credenciais inválidas";
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
    public function findUser(string $login, string $type = "uid") {
        if(!config('ldap.username') && !config('ldap.password')) return;
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

    /**
    *--------------------------------------------------------------------------
    * Criar um usuário novo ou atualizar os dados do usuario
    *--------------------------------------------------------------------------
    *
    * Função para atualizar dados de usuario quando validado ou criar um novo
    * (obs): Para a função de criar um usuário deve ser expecificado no .env
    * com a variável LDAP_DEFAULT_AUTO_CREATE que deve ser true
    *
    * @param  string $model (Model de usuário)
    * @param  string $data (Dados para criação ou atualização do usuário)
    * @param  string $index (Indicie para busca do usuário)
    * @return false|array
    */
    public function createOrUpdateLogin(string $model, array $data=null, array $index = null) {
        if($index) {
            $findUser = $model::where($index);

            if($findUser->count() === 1) {
                $findUser->update($data);
                Auth::loginUsingId($findUser->first()->id);
                return $findUser->first();
            }

            return "Usuário não encontrado";
        }

        if($this->auto_create){
            $newUser = $model::create($data);
            Auth::loginUsingId($newUser->id);
            return $newUser;
        }

        return "Cadastro não encontrado";
    }

}
