# LDAP Connect

 <p align="center">
  <img alt="License" src="https://img.shields.io/badge/License-MIT-blue">
</p>
<br>


Faça bind e busque usuarios de forma simples.

## Dependências:
- ⚡ **PHP 7.4**

## Como usar?

### Laravel >= 8
1 - Instale a biblioteca
```bash
composer require rafa-carmo/ldap-connector
```

2 - Publique os arquivos de configuração:
```bash
 php artisan vendor:publish --tag=ldap-config --tag=ldap-service  
```

3 - Preencha as configuraçãos em seu .env:
```env
LDAP_HOST="localhost" # Obrigatório
LDAP_USERNAME="Usuário para buscas" # Opcional
LDAP_PASSWORD="Senha do usuário acima" # Opcional
LDAP_PORT="Porta do Serviço" # Opcional - Padrão: 389
LDAP_TIMEOUT=5 # Opcional - Timeout padrão.

LDAP_BASE_DN="dc=example,dc=com" # Opcional - Dominio Base
LDAP_MAIL_DOMAIN=example.com # Opcional - Dominio para login com email

LDAP_AUTO_CREATE=false # Opcional - Para implementar lógica de criar usuário automático no primeiro acesso
```

4 - Instancie uma classe na sua area de login para fazer sua lógica:
```php
# Exemplo ( Este é um exemplo para entender a lógica, aplique de acordo com seu projeto )
    $ldap = new LdapConnectService();
    # bind - Faz a verificação do usuário no servidor LDAP
    # Retorna True ou o erro.
    $bindAttempt = $ldap->bind($request->email, $request->password);

    # Caso tenha sucesso na validação do usuário faz o login
    if($bindAttempt === true) {
        # A função createOrUpdateLogin faz a validação no banco se o usuário ja existe
        # os parametros que devem ser passados são:
        # 1° - Classe de usuarios e autenticação
        # 2° - Caso vá atualizar ou criar o usuario, devem ser passados os dados que serão salvos no banco. (Atenção: A criação somente será efetuada caso  a variável LDAP_AUTO_CREATE esteja true)
        # 3° - Um array com o nome do campo a ser validado no banco
        # caso o login seja efetuado com sucesso irá retornar true, caso não irá retornar a mensagem de erro de usuário não cadastrado.
        $login = $ldap->createOrUpdateLogin(User::class, [], ["username" => $ldap->getUsername($request->email)]);

        # Caso tenha efetuado o login redireciona para a rota home
        if($login === true) {
            return redirect(route('home'));
        }

        # Sobreescreve a mensagem de erro para retornar ao usuário.
        $bindAttempt = $login;
    }

    # retorna a rota anterior com as mensagens de erro
    return redirect()->back()->withErrors([
        "error" => $bindAttempt
    ]);
```

## Personalizando o serviço:

Para manipular o funcionamento do serviço basta alterar o arquivo localizado em **App\Http\Services\LdapConnectService.php**

### Funções da classe:

#### Função privada - createConnection:
Cria a conexão inicial com o servidor ldap

#### Função privada - sanitize:
Limpa a string de busca para evitar LDAP injection

#### Função pública - getUsername: 
Limpa e padroniza o username

#### Função pública - genLoginString:
Função para gerar o login que será feito no servidor LDAP,

Caso seu login deva ser de uma forma diferente favor personalizar esta função no arquivo.

exemplo 1:
Caso seja login com email 
login: `usuario.nome`
a função fará o login fazendo a concatenação com o dominio: `usuario.nome@dominio.com`

exemplo 2:
Caso seja login com uid.
usuário: `usuario.nome`
a função fará o login fazendo a concatenação com o base_dn: `ud=usuario.nome,dc=example,dc=com`


#### Função pública - bind:
Fará o bind com o servidor LDAP para validar o usuário.

Retornos: True para validado / Mensagem de Error.

#### Função pública - findUser:
Esta função somente irá ser utilizada caso estejam configuradas as variáveis `LDAP_USERNAME` e `LDAP_PASSWORD`

Ela fará a busca de usuários no servidor LDAP.

Recebe 2 parâmetros:
- Login de busca ( caso tenha @ o tipo de busca será mail por padrão )
- Tipo de busca ( caso seja deixado em branco ira buscar por uid )

#### Função pública - createOrUpdateLogin: 
Fará a criação ou atualização de um cadastro no banco de dados do sistema.

**Obs:** A criação somente será efetuada caso a variável `LDAP_AUTO_CREATE` seja true.

Parametros da função:
- 1° - Model do usuário.
- 2° - Array com os dados para criação ou atualização do usuário.
- 3° - Array com os dados de busca do usuário.

Retornos da função:
- True - Caso o login seja efetuado com sucess.
- Mensagem de Erro.

## Como Contrubuir?

- Faça um `Fork` do Projeto.
- Clone o projeto no seu repositório
- Crie uma nova branch com a funcionalidade `git branch -b nova-funcionalidade`
- commit a nova funcionalidade `git commit -m "feat: funcionalidade"
- Faça um push no seu fork `git push origin main`
- No seu repositório clique em Contribute e abra uma `pull request`


Obrigado, Qualquer sujestão fique a vontade para criar uma issue.