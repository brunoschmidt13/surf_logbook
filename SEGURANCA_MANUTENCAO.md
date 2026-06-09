# 🔐 SEGURANÇA & MANUTENÇÃO - SurfLog

## 📋 CHECKLIST DE SEGURANÇA IMPLEMENTADA

### ✅ Autenticação e Autorização

- [x] **Senhas Hasheadas:** Usando `password_hash($pwd, PASSWORD_DEFAULT)` = bcrypt
- [x] **Verificação Segura:** `password_verify($inputSenha, $hashArmazenado)`
- [x] **Sessões:** `$_SESSION` para manter usuário logado
- [x] **Logout:** `session_destroy()` limpa completamente a sessão
- [x] **Proteção de Rotas:** Todas as páginas verificam `isset($_SESSION['usuario_id'])`
- [x] **Admin Check:** Páginas admin verificam `is_admin == 1`
- [x] **Dupla Segurança:** Verificações em cascata antes de operações críticas

### ✅ Proteção contra SQL Injection

- [x] **PDO Prepared Statements:** Uso de placeholders `?` em TODAS as queries
- [x] **Separação Código-Dados:** Dados nunca interpolados diretamente na query
- [x] **Validação de Entrada:** `filter_input()` para email e inteiros
- [x] **Tipagem:** `intval()`, `floatval()` para dados numéricos

**Exemplo Seguro:**
```php
// ✅ CORRETO
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]); // Parâmetro separado

// ❌ INSEGURO (EVITAR!)
$result = $pdo->query("SELECT * FROM usuarios WHERE id = $id"); // Perigo!
```

### ✅ Proteção contra XSS (Cross-Site Scripting)

- [x] **htmlspecialchars():** Usado ao exibir dados do usuário no HTML
- [x] **Conversão HTML:** `<`, `>`, `&`, `"`, `'` convertidos para entidades HTML
- [x] **Previne:** Injeção de `<script>` malicioso

**Exemplo:**
```php
// Se usuário digitou: <img src=x onerror="alert('hacked!')">
// Será exibido como: &lt;img src=x onerror=&quot;alert('hacked!')&quot;&gt;
echo htmlspecialchars($usuario['nome']); // Seguro!
```

### ✅ Proteção de Dados

- [x] **Transações:** `beginTransaction()` / `commit()` / `rollBack()` para integridade
- [x] **Cascade Deletes:** Quando usuário é deletado, todas suas pranchas/sessões também
- [x] **Foreign Keys:** Relacionamentos garantidos no nível de BD
- [x] **Soft Deletes Parciais:** Para pranchas, desvincula sessões antes de deletar

### ✅ Validação de Entrada

```php
// Email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
// Apenas emails válidos: user@domain.com

// Inteiros
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// Apenas números inteiros

// Texto Seguro
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
// Remove caracteres perigosos
```

### ✅ Charset UTF-8

- [x] **Conexão:** `charset=utf8mb4` em DSN
- [x] **Tabelas:** `CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`
- [x] **Suporte:** Emojis, acentos, caracteres especiais funcionam corretamente

---

## 🚨 VULNERABILIDADES POSSÍVEIS E COMO EVITAR

### 1. SQL Injection
**Risco:** Alterar query durante execução
```php
// ❌ PERIGOSO
$email = $_POST['email']; // Poderia ser: admin' OR '1'='1
$stmt = $pdo->query("SELECT * FROM usuarios WHERE email = '$email'");

// ✅ SEGURO
$email = $_POST['email'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]); // Parâmetro isolado
```

### 2. XSS (Cross-Site Scripting)
**Risco:** Injetar JavaScript malicioso
```php
// ❌ PERIGOSO
$nome = $_POST['nome']; // Poderia ser: <script>alert('hacked')</script>
echo "<p>Bem-vindo, $nome!</p>";

// ✅ SEGURO
$nome = $_POST['nome'];
echo "<p>Bem-vindo, " . htmlspecialchars($nome) . "!</p>";
```

