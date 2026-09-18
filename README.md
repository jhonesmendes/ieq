# IEQ - Gestão de Células

Sistema web completo para gerenciamento de células e pequenos grupos de igrejas com suporte a múltiplos usuários, controle de presença, galeria de fotos e muito mais.

**Status:** ✅ Funcional e em Produção  
**Versão:** 2.0+  
**Última Atualização:** 22 de janeiro de 2026  
**📱 Otimização Mobile:** ✅ Completa (Jan/2026)

---

## 🎯 Funcionalidades

### Core
- 👥 **Gestão de Membros** - Cadastro completo com histórico e foto
- 📍 **Gestão de Células** - Organização de grupos com líderes e supervisores
- ✍️ **Controle de Presença** - Registro e acompanhamento de participação
- 👤 **Gestão de Usuários** - Usuários com múltiplos papéis e permissões
- 🔐 **Autenticação Google OAuth** - Login com conta Google

### Avançadas
- 📸 **Galeria de Reuniões** - Upload e gerenciamento de fotos com filtros
- 👋 **Visitantes** - Cadastro e acompanhamento de novos visitantes
- ⭐ **Novo Convertido** - Fluxo específico para novos conversos
- ✅ **Aprovação de Cadastros** - Sistema de aprovação com workflow
- 📚 **Cursos e Formação** - Gerenciamento de cursos e trilhas
- 📅 **Eventos** - Criação e divulgação de eventos com calendário
- ⛪ **Igrejas** - Gerenciamento de múltiplas igrejas
- 🗓️ **Calendário** - Visualização de eventos e reuniões
- ⚙️ **Configurações** - Personalização completa da aplicação
- 🔌 **API RESTful** - 50+ endpoints para acesso completo aos dados

### 📱 Responsividade Mobile (NEW!)
- ✅ **Otimização Completa** - Site 100% responsivo para celulares
- 📲 **Hamburger Menu** - Navegação colapsável em mobile
- 👆 **Touch-Friendly** - Botões e inputs com 44px+ (WCAG AA)
- 📊 **Grids Adaptativos** - Layouts que se ajustam: 1 coluna (mobile) → 3+ colunas (desktop)
- 📋 **Tabelas Responsivas** - Scroll horizontal automático em telas pequenas
- 🚀 **Performance** - Totalmente otimizado para conexões 4G
- **Breakpoints**: Mobile (<768px) • Tablet (768-1024px) • Desktop (>1024px)

---

## 🚀 Como Iniciar

### Requisitos
- PHP 7.4+ (recomendado 8.0+)
- XAMPP ou servidor web local/remoto
- SQLite3 (incluído no PHP) ou MySQL/MariaDB

### Instalação Rápida

1. **Clone ou copie os arquivos para seu servidor:**
```bash
# Se em XAMPP
cp -r ieq C:\xampp\htdocs\
```

2. **Configure permissões (Linux/Mac):**
```bash
chmod 777 data/
chmod 777 uploads/
```

3. **Acesse via navegador:**
```
http://localhost/ieq
```

4. **Crie sua conta:**
   - Clique em "Registre-se aqui"
   - Preencha os dados
   - Confirme o registro

5. **Faça login com seu email e senha**

Pronto! O banco de dados será criado automaticamente no primeiro acesso.

---

## 🔐 Hierarquia de Permissões

O sistema usa um modelo robusto de hierarquia de papéis:

| Papel | Nível | Permissões | Acesso |
|-------|-------|-----------|--------|
| 🔴 **Admin** | 5 | Acesso total, gerenciar usuários, sistema | Tudo |
| 🟣 **Pastor** | 4 | Gerenciar células, aprovar cadastros, relatórios | Tudo |
| 🟠 **Supervisor** | 3 | Gerenciar múltiplas células, aprovar presenças | Múltiplas células |
| 🟡 **Líder** | 2 | Gerenciar sua célula, registrar presença/fotos | Sua célula |
| 🟢 **Membro** | 1 | Acesso básico, apenas leitura | Dados públicos |

### Controle de Acesso
- ✅ Cada papel tem acesso apenas aos dados que pode visualizar
- ✅ Sistema valida permissões tanto no **frontend** quanto no **backend**
- ✅ Restrições são aplicadas na API (impossível contornar pela URL)
- ✅ Logs de todas as operações sensíveis

