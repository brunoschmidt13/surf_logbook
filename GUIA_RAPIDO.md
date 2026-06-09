# 🔍 GUIA RÁPIDO DE REFERÊNCIA - SurfLog

## 📌 Arquivo-por-Arquivo (Resumo Executivo)

---

### ⚙️ **SISTEMA & CONFIGURAÇÃO**

#### `config/conexao.php`
```php
// ✅ Conecta ao MySQL usando PDO
// Credenciais: localhost:root (sem senha)
// Banco: surflog
// Charset: utf8mb4 (suporta emojis/acentos)

// Importante: Este arquivo é importado por QUASE TODOS os outros!
require_once 'config/conexao.php';
```

---

### 🔐 **AUTENTICAÇÃO**

#### `index.php`
```php
// ✅ Página de Login e Cadastro
// - TAB 1: Login (email + senha)
// - TAB 2: Sign Up (nome + email + senha)

// Validações:
// - Email: FILTER_VALIDATE_EMAIL
// - Nome: FILTER_SANITIZE_SPECIAL_CHARS
// - Senha: password_hash(pwd, PASSWORD_DEFAULT) = bcrypt

// Fluxo Login:
if ($usuario && password_verify($senha, $usuario['senha'])) {
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    header("Location: dashboard.php");
}
```

#### `logout.php`
```php
// ✅ Limpa a sessão
session_unset();       // Remove variáveis
session_destroy();     // Destrói cookie
header("Location: index.php");
```

---

### 👤 **USUÁRIO LOGADO**

#### `dashboard.php`
```php
// ✅ Hub Principal após login
// - Mostra 6 cards de estatísticas
// - Lista pranchas
// - Histórico de sessões
// - Modais para adicionar dados

// Queries Principais:
COUNT(*)              // Total de sessões
SUM(duracao_minutos)  // Tempo total na água
AVG(nota)             // Nota média
MAX(duracao_minutos)  // Sessão mais longa
MAX(altura_onda)      // Maior onda
GROUP BY (prancha_id) // Prancha mais usada
```

#### `salvar_prancha.php`
```php
// ✅ Recebe POST do modal de proncha
// Campos: modelo, medidas
// Insere: INSERT INTO pranchas (usuario_id, modelo, medidas)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO pranchas (...) VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, $modelo, $medidas]);
}
header("Location: dashboard.php"); // Redireciona
```

#### `salvar_sessao.php`
```php
// ✅ Recebe POST do modal de sessão
// Campos Obrigatórios: data_sessao, duracao_minutos
// Campos Opcionais: prancha_id, altura_onda, periodo_onda, nota, observacoes

// Conversões:
intval($_POST['duracao_minutos'])     // Para inteiro
floatval($_POST['altura_onda'])       // Para float
!empty($_POST['prancha_id']) ? intval(...) : null  // Opcional

// Insere: INSERT INTO sessoes (11 campos)
```

---

### 👑 **ADMINISTRADOR**

#### `admin.php`
```php
// ✅ Painel Principal de Admin
// Proteção: if (!$user_atual || $user_atual['is_admin'] != 1) REJEITA

// Mostra:
// - Tabela de TODOS os usuários
// - Botões: Ver Pranchas, Ver Sessões, Promover, Deletar

// Links de Ação:
admin_pranchas.php?usuario_id=X       // Ver pranchas de X
admin_sessoes.php?usuario_id=X        // Ver sessões de X
admin_acoes.php?action=toggle_role&id=X  // Promover/Rebaixar
admin_acoes.php?action=delete_user&id=X  // Deletar usuário
```

#### `admin_acoes.php`
```php
// ✅ Executa ações do admin

// AÇÃO 1: toggle_role
if ($action === 'toggle_role') {
    $novo_cargo = $target_user['is_admin'] == 1 ? 0 : 1;
    $update->execute([$novo_cargo, $user_id]); // Alterna 0→1 ou 1→0
}

// AÇÃO 2: delete_user (COM TRANSAÇÃO!)
$pdo->beginTransaction();  // Começa transação
try {
    DELETE FROM sessoes WHERE usuario_id = ?
    DELETE FROM pranchas WHERE usuario_id = ?
    DELETE FROM usuarios WHERE id = ?
    $pdo->commit();  // Se tudo OK, confirma
} catch {
    $pdo->rollBack();  // Se erro, desfaz tudo!
}
```

#### `admin_pranchas.php`
```php
// ✅ Lista pranchas de um usuário
// Proteção: Valida usuario_id da URL

// Deleção de Prancha:
UPDATE sessoes SET prancha_id = NULL WHERE prancha_id = ?  // Desvincular
DELETE FROM pranchas WHERE id = ? AND usuario_id = ?       // Deletar

// Usa transação para não deixar dados órfãos
```

#### `admin_sessoes.php`
```php
// ✅ Mostra histórico de sessões de um usuário

// Query com JOIN:
SELECT s.*, p.modelo AS prancha_nome 
FROM sessoes s 
LEFT JOIN pranchas p ON s.prancha_id = p.id
WHERE s.usuario_id = ? 
ORDER BY s.data_sessao DESC

// Deleção simples: DELETE FROM sessoes WHERE id = ? AND usuario_id = ?
```

---

## 🗄️ BANCO DE DADOS - 3 Tabelas

### 1️⃣ **usuarios**
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | PK, Auto Increment |
| nome | VARCHAR(255) | Nome completo |
| email | VARCHAR(255) | UNIQUE |
| senha | VARCHAR(255) | Hash bcrypt |
| is_admin | TINYINT(1) | 0=Comum, 1=Admin |

