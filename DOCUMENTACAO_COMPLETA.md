# 🌊 SurfLog - Documentação Completa do Projeto

---

## 📋 Índice
1. [Visão Geral](#visão-geral)
2. [Arquitetura](#arquitetura)
3. [Estrutura de Pastas](#estrutura-de-pastas)
4. [Arquivos PHP](#arquivos-php)
5. [Banco de Dados](#banco-de-dados)
6. [Fluxo de Usuário](#fluxo-de-usuário)
7. [Segurança](#segurança)

---

## 🎯 Visão Geral

**SurfLog** é uma aplicação web para logging de sessões de surf. Permite que surfistas registrem e acompanhem:
- Sessões de surf (data, localização, duração, nota)
- Pranchas de surf utilizadas
- Condições do mar (altura e período das ondas)
- Estatísticas e recordes pessoais
- Sistema de administração para gerenciar usuários

**Stack Tecnológico:**
- **Backend:** PHP 7.4+
- **Banco de Dados:** MySQL
- **Frontend:** HTML5 + CSS3 (Glassmorphism Design)
- **Segurança:** PDO Prepared Statements, Password Hashing (bcrypt)

---

## 🏗️ Arquitetura

### Modelo MVC Simplificado
```
Views (HTML/CSS)
    ↓
Controllers (PHP - Lógica)
    ↓
Models (Banco de Dados - MySQL)
```

### Camadas:
1. **Camada de Apresentação:** Arquivos PHP com HTML/CSS embutido
2. **Camada de Lógica:** Queries e processamento de dados em PHP
3. **Camada de Dados:** Tabelas MySQL (usuarios, pranchas, sessoes)

---

## 📁 Estrutura de Pastas

```
surf_logbook/
│
├── index.php                    # Login e Cadastro
├── dashboard.php                # Página Principal (Dashboard do Usuário)
├── logout.php                   # Logout
│
├── admin.php                    # Painel Principal de Admin
├── admin_acoes.php              # Ações de Admin (promover, deletar)
├── admin_pranchas.php           # Gerenciar Pranchas de Usuário
├── admin_sessoes.php            # Gerenciar Sessões de Usuário
│
├── salvar_prancha.php           # Salvar Nova Prancha
├── salvar_sessao.php            # Salvar Nova Sessão
│
├── config/
│   └── conexao.php              # Conexão com Banco de Dados
│
├── css/
│   └── style.css                # Estilos CSS (Vazio - CSS inline no PHP)
│
├── js/
│   └── script.js                # Scripts JavaScript (Vazio - JS inline no PHP)
│
├── db/
│   └── script.sql               # Script SQL (Vazio - Estrutura do BD)
│
├── img/
│   └── login_img.avif           # Imagem de Fundo do Login
│
└── DOCUMENTACAO_COMPLETA.md     # Este arquivo
```

---

## 🔧 Arquivos PHP

### 1️⃣ **index.php** - Login e Cadastro

**Função:** Página de entrada da aplicação. Autentica usuários existentes ou cria novos accounts.

**Fluxo:**
- Se usuário já está logado → Redireciona para dashboard.php
- Formulário com duas abas: **Login** e **Sign Up**

**Operações:**
- **Cadastro:** Valida nome/email, faz hash da senha, insere na tabela `usuarios`
- **Login:** Busca usuário por email, verifica senha com `password_verify()`, cria sessão

**Segurança:**
- `filter_input()` para validar email
- `password_hash()` com algoritmo bcrypt
- PDO prepared statements (proteção SQL Injection)
- Tratamento de exceções PDO

---

### 2️⃣ **dashboard.php** - Página Principal do Usuário

**Função:** Hub central após login. Mostra estatísticas, pranchas e histórico de sessões.

**Fluxo:**
1. Valida se usuário está logado
2. Busca 4 tipos de dados:
   - Estatísticas gerais (total sessões, tempo, nota média)
   - Recordes (maior sessão, prancha favorita, maior onda)
   - Lista de pranchas
   - Histórico de sessões

**Queries SQL:**
- `COUNT()` e `SUM()` para agregações
- `MAX()` para recordes
- `GROUP BY` e `COUNT()` para prancha mais usada
- `LEFT JOIN` para combinar sessões com pranchas

**Recursos de UI:**
- 6 cards de widgets com estatísticas
- Seção de pranchas com botão "+ Add board"
- Seção de histórico com detalhes de cada sessão
- Modais flutuantes para adicionar pranchas e sessões

**Modal de Prancha:**
- Campos: Modelo/Marca, Medidas/Volume
- Chama POST para `salvar_prancha.php`

**Modal de Sessão:**
- Campos: Data, Duração, Localização (estado/cidade/praia)
- Campos opcionais: Prancha, Altura da Onda, Período, Nota, Observações
- Chama POST para `salvar_sessao.php`

---

### 3️⃣ **config/conexao.php** - Conexão com Banco de Dados

**Função:** Centraliza configuração e conexão PDO com MySQL.

**Variáveis de Configuração:**
```php
$host = 'localhost';      // Servidor MySQL
$db   = 'surflog';        // Nome do banco
$user = 'root';           // Usuário XAMPP padrão
$pass = '';               // Senha XAMPP padrão (vazia)
$charset = 'utf8mb4';     // Suporta caracteres especiais
```

**Opções PDO:**
- `ERRMODE_EXCEPTION` → Lança exceções em erros
- `FETCH_ASSOC` → Retorna arrays associativos
- `EMULATE_PREPARES = false` → Prepared statements reais

**Importância:** Importado por `require_once` em praticamente todos os arquivos.

---

### 4️⃣ **logout.php** - Encerrar Sessão

**Função:** Limpa a sessão e redireciona para login.

**Operações:**
1. `session_start()` - Inicia sessão
2. `session_unset()` - Remove todas as variáveis de sessão
3. `session_destroy()` - Destrói a sessão
4. `header("Location: index.php")` - Redireciona para login

---

### 5️⃣ **salvar_prancha.php** - Salvar Nova Prancha

**Função:** Recebe dados do modal de prancha e insere no banco.

**Fluxo:**
1. Verifica se usuário está logado
2. Valida se formulário é POST
3. Obtém dados: `modelo`, `medidas`
4. Insere em `pranchas` com ID do usuário logado
5. Redireciona para `dashboard.php`

**Segurança:**
- Verifica autenticação
- PDO prepared statement (proteção SQL Injection)

---

### 6️⃣ **salvar_sessao.php** - Salvar Nova Sessão de Surf

**Função:** Recebe dados do modal de sessão e insere no banco.

**Campos Capturados:**
- Obrigatórios: `data_sessao`, `duracao_minutos`
- Localização: `estado`, `cidade`, `praia`
- Opcionais: `prancha_id`, `altura_onda`, `periodo_onda`
- Nota: `nota` (0.5 a 5.0)
- Observações: `observacoes` (texto livre)

**Tipagem:**
- `intval()` para durações e períodos
- `floatval()` para altura de onda e nota
- `!empty()` para campos opcionais

**Fluxo:**
1. Valida autenticação
2. Obtém dados do POST
3. Insere em tabela `sessoes` com 11 campos
4. Redireciona para `dashboard.php`

---

### 7️⃣ **admin.php** - Painel Principal de Admin

**Função:** Painel de controle para administradores. Lista TODOS os usuários com opções de gerenciamento.

**Proteções:**
- Verifica se usuário está logado
- Valida se `is_admin = 1`

**Dados Exibidos (Tabela):**
- Nome, Email, Nível (Admin/Comum)
- Botões para: Ver Pranchas, Ver Sessões, Promover/Rebaixar, Deletar

**Ações Disponíveis:**
- 🏄‍♂️ **Ver Pranchas** → `admin_pranchas.php?usuario_id=X`
- 🌊 **Ver Sessões** → `admin_sessoes.php?usuario_id=X`
- 👑 **Promover/Rebaixar** → `admin_acoes.php?action=toggle_role&id=X`
- 🗑️ **Deletar Usuário** → `admin_acoes.php?action=delete_user&id=X`

---

### 8️⃣ **admin_acoes.php** - Ações de Administração

**Função:** Executa ações específicas do admin via GET parameters.

**Ações Suportadas:**

#### Ação 1: `toggle_role`
- Alterna status do usuário entre Admin e Comum
- URL: `admin_acoes.php?action=toggle_role&id=123`
- Operação: `UPDATE usuarios SET is_admin = 0/1 WHERE id = ?`

#### Ação 2: `delete_user`
- Deleta usuário E todos seus dados (pranchas, sessões)
- URL: `admin_acoes.php?action=delete_user&id=123`
- Usa **transação** para garantir integridade:
  1. DELETE FROM sessoes WHERE usuario_id = ?
  2. DELETE FROM pranchas WHERE usuario_id = ?
  3. DELETE FROM usuarios WHERE id = ?
  4. Se erro → ROLLBACK (desfaz tudo)
  5. Se OK → COMMIT (confirma tudo)

**Segurança:** Dupla verificação de admin antes de qualquer ação.

---

### 9️⃣ **admin_pranchas.php** - Gerenciar Pranchas

**Função:** Lista TODAS as pranchas de um usuário específico.

**Fluxo:**
1. Valida se admin está logado
2. Obtém `usuario_id` da URL
3. Busca e exibe pranchas em cards
4. Permite deletar pranchas individualmente

**Deleção de Prancha:**
- Primeiro coloca `NULL` no `prancha_id` de todas as sessões que usavam
- Depois deleta a prancha
- Usa transação para garantir consistência

**Proteção:** Verifica `usuario_id` do admin antes de deletar (dupla segurança).

---

### 🔟 **admin_sessoes.php** - Gerenciar Sessões

**Função:** Mostra histórico COMPLETO de sessões de um usuário.

**Fluxo:**
1. Valida se admin está logado
2. Obtém `usuario_id` da URL
3. Busca todas as sessões em tabela
4. Exibe: Data, Localização, Duração, Prancha, Nota

**Campos Exibidos:**
- Data (formatada: DD/MM/YYYY)
- Localização (Praia - Estado)
- Duração em minutos
- Modelo da prancha (ou "Nenhuma" se deletada)
- Nota com estrela (⭐)
- Botão de exclusão

**Deleção:** Simples - DELETE direto (sem dependências pois sessão é última na cadeia).

---

## 🗄️ Banco de Dados

### Estrutura das Tabelas

#### Tabela: `usuarios`
```sql
CREATE TABLE usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) UNIQUE NOT NULL,
    senha         VARCHAR(255) NOT NULL,
    is_admin      TINYINT(1) DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Descrição:**
- `id` - Identificador único do usuário
- `nome` - Nome completo
- `email` - Email único (não pode repetir)
- `senha` - Hash bcrypt da senha
- `is_admin` - Flag 0=Comum, 1=Admin
- `created_at` - Data de criação da conta

---

#### Tabela: `pranchas`
```sql
CREATE TABLE pranchas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    modelo      VARCHAR(255),
    medidas     VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

**Descrição:**
- `id` - Identificador único da prancha
- `usuario_id` - Foreign Key para usuários
- `modelo` - Marca/modelo da prancha (ex: "Simões Funboard")
- `medidas` - Dimensões e volume (ex: "7.11 X 21 X 52L")
- `created_at` - Data de cadastro

---

#### Tabela: `sessoes`
```sql
CREATE TABLE sessoes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    prancha_id      INT,
    data_sessao     DATE NOT NULL,
    duracao_minutos INT,
    nota            FLOAT,
    estado          VARCHAR(100),
    cidade          VARCHAR(100),
    praia           VARCHAR(255),
    altura_onda     FLOAT,
    periodo_onda    INT,
    observacoes     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (prancha_id) REFERENCES pranchas(id) ON DELETE SET NULL
);
```

**Descrição:**
- `id` - Identificador único da sessão
- `usuario_id` - Foreign Key para usuários
- `prancha_id` - Foreign Key para pranchas (pode ser NULL)
- `data_sessao` - Data da sessão de surf
- `duracao_minutos` - Tempo em minutos
- `nota` - Avaliação pessoal (1-5)
- `estado`, `cidade`, `praia` - Localização
- `altura_onda` - Altura em metros (NULL se não informado)
- `periodo_onda` - Período em segundos (NULL se não informado)
- `observacoes` - Texto livre
- `created_at` - Data de criação do registro

---

## 🔄 Fluxo de Usuário

### 1. Novo Usuário
```
index.php (Sign Up)
    ↓
[Preenche: Nome, Email, Senha]
    ↓
Valida email/nome
    ↓
Hash da senha (bcrypt)
    ↓
INSERT INTO usuarios
    ↓
"Conta criada com sucesso! Faça o login."
```

### 2. Login
```
index.php (Login)
    ↓
[Preenche: Email, Senha]
    ↓
SELECT * FROM usuarios WHERE email = ?
    ↓
password_verify(senha, hash)
    ↓
Cria $_SESSION['usuario_id'] e ['usuario_nome']
    ↓
Redireciona para dashboard.php
```

### 3. Usar Dashboard
```
dashboard.php
    ↓
[Ver estatísticas, pranchas, histórico]
    ↓
Clica "+ Add board" ou "+ New session"
    ↓
Modal se abre
    ↓
Preenche formulário
    ↓
Form POST para salvar_prancha.php ou salvar_sessao.php
    ↓
INSERT INTO banco de dados
    ↓
Redireciona de volta para dashboard.php
    ↓
[Dados atualizados aparecem na página]
```

### 4. Admin Gerencia Usuário
```
admin.php
    ↓
[Lista de usuários]
    ↓
Clica em "Pranchas", "Sessões", "Promover", ou "Deletar"
    ↓
Se "Pranchas" → admin_pranchas.php?usuario_id=X
Se "Sessões"  → admin_sessoes.php?usuario_id=X
Se "Promover" → admin_acoes.php?action=toggle_role&id=X
Se "Deletar"  → admin_acoes.php?action=delete_user&id=X
    ↓
Ação é executada
    ↓
Redireciona de volta
```

---

## 🔒 Segurança

### 1. Autenticação
- ✅ Password Hashing: `password_hash($senha, PASSWORD_DEFAULT)` (bcrypt)
- ✅ Verificação: `password_verify($senha_digitada, $hash_armazenado)`
- ✅ Sessões: `$_SESSION` com id e nome do usuário
- ✅ Proteção de rotas: Verificação de `isset($_SESSION['usuario_id'])` em cada página

### 2. Proteção contra SQL Injection
- ✅ PDO Prepared Statements: `$pdo->prepare()` com placeholders `?`
- ✅ Separação de código e dados
- ✅ Exemplos:
  ```php
  // ✅ SEGURO
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
  $stmt->execute([$id]);
  
  // ❌ INSEGURO (evitar sempre)
  $resultado = $pdo->query("SELECT * FROM usuarios WHERE id = $id");
  ```

### 3. Validação de Entrada
- ✅ `filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)`
- ✅ `filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS)`
- ✅ `filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)`
- ✅ Validações de tipo: `intval()`, `floatval()`

### 4. Proteção de Dados do Admin
- ✅ Dupla verificação de permissão (`is_admin = 1`)
- ✅ Verificação de `usuario_id` ao deletar dados
- ✅ Transações para operações críticas (DELETE múltiplas)

### 5. Proteção HTML (XSS)
- ✅ `htmlspecialchars()` ao exibir dados do usuário
- ✅ Previne injeção de scripts via formulários

### 6. Sessões Seguras
- ✅ `session_start()` no início das páginas
- ✅ `session_destroy()` no logout
- ✅ Verificação de sessão ativa em cada página protegida

---

## 📊 Diagrama de Relacionamentos

```
usuarios (1) ─── (N) pranchas
   │
   └─── (N) sessoes

pranchas (1) ─── (N) sessoes
```

- Um usuário pode ter muitas pranchas
- Um usuário pode ter muitas sessões
- Uma prancha pode ser usada em muitas sessões
- Uma sessão usa zero ou uma prancha

---

## 🚀 Como Usar

### Preparação
1. Criar banco de dados `surflog` no MySQL
2. Importar estrutura de tabelas
3. Configurar XAMPP em `config/conexao.php`

### Primeiro Acesso
1. Ir para `http://localhost/surf_logbook/index.php`
2. Criar nova conta (Sign Up)
3. Fazer login
4. Acessar dashboard

### Promover Admin
1. Criar conta normal
2. Via phpMyAdmin ou query direto: `UPDATE usuarios SET is_admin = 1 WHERE email = 'seu@email.com'`
3. Fazer logout e login novamente
4. Agora pode acessar `admin.php`

---

## 📝 Resumo das Funcionalidades

| Função | Arquivo | Tipo | Acesso |
|--------|---------|------|--------|
| Login/Cadastro | index.php | HTML+PHP | Público |
| Dashboard | dashboard.php | HTML+PHP | Autenticado |
| Logout | logout.php | PHP | Autenticado |
| Adicionar Prancha | salvar_prancha.php | PHP | Autenticado |
| Adicionar Sessão | salvar_sessao.php | PHP | Autenticado |
| Painel Admin | admin.php | HTML+PHP | Admin |
| Gerenciar Pranchas | admin_pranchas.php | HTML+PHP | Admin |
| Gerenciar Sessões | admin_sessoes.php | HTML+PHP | Admin |
| Ações Admin | admin_acoes.php | PHP | Admin |
| Conexão BD | config/conexao.php | PHP | Sistema |

---

## 🎨 Design Patterns

### Usado
- ✅ MVC Simplificado (View + Controller no mesmo arquivo)
- ✅ Prepared Statements (Data Access Pattern)
- ✅ Transactions (Database Pattern)
- ✅ Middleware-like Verification (Security Pattern)
- ✅ Session Management (Authentication Pattern)

### Poderia Melhorar
- ⚠️ Separar HTML do PHP (usar Views e Templates)
- ⚠️ Usar Framework (Laravel, Symfony)
- ⚠️ Implementar Repository Pattern
- ⚠️ Adicionar Logging e Monitoring
- ⚠️ API RESTful em vez de POST/GET direto

---

**Versão:** 1.0  
**Última Atualização:** 2026-06-09  
**Autor:** Desenvolvedor SurfLog  

---
