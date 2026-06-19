<?php
/**
 * FILE: idiomas.php
 * PURPOSE: Multi-language translator engine (EN/PT-BR) for SurfLog
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define o idioma padrão se não houver um na sessão (Padrão: Inglês)
$lang = $_SESSION['lang'] ?? 'en';

// Banco de dados de termos traduzidos do SurfLog
$textos = [
    'pt' => [
        // --- Navegação & Estrutura Geral ---
        'Log out ↗' => 'Sair ↗',
        'Admin Panel 👑' => 'Painel Admin 👑',
        'Back to Dashboard' => 'Voltar para o Dashboard',
        '← Back to Panel' => '← Voltar ao Painel',

        // --- index.php (Gateway de Login / Cadastro) ---
        '🏄‍♂️ SurfLog Gateway' => '🏄‍♂️ Portal SurfLog',
        'Sign In' => 'Entrar',
        'Register' => 'Cadastrar',
        'Password Recovery' => 'Recuperação de Senha',
        'New Password Setup' => 'Configuração de Nova Senha',
        'Email Address' => 'Endereço de E-mail',
        'Your Password' => 'Sua Senha',
        'Forgot password?' => 'Esqueceu a senha?',
        'Full Name' => 'Nome Completo',
        'Choose a Secure Password' => 'Escolha uma Senha Segura',
        'At least 6 characters' => 'Pelo menos 6 caracteres',
        'Confirm Password' => 'Confirme a Senha',
        'Create Account & Surf 🏄‍♂️' => 'Criar Conta & Surfar 🏄‍♂️',
        'Send Recovery Link' => 'Enviar Link de Recuperação',
        'Back to Login' => 'Voltar para o Login',
        'New Password' => 'Nova Senha',
        'Confirm New Password' => 'Confirmar Nova Senha',
        'Update Password' => 'Atualizar Senha',

        // --- Mensagens do Sistema (Erros / Sucessos) ---
        'Database connection failed.' => 'Falha na conexão com o banco de dados.',
        'Invalid e-mail or password.' => 'E-mail ou senha inválidos.',
        'This e-mail is already registered.' => 'Este e-mail já está cadastrado.',
        'Passwords do not match.' => 'As senhas não coincidem.',
        'Registration successful! Please sign in.' => 'Cadastro realizado com sucesso! Por favor, faça o login.',
        'If this email exists, a recovery link was sent!' => 'Se este e-mail existir, um link de recuperação foi enviado!',
        'This recovery token is invalid or has expired. Please request a new link.' => 'Este token de recuperação é inválido ou expirou. Por favor, solicite um novo link.',
        'Password updated successfully! Please log in.' => 'Senha atualizada com sucesso! Por favor, faça o login.',
        'Access denied. Invalid or missing token.' => 'Acesso negado. Token inválido ou ausente.',
        'Choose a new password' => 'Escolha uma nova senha',
        'Please fill required fields (Model and Brand).' => 'Por favor, preencha os campos obrigatórios (Modelo e Marca).',
        'Error saving to database: ' => 'Erro ao salvar no banco de dados: ',
        'You cannot add yourself.' => 'Você não pode adicionar a si mesmo.',
        'User ID not found.' => 'ID de usuário não encontrado.',
        'Friend request sent!' => 'Pedido de amizade enviado!',
        'You are now buddies! Shaka! 🤙' => 'Agora vocês são parceiros de surf! Shaka! 🤙',
        'Friend request declined.' => 'Pedido de amizade recusado.',
        'Friendship removed.' => 'Parceria desfeita.',
        'You must be friends to view this dashboard.' => 'Você precisa ser amigo deste usuário para ver o dashboard dele.',
        'Buddy' => 'Parceiro',

        // --- dashboard.php (Módulos principais) ---
        'What\'s New in SurfLog!' => 'Novidades no SurfLog!',
        'notice_text' => 'E aí, mestre! Acabamos de lançar novas atualizações 🌊🔥<br>🤙 Novo Sistema de Amizades — Adicione seus amigos usando o ID de usuário e monte sua própria surf crew.<br>📊 Dashboard da Crew — Acompanhe as sessões dos seus amigos e veja o progresso da sua galera.<br>🏄‍♂️ Dashboard Comparativa — Compare estatísticas, acompanhe sua evolução e descubra quem está pegando as melhores ondas.<br>Fique conectado, evolua seus limites e aproveite a jornada junto com sua crew. Aloha e boas ondas! 🌊🤙',
        "Got it! Let's surf!" => "Entendido! Vamos surfar!",
        'Welcome back,' => 'Bem-vindo de volta,',
        'Your Personal Surf Logbook & Crew Stats' => 'Seu Diário de Surf Pessoal & Estatísticas da Galera',
        'New Board' => 'Nova Prancha',
        'New Session' => 'Nova Sessão',
        'Your Stats Summary' => 'Resumo de Estatísticas',
        'Total Sessions' => 'Total de Sessões',
        'Water Time' => 'Tempo de Água',
        'Average Score' => 'Nota Média',
        'sessions' => 'sessões',
        'min' => 'min',
        'stars' => 'estrelas',
        'Your Quiver / Boards' => 'Seu Quiver / Pranchas',
        'No boards registered yet. Add your first board to start tracking!' => 'Nenhuma prancha cadastrada ainda. Adicione sua primeira prancha para começar!',
        'Size:' => 'Tamanho:',
        'Volume:' => 'Volume:',
        'Your Surf History' => 'Seu Histórico de Surf',
        'Date' => 'Data',
        'Location' => 'Localização',
        'Duration' => 'Duração',
        'Wave Conditions' => 'Condições da Onda',
        'Board Used' => 'Prancha Usada',
        'Rating' => 'Avaliação',
        'Notes' => 'Notas',
        'Action' => 'Ação',
        'None' => 'Nenhuma',
        'Delete' => 'Excluir',
        'No sessions logged yet. Go catch some waves!' => 'Nenhuma sessão registrada ainda. Vá pegar algumas ondas!',
        'Your Crew & Buddies' => 'Sua Galera & Parceiros',
        'Add Buddy by ID' => 'Adicionar Parceiro por ID',
        'Enter Friend ID...' => 'Digite o ID do amigo...',
        'Add' => 'Adicionar',
        'Pending Requests Received' => 'Pedidos Pendentes Recebidos',
        'wants to be your surf buddy!' => 'quer ser seu parceiro de surf!',
        'Accept 🤙' => 'Aceitar 🤙',
        'Decline ❌' => 'Recusar ❌',
        'Active Surf Crew' => 'Galera Ativa do Surf',
        'Your personal ID to share:' => 'Seu ID pessoal para compartilhar:',
        'Compare' => 'Comparar',
        'Remove' => 'Remover',
        'You don\'t have any surf buddies added yet. Share your ID with friends!' => 'Você não tem parceiros de surf adicionados ainda. Compartilhe seu ID com os amigos!',

        // --- Modais de Cadastro ---
        'Add New Surfboard' => 'Adicionar Nova Prancha',
        'Board Model' => 'Modelo da Prancha',
        'e.g. Monsta 8, Seaside, Pyzalien' => 'ex: Monsta 8, Seaside, Pyzalien',
        'Brand / Shaper' => 'Marca / Shaper',
        'e.g. JS Industries, Firewire, Pyzel' => 'ex: JS Industries, Firewire, Pyzel',
        'Size (Length)' => 'Tamanho (Comprimento)',
        'e.g. 5\'11, 6\'0, 5\'8' => 'ex: 5\'11, 6\'0, 5\'8',
        'Volume (Liters)' => 'Volume (Litros)',
        'e.g. 28.5, 32, 26' => 'ex: 28.5, 32, 26',
        'Save Board 🏄‍♂️' => 'Salvar Prancha 🏄‍♂️',
        'Log New Surf Session' => 'Registrar Nova Sessão de Surf',
        'Session Date' => 'Data da Sessão',
        'Duration (Minutes)' => 'Duração (Minutos)',
        'State / Region' => 'Estado / Região',
        'e.g. California, Rio de Janeiro, Bali' => 'ex: California, Rio de Janeiro, Bali',
        'City' => 'Cidade',
        'e.g. Huntington Beach, Saquarema' => 'ex: Huntington Beach, Saquarema',
        'Beach / Spot Name' => 'Praia / Pico',
        'e.g. Lower Trestles, Itaúna, Uluwatu' => 'ex: Lower Trestles, Itaúna, Uluwatu',
        'Select Board' => 'Selecionar Prancha',
        '-- No board (Barefoot / Bodyboard) --' => '-- Sem prancha (De peito / Bodyboard) --',
        'Wave Height (Meters)' => 'Altura da Onda (Metros)',
        'Placeholder: From 0.5 to 5.0' => 'De 0.5 a 5.0',
        'Wave Period (Seconds)' => 'Período da Onda (Segundos)',
        'Placeholder: From 4 to 20' => 'De 4 a 20',
        'Session Rating' => 'Nota da Sessão',
        'Notes / Diary' => 'Notas / Diário',
        'How was the wind? And the tide? Big barrels?' => 'Como estava o vento? E a maré? Altos tubos?',
        'Log Session' => 'Registrar Sessão',

        // --- amigo.php (Tela de Comparação de Estatísticas) ---
        'Surf Battle Mode ⚔️' => 'Modo Batalha de Surf ⚔️',
        'Comparing performance stats between you and your buddy' => 'Comparando estatísticas de desempenho entre você e seu parceiro',
        'You' => 'Você',
        'Total Sessions Done' => 'Total de Sessões Realizadas',
        'Total Time in Water' => 'Tempo Total na Água',
        'Biggest Wave Conquered' => 'Maior Onda Conquistada',
        'Best Session Score' => 'Melhor Nota de Sessão',
        'Recent Surf History' => 'Histórico de Surf Recente',
        'Has not logged any sessions yet.' => 'Não registrou nenhuma sessão ainda.',

        // --- Painel Administrativo (admin.php, etc) ---
        'SurfLog Administrative Control' => 'Controle Administrativo SurfLog',
        'System Overview' => 'Visão Geral do Sistema',
        'Total Users' => 'Total de Usuários',
        'Registered Boards' => 'Pranchas Cadastradas',
        'Logged Sessions' => 'Sessões Registradas',
        'User Management Database' => 'Banco de Dados de Gestão de Usuários',
        'ID' => 'ID',
        'Name' => 'Nome',
        'Email' => 'E-mail',
        'Role / Level' => 'Cargo / Nível',
        'View Data' => 'Ver Dados',
        'Actions' => 'Ações',
        'Administrator' => 'Administrador',
        'Regular User' => 'Usuário Comum',
        '🏄‍♂️ Boards' => '🏄‍♂️ Pranchas',
        '🌊 Sessions' => '🌊 Sessões',
        '📥 Demote' => '📥 Rebaixar',
        '👑 Promote' => '👑 Promover',
        '🗑️ Delete User' => '🗑️ Excluir Usuário',
        'Boards of:' => 'Pranchas de:',
        'This user has no boards registered.' => 'Este usuário não possui pranchas cadastradas.',
        'Brand:' => 'Marca:',
        'Surf Sessions History of:' => 'Histórico de Sessões de Surf de:',
        'This user has no surf sessions logged.' => 'Este usuário não possui sessões de surf registradas.',

        // --- Diálogos de Confirmação JavaScript ---
        "Are you sure you want to remove this surf session from history?" => "Tem certeza que deseja excluir esta sessão de surf do histórico?",
        "Are you sure you want to delete this board from your quiver?" => "Tem certeza que deseja excluir esta prancha do seu quiver?",
        "Are you sure you want to break partnership with this buddy?" => "Tem certeza que deseja desfazer a parceria com este parceiro?",
        "CRITICAL WARNING: Deleting this user will permanently remove the account, ALL boards and ALL sessions in the system. Continue?" => "AVISO CRÍTICO: Excluir este usuário irá remover permanentemente a conta, TODAS as pranchas e TODAS as sessões no sistema. Continuar?",
        "Are you sure you want to delete this surf session from user history?" => "Tem certeza que deseja excluir esta sessão de surf do histórico do usuário?",
        "Are you sure you want to delete this board from user quiver?" => "Tem certeza que deseja excluir esta prancha do quiver do usuário?"
    ]
];

/**
 * Função global de tradução.
 * Se o idioma for 'en' ou a tradução não existir no array, exibe o termo original em inglês.
 */
function __($termo) {
    global $lang, $textos;
    if ($lang === 'en' || !isset($textos[$lang][$termo])) {
        return $termo;
    }
    return $textos[$lang][$termo];
}