### 3. Force Browse (Acesso Direto)
**Risco:** Acessar páginas admin sem permissão
```php
// ✅ IMPLEMENTADO
// Todas as páginas admin verificam:
if (!$user_atual || $user_atual['is_admin'] != 1) {
    header("Location: dashboard.php");
    exit;
}
```

### 4. Session Hijacking
**Risco:** Alguém copiar ID da sessão
```php
// ✅ MITIGADO (parcialmente)
// - Usar HTTPS em produção (encripta ID da sessão)
// - Usar cookies HttpOnly (impede JavaScript acessar)
// - Usar Secure flag (só enviar via HTTPS)

// No php.ini:
// session.cookie_httponly = 1
// session.cookie_secure = 1 (em produção)
```

### 5. Força Bruta (Múltiplas Tentativas de Login)
**Risco:** Tentar adivinhar senha por tentativa e erro
```php
// ⚠️ NÃO IMPLEMENTADO AINDA
// Solução: Implementar rate limiting
// - Limitar logins por IP
// - Adicionar delay após falhas
// - Registrar tentativas suspeitas
```

### 6. CSRF (Cross-Site Request Forgery)
**Risco:** Formulário de outro site alterar seus dados
```php
// ⚠️ NÃO IMPLEMENTADO AINDA
// Solução: Usar tokens CSRF
// - Gerar token único por formulário
// - Validar token antes de processar POST
```

---

## 🛠️ MANUTENÇÃO RECOMENDADA

### 📊 Monitoramento

#### Checar Espaço em Disco
```sql
-- Ver tamanho das tabelas
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM information_schema.tables
WHERE table_schema = 'surflog';
```

#### Checar Performance de Queries
```sql
-- Queries mais lentas
SELECT * FROM slow_query_log;

-- Criar índices se necessário
ALTER TABLE sessoes ADD INDEX idx_usuario_data (usuario_id, data_sessao);
```

#### Monitorar Usuários e Dados
```sql
-- Total de usuários
SELECT COUNT(*) as total_usuarios FROM usuarios;

-- Total de sessões
SELECT COUNT(*) as total_sessoes FROM sessoes;

-- Quantidade de dados
SELECT 
    (SELECT COUNT(*) FROM usuarios) as usuarios,
    (SELECT COUNT(*) FROM pranchas) as pranchas,
    (SELECT COUNT(*) FROM sessoes) as sessoes;
```

### 🔄 Backups

#### Backup Manual (Windows)
```bash
:: Criar backup em arquivo SQL
mysqldump -u root surflog > backup_surflog_2026-06-09.sql

:: Restaurar de backup
mysql -u root surflog < backup_surflog_2026-06-09.sql
```

#### Backup Automático (Programado)
```batch
:: Criar arquivo batch: backup.bat
@echo off
set data=%date:~-4%%date:~-10,2%%date:~-7,2%
mysqldump -u root surflog > C:\backups\surflog_%data%.sql
echo Backup completado em: %data%
```

### 🧹 Limpeza de Dados

#### Remover Usuários Inativos
```sql
-- Usuários que não adicionaram sessão há 6 meses
SELECT * FROM usuarios 
WHERE id NOT IN (
    SELECT DISTINCT usuario_id FROM sessoes 
    WHERE data_sessao > DATE_SUB(NOW(), INTERVAL 6 MONTH)
);
```

#### Remover Pranchas Não Utilizadas
```sql
-- Pranchas que nunca foram usadas em sessão
SELECT p.* FROM pranchas p
LEFT JOIN sessoes s ON p.id = s.prancha_id
WHERE s.id IS NULL;
```

### 📝 Logs

