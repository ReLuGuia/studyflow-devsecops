<?php
/**
 * Logout do StudyFlow
 * Destroi a sessão do usuário e redireciona para a página inicial
 */

// Ativar exibição de erros (apenas para desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Armazenar nome do usuário para mensagem (opcional)
$nome_usuario = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : '';

// Registrar o logout no banco de dados (opcional)
if (isset($_SESSION['usuario_id'])) {
    try {
        // Conectar ao banco de dados
        require_once 'includes/config.php';
        
        // Opção 1: Atualizar último acesso
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        
        // Opção 2: Inserir em tabela de logs (se existir)
        // $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao, ip, data) VALUES (?, 'logout', ?, NOW())");
        // $stmt->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR']]);
        
    } catch (Exception $e) {
        // Ignorar erros de banco de dados no logout
        error_log("Erro ao registrar logout: " . $e->getMessage());
    }
}

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Limpar cookies específicos do sistema (se houver)
setcookie('remember_token', '', time() - 3600, '/');
setcookie('user_preferences', '', time() - 3600, '/');

// Configurar mensagem de sucesso para exibir após redirecionamento
session_start(); // Reiniciar sessão apenas para a mensagem
$_SESSION['logout_success'] = true;
$_SESSION['logout_message'] = $nome_usuario ? 
    "Até logo, $nome_usuario! Volte sempre para estudar!" : 
    "Logout realizado com sucesso! Até a próxima.";
session_write_close();

// Redirecionar para a página inicial
header('Location: index.php');
exit;
?>