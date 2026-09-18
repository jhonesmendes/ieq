# Como ativar o login com Google

O código já está pronto e funcionando (`config/google_oauth.php`,
`iniciar_login_google.php`, `auth.html`, `oauth_handler.php`). O único passo
que falta é criar uma credencial OAuth no Google Cloud Console e colar as
duas chaves no `.env`. Isso leva uns 10 minutos.

O botão "Google" na tela de login já está **habilitado**
(`GOOGLE_OAUTH_HABILITADO = true` em `config/google_oauth.php`), mas até você
completar os passos abaixo, clicar nele vai dar erro "redirect_uri_mismatch"
— é esperado, o Google só aceita depois que a URL estiver cadastrada lá.

## Passo 1 — Criar/abrir o projeto no Google Cloud

1. Acesse **https://console.cloud.google.com/**
2. Faça login com uma conta Google (pode ser a mesma do e-mail da igreja).
3. No topo, clique no seletor de projeto → **"Novo projeto"**.
4. Nome sugerido: `IEQ Gestão`. Clique em **Criar**.

## Passo 2 — Configurar a "tela de consentimento OAuth"

Antes de criar a credencial, o Google exige isso pelo menos uma vez por projeto.

1. Menu lateral → **APIs e serviços** → **Tela de consentimento OAuth**.
2. Tipo de usuário: **Externo** → Criar.
3. Preencha:
   - Nome do app: `IEQ Gestão de Células`
   - E-mail de suporte do usuário: seu e-mail
   - E-mail de contato do desenvolvedor: seu e-mail
4. Pode pular "Escopos" e "Usuários de teste" (clique em Salvar e continuar
   até o resumo final).
5. Não precisa publicar o app para produção agora — ele funciona em modo de
   teste para os e-mails que você adicionar como "usuários de teste"; para
   liberar para qualquer pessoa da igreja, publique o app (botão "Publish
   app") quando estiver pronto.

## Passo 3 — Criar a credencial OAuth (Client ID)

1. Menu lateral → **APIs e serviços** → **Credenciais**.
2. **+ Criar credenciais** → **ID do cliente OAuth**.
3. Tipo de aplicativo: **Aplicativo da Web**.
4. Nome: `IEQ Web`.
5. Em **Origens JavaScript autorizadas**, adicione as duas (uma por linha,
   sem barra no final):
   ```
   https://ieqroo.com.br
   https://www.ieqroo.com.br
   ```
6. Em **URIs de redirecionamento autorizados**, adicione as duas:
   ```
   https://ieqroo.com.br/auth.html
   https://www.ieqroo.com.br/auth.html
   ```
   (o site responde nos dois domínios — se só uma delas estiver cadastrada,
   quem cair na outra recebe erro do Google)
7. Clique em **Criar**. Uma janela vai mostrar o **Client ID** e o
   **Client Secret** — copie os dois agora, o Secret não aparece de novo
   depois (dá pra gerar um novo se perder).

## Passo 4 — Colocar as credenciais no `.env`

Abra o arquivo `.env` na raiz do projeto (mesmo lugar das configurações do
banco de dados) e preencha:

```
GOOGLE_CLIENT_ID=cole-aqui-o-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=cole-aqui-o-client-secret
```

Isso substitui automaticamente os valores antigos que estavam fixos em
`config/google_oauth.php` (o código já prioriza o `.env` quando ele existe).

## Passo 5 — Testar

1. Suba o `.env` atualizado pro servidor (o `.env` normalmente **não** é
   versionado — confirme que ele já está no servidor com as credenciais
   antigas do banco de dados; se sim, só edite o mesmo arquivo por lá).
2. Abra `https://ieqroo.com.br/` (ou a versão com `www`) na tela de login.
3. Clique em **Google**.
4. Deve abrir a tela de escolha de conta do Google e, ao autorizar, voltar
   logado no sistema.

### Se der erro "Acesso bloqueado: este app não foi verificado"

Normal enquanto o app estiver em modo de teste (Passo 2). Duas opções:
- Adicionar o e-mail de quem vai testar em **Tela de consentimento OAuth →
  Usuários de teste**; ou
- Publicar o app (**Tela de consentimento OAuth → botão "Publish app"**) —
  não exige revisão do Google para os escopos básicos usados aqui
  (`email`, `profile`).

### Se der erro "redirect_uri_mismatch"

A URL que apareceu no erro do Google precisa estar **exatamente igual** (mesmo
protocolo `https://`, mesmo domínio, sem barra a mais no final) na lista de
"URIs de redirecionamento autorizados" do Passo 3. É o erro mais comum —
volte lá e confira caractere por caractere.

## O que já está pronto no código (não precisa mexer)

- `config/google_oauth.php` — monta a URL de login e troca o código pelo
  token; já detecta sozinho se o visitante usou `ieqroo.com.br` ou
  `www.ieqroo.com.br`.
- `iniciar_login_google.php` — botão "Google" da tela de login manda pra cá.
- `auth.html` — recebe a volta do Google e repassa pro `oauth_handler.php`
  (existe por causa de um bloqueio de Mod_Security com URLs grandes).
- `oauth_handler.php` — cria o usuário (se for a primeira vez, fica
  `status_aprovacao = 'pendente'`, igual ao cadastro normal — alguém com
  permissão de admin precisa aprovar em **Aprovação de Cadastros**) ou
  autentica quem já existe, e abre a sessão do sistema.