#### Criar Tabela de Logs
```sql
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100),
    descricao TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

#### Registrar Ações (em admin_acoes.php)
```php
// Após deletar usuário:
$stmt_log = $pdo->prepare("
    INSERT INTO logs (usuario_id, acao, descricao, ip_address) 
    VALUES (?, ?, ?, ?)
");
$stmt_log->execute([
    $_SESSION['usuario_id'],
    'delete_user',
    "Deletou usuário ID: $user_id",
    $_SERVER['REMOTE_ADDR']
]);
```

---

## 🚀 MELHORIAS FUTURAS

### Curto Prazo (1-3 meses)
1. [ ] Rate limiting para login (evitar força bruta)
2. [ ] CSRF tokens em formulários
3. [ ] Logging de ações do admin
4. [ ] Email de confirmação para novo cadastro
5. [ ] Recuperação de senha (reset via email)

### Médio Prazo (3-6 meses)
1. [ ] Separar backend (API) do frontend
2. [ ] Autenticação via JWT tokens
3. [ ] Two-Factor Authentication (2FA)
4. [ ] Exportar dados em CSV/PDF
5. [ ] Filtros avançados no admin

### Longo Prazo (6-12 meses)
1. [ ] Migrar para framework (Laravel/Symfony)
2. [ ] GraphQL API
3. [ ] Mobile app (React Native)
4. [ ] Analytics dashboard
5. [ ] Integração com redes sociais

---

## 🔍 TESTE DE SEGURANÇA MANUAL

### 1. Teste SQL Injection
```
❌ Abra: index.php
❌ No campo email, digite: admin@email.com' OR '1'='1
❌ Se conseguir logar sem senha correta = VULNERÁVEL
✅ Deve recusar com "E-mail ou senha incorretos"
```

### 2. Teste XSS
```
❌ Crie uma prancha com modelo: <img src=x onerror="alert('XSS')">
❌ Se aparecer alert = VULNERÁVEL
✅ Deve exibir como texto (com < e > escapados)
```

### 3. Teste Force Browse
```
❌ Sem estar logado, acesse: admin.php
❌ Se conseguir ver a página = VULNERÁVEL
✅ Deve redirecionar para index.php
```

### 4. Teste de Permissão
```
❌ Sendo usuário comum, acesse: admin.php
❌ Se conseguir ver a página = VULNERÁVEL
✅ Deve redirecionar para dashboard.php
```

### 5. Teste Session Hijacking (básico)
```
1. Faça login
2. Abra DevTools (F12) → Application → Cookies
3. Copie o valor de PHPSESSID
4. Em outra aba, tente usar esse ID
❌ Se conseguir = requer HTTPS em produção
✅ HTTPS encriptará a transmissão
```

---

## 📞 SUPORTE E TROUBLESHOOTING

### Erro: "Este e-mail já está cadastrado"
- **Causa:** Email já existe no banco
- **Solução:** Use outro email ou faça login com esse email

### Erro: "E-mail ou senha incorretos"
- **Causa:** Email não existe OU senha errada
- **Solução:** Verifique se email existe; se esquecer senha, reset (não implementado)

### Erro: "SQLSTATE[HY000]: General error"
- **Causa:** Falha de conexão com banco de dados
- **Solução:** Verifique se MySQL está rodando, confirme dados em `config/conexao.php`

### Erro: "Class 'PDO' not found"
- **Causa:** PHP não tem extensão PDO ativa
- **Solução:** Abra `php.ini`, procure por `extension=pdo_mysql`, remova `;`

### Página em branco
- **Causa:** Erro fatal de PHP
- **Solução:** Ative error reporting em `php.ini`: `display_errors = On`

---

## 📋 RESUMO DE SEGURANÇA

| Aspecto | Status | Implementado | Próximos Passos |
|---------|--------|--------------|-----------------|
| Autenticação | ✅ | Bcrypt + Sessões | 2FA |
| SQL Injection | ✅ | Prepared Statements | Validação extra |
| XSS | ✅ | htmlspecialchars() | CSP Headers |
| CSRF | ⚠️ | Não implementado | Tokens CSRF |
| Rate Limiting | ⚠️ | Não implementado | Implementar IP-based |
| Logs | ⚠️ | Não implementado | Tabela de logs |
| HTTPS | ⚠️ | Não recomendado (dev) | Ativar em produção |
| Backup | ⚠️ | Manual | Automático |

---

**Última Atualização:** 2026-06-09  
**Versão:** 1.0  
**Status:** Pronto para Desenvolvimento / Requer Hardening para Produção  

