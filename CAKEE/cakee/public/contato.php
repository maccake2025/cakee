<?php
// Se o formulário for enviado, você pode processar os dados aqui
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $mensagem = $_POST['mensagem'];

    // Aqui você pode processar os dados (como enviar para um e-mail ou armazenar no banco de dados)
    // Exemplo de envio de e-mail:
    mail("maccake21@gmail.com", "Mensagem de Contato - $nome", $mensagem, "From: $email");

    // Após o envio, redireciona o usuário para uma página de confirmação ou uma nova ação
    header("Location: obrigado.php");
    exit;
}
?>

<?php include('header.php'); ?>

<!-- Toldo -->
<div class="toldo">
    <img src="./imagens/toldo_base site div.png" alt="Toldo decorativo do site">
</div>

<!-- Conteúdo Principal -->
<main>
    <section class="formulario">
        <h2>Envie sua mensagem</h2>
        <form id="contactForm" action="contato.php" method="POST">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" placeholder="Digite seu nome" required>
            
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
            
            <label for="mensagem">Mensagem:</label>
            <textarea id="mensagem" name="mensagem" rows="5" placeholder="Escreva sua mensagem aqui" required></textarea>
            
            <button type="submit" aria-label="Enviar mensagem">Enviar</button>
        </form>
    </section>
    <!-- Informações de Contato -->
    <div class="info-container">
        <h1>Informações de Contato</h1>
        <p><strong>Telefone:</strong> <span>(11) 1234-5678</span></p>
        <p><strong>E-mail:</strong> <span>maccake21@gmail.com</span></p>
        <p><strong>Endereço:</strong> <span>Rua Exemplo, 123, Bairro, Cidade, Estado</span></p>
        <h2 id="localizacao">Localização</h2>
        <iframe 
            src="https://www.google.com/maps/embed?pb=..."
            width="100%" 
            height="450" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade"
            title="Localização">
        </iframe>
    </div>
</main>

<?php include('footer.php'); ?>

<!-- Scripts -->
<script src="js/contato.js"></script>
</body>
</html>
