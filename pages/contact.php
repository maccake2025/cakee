<?php
// pages/contact.php

// (Opcional) Se quiser registrar visitas ou logar ações, pode iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// (Opcional) Defina um título para aparecer no header
$page_title = "Contato";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contato - </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Entre em contato com a equipe da Mac Cake para dúvidas, sugestões ou suporte.">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="contact-page">
        <section class="contact-info">
            <h1>Fale Conosco</h1>
            <p>
                Tem dúvidas, sugestões ou precisa de suporte?<br>
                Preencha o formulário abaixo ou envie um e-mail para <a href="mailto:maccake21@gmail.com">maccake21@gmail.com</a>.
            </p>
            <ul class="contact-details">
                <li><i class="fas fa-envelope"></i> maccake21@gmail.com</li>
                <li><i class="fas fa-phone"></i> (11) 90000-0000</li>
                <li><i class="fas fa-map-marker-alt"></i> Uberaba/SP</li>
            </ul>
        </section>

        <section class="contact-form-section">
            <h2>Formulário de Contato</h2>
            <div class="google-form-container">
                <!-- Google Forms Embed -->
                <iframe src="https://docs.google.com/forms/d/e/1FAIpQLSdZQRUyKP8b_dTeoUFH-3ptka9ixYcTeohe1X1mQXMKCdUsDw/viewform?embedded=true"
                        width="100%" height="900" frameborder="0" marginheight="0" marginwidth="0"
                        style="border: none; background: transparent;">
                    Carregando formulário…
                </iframe>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>