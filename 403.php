<?php
/**
 * 403 Forbidden Page
 */
$pageTitle = 'Acesso Negado';
require_once __DIR__ . '/includes/header.php';
?>

<main class="py-5">
    <div class="container text-center">
        <div class="py-5">
            <i class="bi bi-shield-x text-danger" style="font-size: 5rem;"></i>
            <h1 class="mt-3">403 - Acesso Negado</h1>
            <p class="text-muted">Você não tem permissão para acessar esta página.</p>
            <a href="<?= BASE_URL ?>" class="btn btn-primary mt-3">
                <i class="bi bi-house"></i> Voltar ao Início
            </a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