---

## � Segurança

### CSRF Protection (✅ Implementado)
O sistema está protegido contra ataques de Cross-Site Request Forgery (CSRF) através de:
- **Tokens únicos por sessão**: Cada sessão recebe um token CSRF único e aleatório
- **Validação automática**: Todos os formulários são validados automaticamente
- **Regeneração pós-login**: Token é regenerado após autenticação bem-sucedida
- **Proteção global**: Script JavaScript injeta tokens em todos os formulários

**Como funciona:**
```php
// Sistema injeta automaticamente
<input type="hidden" name="csrf_token" value="abc123...">

// Backend valida
if (!validar_csrf_token($_POST['csrf_token'])) {
    die('Token CSRF inválido');
}
```

### Rate Limiting (✅ Implementado)
Proteção contra brute force e ataques de força bruta:
- **Login:** Máximo 10 tentativas por minuto, bloqueio por 15 minutos
- **Registro:** Máximo 100 requisições por minuto
- **API:** Máximo 100 requisições por minuto (padrão)
- **Detecção de IP:** Suporta proxies e load balancers (Cloudflare, nginx, etc)

**Como funciona:**
```
Tentativa 1-9: Aceito ✅
Tentativa 10: Bloqueado por 15 minutos ❌
Erro: HTTP 429 "Too Many Requests"
```

### Logging de Segurança
Todas as operações de segurança são registradas em `data/seguranca.log`:
- Tentativas de login falhadas
- Ativações de rate limiting
- Falhas de validação CSRF
- IPs bloqueados
- Timestamps e detalhes completos

**Ver logs:**
```bash
tail -f data/seguranca.log
```

### Boas Práticas de Segurança Implementadas
- ✅ Senhas em Bcrypt (hash irreversível)
- ✅ Prepared statements (previne SQL injection)
- ✅ Validação de entrada em todos os formulários
- ✅ Output encoding (previne XSS)
- ✅ HTTPS recomendado em produção
- ✅ Headers de segurança (CORS configurado)
- ✅ Controle de acesso baseado em papel (RBAC)

### Validar Segurança
Para verificar se todas as proteções estão ativas:
```
Acesse: http://localhost/ieq/Documents/validar_seguranca.php
```

Relatório completo de:
- ✅ CSRF Protection
- ✅ Rate Limiting
- ✅ Integrações
- ✅ Banco de dados
- ✅ Logs de segurança

### Guia de Testes
Documentação completa sobre como testar cada funcionalidade de segurança:
```
Arquivo: Documents/GUIA_TESTE_SEGURANCA.md
```

---

## �🔌 API Endpoints Completos

Total de **50+ endpoints** organizados por funcionalidade:

### Autenticação (3)
- `POST /api/index.php?acao=login` - Fazer login
- `POST /api/index.php?acao=registrar` - Registrar novo usuário
- `POST /api/index.php?acao=alterar_senha` - Alterar senha

### Membros (6)
- `GET /api/index.php?acao=listar_membros` - Listar membros
- `GET /api/index.php?acao=obter_membro&id=1` - Obter membro
- `POST /api/index.php?acao=criar_membro` - Criar membro
- `POST /api/index.php?acao=atualizar_membro` - Atualizar membro
- `POST /api/index.php?acao=deletar_membro` - Deletar membro
- `POST /api/index.php?acao=upload_foto_membro` - Upload de foto

### Células (6)
- `GET /api/index.php?acao=listar_celulas` - Listar células
- `GET /api/index.php?acao=obter_celula&id=1` - Obter célula
- `POST /api/index.php?acao=criar_celula` - Criar célula
- `POST /api/index.php?acao=atualizar_celula` - Atualizar célula
- `POST /api/index.php?acao=deletar_celula` - Deletar célula
- `GET /api/index.php?acao=listar_lideres` - Listar líderes

### Presença (5)
- `GET /api/index.php?acao=listar_presencas_celula&celula_id=1` - Presenças
- `POST /api/index.php?acao=registrar_presenca` - Registrar presença
- `GET /api/index.php?acao=estatisticas_celula&celula_id=1` - Estatísticas
- `GET /api/index.php?acao=relatorio_presenca` - Relatório
- `POST /api/index.php?acao=deletar_presenca` - Deletar presença

