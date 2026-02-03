<?php
require_once __DIR__ . '/../config/app.php';

function isActive($urls) {
    if (!is_array($urls)) { $urls = [$urls]; }
    foreach ($urls as $url) {
        if (strpos($_SERVER['REQUEST_URI'], $url) !== false) {
            return true;
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Prev Dentista</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <a href="<?= BASE_URL ?>index.php" style="text-decoration:none; color:inherit;">🦷 Prev Dentista</a>
        </div>
        
        <?php if(isset($_SESSION['usuario_id'])): ?>
        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <?php endif; ?>
        
        <nav class="menu" id="navbar-menu">
            <?php if(isset($_SESSION['usuario_id'])): ?>
                <a href="<?= BASE_URL ?>index.php" class="<?= isActive(['index.php']) ? 'active' : '' ?>">Dashboard</a>
                <a href="<?= BASE_URL ?>views/novo_atendimento.php" class="<?= isActive(['novo_atendimento.php']) ? 'active' : '' ?>">Novo Atendimento</a>
                
                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActive(['procedimentos.php', 'despesas.php', 'usuarios.php']) ? 'active' : '' ?>">
                        Cadastros <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>procedimentos.php">Procedimentos</a>
                        <a href="<?= BASE_URL ?>despesas.php">Despesas</a>
                        <?php if ($_SESSION['usuario_perfil'] === 'proprietario'): ?>
                            <a href="<?= BASE_URL ?>usuarios.php">Usuários</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActive(['relatorios.php', 'relatorio_dentistas.php', 'relatorio_diario.php']) ? 'active' : '' ?>">
                        Relatórios <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>relatorio_diario.php">Diário</a>
                        <a href="<?= BASE_URL ?>relatorios.php">Financeiro Geral</a>
                        <?php if ($_SESSION['usuario_perfil'] === 'proprietario'): ?>
                            <a href="<?= BASE_URL ?>relatorio_dentistas.php">Por Dentista</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <?php if(isset($_SESSION['usuario_id'])): ?>
            <div class="user-menu">
                <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
                <a href="<?= BASE_URL ?>actions/logout.php" class="btn btn-secondary">Sair</a>
            </div>
        <?php endif; ?>
    </header>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobile-menu');
            const navMenu = document.getElementById('navbar-menu');
            
            if (menuToggle && navMenu) {
                menuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }

            document.querySelectorAll('.dropdown').forEach(function(dropdown) {
                const dropdownToggle = dropdown.querySelector('a');
                dropdownToggle.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        event.preventDefault();
                        const content = dropdown.querySelector('.dropdown-content');
                        const isVisible = content.style.display === 'block';
                        
                        // Fecha outros
                        document.querySelectorAll('.dropdown-content').forEach(c => c.style.display = 'none');
                        content.style.display = isVisible ? 'none' : 'block';
                    }
                });
            });
        });
    </script>
    <main class="container">    