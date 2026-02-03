<?php
require_once 'config/app.php';
require_once 'config/session.php';

// Se já estiver logado, opcionalmente redireciona para a dashboard interna
// if(isset($_SESSION['usuario_id'])) { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prev Dentista - Seu Sorriso VIP</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="lp-body">

    <header class="lp-header">
        <div class="lp-container nav-flex">
            <div class="logo">🦷 Clínica Os Dentistas</div>
            
            <div class="lp-login-area">
                <form action="actions/verificar_login.php" method="POST" class="lp-login-form">
                    <input type="text" name="login" placeholder="Usuário" required>
                    <input type="password" name="senha" placeholder="Senha" required>
                    <button type="submit" class="btn btn-primary btn-sm">Confirmar</button>
                </form>
                <?php if(isset($_GET['erro'])): ?>
                    <span class="lp-error">Dados inválidos</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="lp-hero">
        <div class="lp-container grid-2">
            <div class="hero-text">
                <h1>🚀 Transforme seu sorriso com a gente! 🌟</h1>
                <p>Na <strong>Clínica Os Dentistas</strong>, você é o centro das atenções! 😊 Oferecemos um atendimento humanizado em uma estrutura moderna projetada para o seu conforto.</p>
                <ul class="lp-features">
                    <li><i class="fas fa-check-circle"></i> Atendimento humanizado e personalizado</li>
                    <li><i class="fas fa-check-circle"></i> Clínica com estrutura nova e moderna</li>
                    <li><i class="fas fa-check-circle"></i> Tratamento VIP: água, cafezinho, TV e Wi-Fi</li>
                    <li><i class="fas fa-check-circle"></i> Parcelamos em até 10x sem juros 💳</li>
                </ul>
                <a href="https://wa.me/5591999999999" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg">Agendar Consulta 💖</a>
            </div>
            <!-- <div class="hero-img"> -->
                <div class="team-card">
                    <div class="img-wrapper"><img src="<?= BASE_URL ?>assets/img/dentista-11.jpeg" alt="Recepção Prev Dentista"></div>
                </div>
                <!--<img src="<?= BASE_URL ?>assets/img/clinica-recepcao.jpg" alt="Recepção Prev Dentista" class="img-fluid rounded-5 shadow"> -->
            <!-- </div> -->
        </div>
    </section>

    <section class="lp-team">
        <div class="lp-container">
            <h2 class="section-title">Nossas Especialistas</h2>
            <div class="team-grid">
                <div class="team-card">
                    <div class="img-wrapper"><img src="<?= BASE_URL ?>assets/img/dentista-11.jpeg" alt="Dentista"></div>
                    <h3>Dra. Primavera Augusta</h3>
                    <p>Cuidado e precisão em cada detalhe.</p>
                </div>
                <div class="team-card">
                    <div class="img-wrapper"><img src="<?= BASE_URL ?>assets/img/dentista-11.jpeg" alt="Dentista"></div>
                    <h3>Dra. Joana Lobato</h3>
                    <p>Sua saúde bucal em boas mãos.</p>
                </div>
                <div class="team-card">
                    <div class="img-wrapper"><img src="<?= BASE_URL ?>assets/img/dentista-11.jpeg" alt="Dentista"></div>
                    <h3>Dra. Vitória Farias</h3>
                    <p>Excelência no atendimento estético.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="lp-location">
        <div class="lp-container grid-2">
            <div class="map-area">
                <h2 class="section-title">Faça uma visita!</h2>
                <div class="map-placeholder">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.6!2d-48.4!3d-1.3!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMcKwMTgnMDAuMCJTIDQ4wrAyNCcwMC4wIlc!5e0!3m2!1spt-BR!2sbr!4v123456789" width="100%" height="350" style="border:0; border-radius: 15px;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
            <div class="contact-info">
                <h3>Onde estamos</h3>
                <p><i class="fas fa-map-marker-alt"></i> Rua Primavera 1 - Primavera</p>
                <p><i class="fas fa-phone"></i> (91) 99999-9912</p>
                <p><i class="fas fa-clock"></i> Segunda a Sexta: 08h às 12h e 15h às 18h</p>
                <p><i class="fas fa-clock"></i> Sábado: 08h às 12h</p>
                <br>
                <a href="https://maps.app.goo.gl/1dJcL47GFHxJuhFQA" target="_blank" class="btn btn-info">Ver direções no Google Maps</a>
            </div>
        </div>
    </section>

    <footer class="lp-footer">
        <p>&copy; <?= date('Y') ?> Prev Dentistas. Todos os direitos reservados. #SorrisoPerfeito</p>
    </footer>

</body>
</html>