### Galeria (4)
- `POST /api/index.php?acao=criar_reuniao_com_foto` - Upload de foto
- `GET /api/index.php?acao=listar_reunioes` - Listar reuniões
- `GET /api/index.php?acao=galeria_completa` - Galeria com filtros
- `GET /api/index.php?acao=obter_reuniao&reuniao_id=1` - Detalhes

### Visitantes (6)
- `GET /api/index.php?acao=listar_visitantes` - Listar visitantes
- `GET /api/index.php?acao=obter_visitante&id=1` - Obter visitante
- `POST /api/index.php?acao=criar_visitante` - Criar visitante
- `POST /api/index.php?acao=atualizar_visitante` - Atualizar visitante
- `POST /api/index.php?acao=deletar_visitante` - Deletar visitante
- `GET /api/index.php?acao=estatisticas_visitantes` - Estatísticas

### Aprovação de Cadastros (3)
- `GET /api/index.php?acao=listar_cadastros_pendentes` - Pendentes
- `POST /api/index.php?acao=aprovar_cadastro` - Aprovar
- `POST /api/index.php?acao=rejeitar_cadastro` - Rejeitar

### Eventos (6)
- `GET /api/index.php?acao=listar_eventos` - Listar eventos
- `GET /api/index.php?acao=listar_proximos_eventos` - Próximos
- `GET /api/index.php?acao=obter_evento&id=1` - Obter evento
- `POST /api/index.php?acao=criar_evento` - Criar evento
- `POST /api/index.php?acao=atualizar_evento` - Atualizar evento
- `POST /api/index.php?acao=deletar_evento` - Deletar evento

### Cursos (5)
- `GET /api/index.php?acao=listar_cursos` - Listar cursos
- `GET /api/index.php?acao=obter_curso&id=1` - Obter curso
- `POST /api/index.php?acao=criar_curso` - Criar curso
- `POST /api/index.php?acao=atualizar_curso` - Atualizar curso
- `POST /api/index.php?acao=deletar_curso` - Deletar curso

### Igrejas (5)
- `GET /api/index.php?acao=listar_igrejas` - Listar igrejas
- `GET /api/index.php?acao=obter_igreja&id=1` - Obter igreja
- `POST /api/index.php?acao=criar_igreja` - Criar igreja
- `POST /api/index.php?acao=atualizar_igreja` - Atualizar igreja
- `POST /api/index.php?acao=deletar_igreja` - Deletar igreja

### Usuários (4)
- `GET /api/index.php?acao=listar_usuarios` - Listar usuários
- `POST /api/index.php?acao=criar_usuario` - Criar usuário
- `POST /api/index.php?acao=atualizar_usuario` - Atualizar usuário
- `POST /api/index.php?acao=deletar_usuario` - Deletar usuário

### Dashboard (2)
- `GET /api/index.php?acao=estatisticas_dashboard` - Dashboard
- `GET /api/index.php?acao=listar_pastores` - Pastores

---

## 📁 Estrutura do Projeto

```
ieq/
├── index.php                    # Arquivo principal
├── README.md                    # Este arquivo
├── api/
│   └── index.php               # API RESTful com 50+ endpoints
├── config/
│   ├── database.php            # Configuração do banco
│   ├── auth.php                # Autenticação e hash
│   ├── membros.php             # Funções de membros
│   ├── celulas.php             # Funções de células
│   ├── presenca.php            # Funções de presença
│   ├── permissoes.php          # Sistema de permissões
│   ├── eventos.php             # Funções de eventos
│   ├── cursos.php              # Funções de cursos
│   ├── igrejas.php             # Funções de igrejas
│   ├── visitantes.php          # Funções de visitantes
│   ├── aprovacoes.php          # Sistema de aprovações
│   ├── google_oauth.php        # Google OAuth
│   └── upload.php              # Upload de arquivos
├── pages/
│   ├── login.php               # Página de login
│   ├── registro.php            # Página de registro
│   ├── callback_google.php     # Google OAuth callback
│   ├── dashboard.php           # Dashboard principal
│   ├── membros.php             # Gerenciar membros
│   ├── celulas.php             # Gerenciar células
│   ├── presenca.php            # Controle de presença
│   ├── cursos.php              # Gerenciar cursos
│   ├── eventos.php             # Gerenciar eventos
│   ├── galeria.php             # Galeria de fotos
│   ├── visitantes.php          # Gerenciar visitantes
│   ├── novo_convertido.php     # Fluxo de novo convertido
│   ├── aprovacao_cadastros.php # Aprovações
│   ├── calendario.php          # Calendário
│   ├── igrejas.php             # Gerenciar igrejas
│   ├── usuarios.php            # Gerenciar usuários
│   ├── configuracoes.php       # Configurações
│   └── logout.php              # Logout
├── includes/
│   └── sidebar.php             # Menu lateral
├── assets/
│   ├── css/                    # Estilos (style, sidebar, forms)
│   └── js/                     # JavaScript (main.js)
├── uploads/                    # Fotos e arquivos (permissão 777)
├── data/
│   └── ieq.db                  # Banco de dados SQLite
├── Documents/                  # Documentações e guias
└── scripts/                    # Scripts auxiliares
```