### 2️⃣ **pranchas**
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | PK |
| usuario_id | INT | FK para usuarios |
| modelo | VARCHAR(255) | Marca/modelo |
| medidas | VARCHAR(255) | Dimensões |

### 3️⃣ **sessoes**
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | PK |
| usuario_id | INT | FK para usuarios |
| prancha_id | INT | FK para pranchas (pode ser NULL) |
| data_sessao | DATE | Data do surf |
| duracao_minutos | INT | Tempo na água |
| nota | FLOAT | Avaliação (1-5) |
| estado | VARCHAR | Ex: "SC" |
| cidade | VARCHAR | Ex: "Imbituba" |
| praia | VARCHAR | Ex: "Praia do Rosa" |
| altura_onda | FLOAT | Em metros |
| periodo_onda | INT | Em segundos |
| observacoes | TEXT | Notas livres |

---

## 🔒 CHECKLIST DE SEGURANÇA

- ✅ **Senhas:** Hashadas com bcrypt (`PASSWORD_DEFAULT`)
- ✅ **SQL Injection:** Prevenido com Prepared Statements e placeholders `?`
- ✅ **Autenticação:** Verificação de `$_SESSION['usuario_id']` em páginas protegidas
- ✅ **Admin:** Dupla verificação (`isset && is_admin == 1`)
- ✅ **XSS:** Uso de `htmlspecialchars()` ao exibir dados
- ✅ **Integridade:** Transações em operações críticas (DELETE múltiplas)
- ✅ **Validação:** `filter_input()` para email e entrada de dados

---

## 📋 FLUXO DE REQUISIÇÃO TÍPICA

```
1. Usuário acessa index.php
                    ↓
2. Preenche formulário (POST)
                    ↓
3. PHP processa dados
   - Valida entrada
   - Query ao banco (prepared statement)
   - Cria/modifica dados
                    ↓
4. Redireciona para próxima página (header)
                    ↓
5. Browser recarrega nova URL
                    ↓
6. Nova página exibe dados atualizados
```

---

## 🎯 CASOS DE USO COMUNS

### Criar Conta Nova
1. Usuário vai a `index.php` → Clica "Sign Up"
2. Preenche: nome, email, senha
3. Clica "Create Account"
4. `index.php` recebe POST com `acao = 'cadastrar'`
5. Valida dados, faz hash da senha
6. `INSERT INTO usuarios` → sucesso!
7. Mostra mensagem "Conta criada"

### Fazer Login
1. Usuário vai a `index.php` → Clica "Log In" (default)
2. Preenche: email, senha
3. Clica "Log In"
4. `index.php` recebe POST com `acao = 'login'`
5. Busca usuário por email
6. Verifica senha com `password_verify()`
7. Cria `$_SESSION` → Redireciona para `dashboard.php`

### Adicionar Prancha
1. Usuário em `dashboard.php` clica "+ Add board"
2. Modal aparece
3. Preenche: modelo, medidas
4. Clica "Salvar Prancha"
5. Form POST para `salvar_prancha.php`
6. `salvar_prancha.php` → `INSERT INTO pranchas`
7. Redireciona de volta a `dashboard.php`
8. Nova prancha aparece na listagem

### Admin Deletar Usuário
1. Admin em `admin.php` encontra usuário
2. Clica "🗑️ Excluir Usuário"
3. Confirmação: "Apagar permanentemente?"
4. Redireciona para `admin_acoes.php?action=delete_user&id=123`
5. `admin_acoes.php` inicia TRANSAÇÃO:
   - DELETE sessoes
   - DELETE pranchas
   - DELETE usuario
   - COMMIT (se tudo OK) ou ROLLBACK (se erro)
6. Redireciona de volta a `admin.php`
7. Usuário sumiu da tabela

---

## ⚠️ PONTOS CRÍTICOS

1. **Prepared Statements SEMPRE:** Usar `?` placeholders, nunca `$variavel` direto
2. **Sessão em Todas as Páginas:** Verificar `isset($_SESSION['usuario_id'])` logo no início
3. **Admin Check:** Verificar `is_admin == 1` antes de operações sensíveis
4. **Transações para DELETE Múltiplas:** Não deixar dados órfãos
5. **htmlspecialchars():** Ao exibir dados do usuário (XSS)
6. **Email UNIQUE:** Evitar cadastros duplicados
7. **Foreign Keys:** Respeitar relacionamentos (ON DELETE SET NULL)

---

## 🚀 DEPLOYMENT

### Pré-requisitos
- PHP 7.4+
- MySQL 5.7+
- Apache (ou XAMPP)

### Setup Checklist
1. [ ] Criar banco `surflog`
2. [ ] Importar script SQL com tabelas
3. [ ] Configurar `config/conexao.php` (host, user, pass)
4. [ ] Testar conexão (verifica se $pdo conecta)
5. [ ] Criar primeira conta (Sign Up em index.php)
6. [ ] Promover à admin (UPDATE usuarios SET is_admin = 1)
7. [ ] Testar admin panel

---

## 🐛 DEBUG TIPS

```php
// Ver valor de variável
var_dump($variavel);

// Ver última query SQL executada
echo "SELECT * FROM usuarios WHERE email = " . $email;

// Ver erro de conexão
catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

// Ver dados da sessão
var_dump($_SESSION);

// Verificar se query retornou dados
if ($stmt->rowCount() > 0) { ... }
```

---

**Última Atualização:** 2026-06-09  
**Versão:** 1.0  
