<?php
// controllers/AuthController.php
class AuthController {
    public function login() {
        include VIEWS_PATH . 'login.php';
    }

    public function authenticate() {
        $pdo = Conectar_DB();
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = :u");
        $stmt->execute(['u' => $_POST['username']]);
        $user = $stmt->fetch();

        // No seu AuthController.php, dentro da função authenticate():
        if ($user && ($user['password'] === $_POST['password'] || $user['password'] === sha1($_POST['password']))) {    
            session_start();
            $_SESSION['usuario_logado'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            header("Location: " . BASE_URL . "company/dashboard");
        } else {
            die("Credenciais inválidas.");
        }
    }

    public static function check($role = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['usuario_logado'])) {
            header("Location: " . BASE_URL . "login");
            exit;
        }
        if ($role && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== $role) {
            die("Acesso negado.");
        }
    }

    public function logout() {
    session_start();
    session_unset(); // Limpa as variáveis de sessão
    session_destroy(); // Destrói a sessão
    header("Location: " . BASE_URL . "login");
    exit;
}
}