---

## 🎨 Customização

### Cores
As cores principais estão em `assets/css/style.css`:
- `#667eea` - Cor primária (azul)
- `#764ba2` - Cor secundária (roxo)
- `#28a745` - Cor de sucesso (verde)
- `#dc3545` - Cor de erro (vermelho)

### Configurações da Igreja
Acesse **Configurações** na aplicação para personalizar:
- Nome da igreja
- Nome das células (singular/plural)
- Logo da instituição
- Fuso horário
- Idioma
- Cores do tema

### Google OAuth
Para ativar login com Google:

1. Crie um projeto no [Google Cloud Console](https://console.cloud.google.com)
2. Crie OAuth 2.0 credentials (tipo: Web application)
3. Configure redirect URI: `https://seu-dominio.com/ieq/pages/callback_google.php`
4. Configure variáveis de ambiente (arquivo `.env`):
   ```
   GOOGLE_CLIENT_ID=seu_client_id_aqui
   GOOGLE_CLIENT_SECRET=seu_client_secret_aqui
   ```
5. Reinicie Apache/PHP

---

## 📊 Banco de Dados

### SQLite (Padrão)
- Arquivo automático: `data/ieq.db`
- Sem configuração necessária
- Ideal para desenvolvimento e pequenas instalações
- Suporta até ~100k registros sem problemas

### MySQL / MariaDB (Recomendado para Produção)
Crie arquivo `.env` na raiz do projeto:
```
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ieq
DB_USER=seu_usuario
DB_PASS=sua_senha
```

**Criar banco manualmente:**
```sql
CREATE DATABASE ieq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ieq.* TO 'seu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Tabelas Principais
- **usuarios** - Usuários do sistema com papéis
- **celulas** - Células/grupos com líderes
- **membros** - Membros das células
- **presencas** - Registro de presenças
- **reunioes** - Reuniões com fotos
- **visitantes** - Visitantes das células
- **eventos** - Eventos da igreja
- **cursos** - Cursos disponíveis
- **igrejas** - Múltiplas igrejas
- **aprovacoes_cadastro** - Cadastros pendentes

---

## 💾 Backup e Restore

### SQLite
```bash
# Backup
cp data/ieq.db data/ieq.backup.sql

# Restore
cp data/ieq.backup.sql data/ieq.db
```

### MySQL
```bash
# Backup
mysqldump -u usuario -p ieq > backup_$(date +%Y%m%d).sql

# Restore
mysql -u usuario -p ieq < backup.sql
```

### Backup Automático (Recomendado)
```bash
# Adicionar ao cron (rodar diariamente às 2AM)
0 2 * * * /usr/bin/mysqldump -u usuario -p'senha' ieq > /backup/ieq_$(date +\%Y\%m\%d).sql
```

---

## 🔐 Segurança

### Implementado ✅
- ✅ Senhas hashadas com bcrypt (custo 10)
- ✅ Validação de sessão em todas as operações
- ✅ Inputs sanitizados contra SQL Injection (PDO prepared statements)
- ✅ Proteção por hierarquia de papéis
- ✅ Controle de upload de arquivos (tipo, tamanho)
- ✅ Validação de permissões no backend (obrigatório)

### TODO ⚠️
- ⚠️ Proteção CSRF (em desenvolvimento)
- ⚠️ Rate limiting (recomendado em produção)
- ⚠️ Two-factor authentication (futuro)

### HTTPS
**OBRIGATÓRIO em produção!** Configure SSL/TLS no seu servidor:
- Use Let's Encrypt (gratuito)
- Redirecione HTTP → HTTPS
- Configure HSTS headers

---

## 🌐 Deploy em Produção

### Pre-Deployment Checklist
- [ ] Servidor configurado com HTTPS/SSL
- [ ] PHP 8.0+ instalado e configurado
- [ ] MySQL/MariaDB instalado (ou SQLite com permissões)
- [ ] Firewall configurado (porta 80 e 443 abertos)
- [ ] Backup automático configurado
- [ ] Monitoramento de erros ativo

### Configuração de Produção

1. **Configure arquivo `.env`:**
```
APP_ENV=production
APP_DEBUG=false
DB_DRIVER=mysql
DB_HOST=seu_host
DB_NAME=ieq
DB_USER=seu_user
DB_PASS=sua_senha
GOOGLE_CLIENT_ID=seu_id
GOOGLE_CLIENT_SECRET=seu_secret
```

2. **Configure permissões:**
```bash
chmod 755 -R ieq/
chmod 777 ieq/data/
chmod 777 ieq/uploads/
```

3. **Configure PHP (php.ini):**
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

4. **Configure Apache (.htaccess):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

5. **Altere credenciais padrão:**
   - Faça login como admin
   - Altere senha do admin
   - Remova usuários de teste

6. **Configure backups automáticos:**
```bash
# Crontab para backup diário
0 2 * * * /backup/backup.sh
```

### Performance em Produção
- Use PHP 8.0+ (3x mais rápido que 7.4)
- Configure cache HTTP (assets estáticos)
- Use CDN para imagens (Cloudflare, AWS CloudFront)
- Monitore queries lentas do banco
- Configure índices no MySQL
- Use PHP-FPM ao invés de mod_php

---

## 🆘 Troubleshooting

### Problemas de Banco de Dados
**"Não consegue conectar ao banco"**
- Verifique se `data/ieq.db` existe e tem permissão
- Se usar MySQL, verifique credenciais em `.env`
- Verifique se servidor MySQL está rodando

**"Tabelas não existem"**
- Acesse a aplicação para criar automaticamente
- Ou execute scripts de migration manualmente

### Problemas de Login
**"Usuário não encontrado"**
- Verifique se fez registro primeiro
- Confira o email exato digitado

**"Erro ao fazer login com Google"**
- Verifique Client ID e Secret no `.env`
- Confirme URI de callback está configurada
- Verifique se está acessando por HTTPS

### Problemas de Permissão
**"Você não tem permissão para esta ação"**
- Verifique o papel do usuário (Admin, Pastor, etc)
- Admins podem criar qualquer coisa
- Líderes só veem sua célula

### Problemas de Upload
**"Erro ao fazer upload de foto"**
- Verifique limite (5MB para fotos)
- Confirme pasta `uploads/` existe com permissão 777
- Verifique extensão: JPG, PNG ou GIF
- Verifique `post_max_size` em php.ini

### Problemas de Performance
**"Sistema lento"**
- Verifique versão PHP (use 8.0+)
- Monitore queries do banco (use `EXPLAIN`)
- Adicione índices nas tabelas grandes
- Use cache (Redis, Memcached)

---

## 📝 Próximos Passos

- [ ] Proteção CSRF completa
- [ ] Exportação para PDF/Excel
- [ ] Dashboard com gráficos avançados
- [ ] Integração com mapas (Google Maps)
- [ ] Notificações por email
- [ ] App mobile (React Native)
- [ ] Two-factor authentication
- [ ] Auditoria de logs avançada

---

## 📞 Suporte e Contribuição

- 🐛 **Bugs:** Abra uma issue no repositório
- 💡 **Sugestões:** Envie suas ideias
- 🤝 **Contribuições:** Pull requests são bem-vindos
- 📧 **Contato:** Entre em contato com o time

**Documentação Técnica:** Veja pasta `Documents/` para guias detalhados

---

## 📄 Licença

**MIT License** - Você é livre para usar, modificar e distribuir este projeto

---

## ✨ Agradecimentos

Desenvolvido com ❤️ para igrejas e comunidades de fé

**Última atualização:** 22 de janeiro de 2026
