// Função para alternar o menu lateral
function toggleMenu() {
    const nav = document.getElementById('mainNav');
    if (nav) {
        // Alterna entre 'none' e 'flex' para mostrar/ocultar o menu
        if (nav.style.display === "flex") {
            nav.style.display = "none"; // Oculta o menu
        } else {
            nav.style.display = "flex"; // Mostra o menu
        }
    } else {
        console.error("Elemento 'mainNav' não encontrado. Verifique o ID no HTML.");
    }
}

// Função para fechar o menu ao clicar no botão X ou em um link
function closeMenu() {
    const nav = document.getElementById('mainNav');
    if (nav && nav.style.display === "flex") {
        nav.style.display = "none"; // Fecha o menu
    }
}

// Fecha o menu ao clicar fora dele
document.addEventListener('click', function (event) {
    const nav = document.getElementById('mainNav');
    const menuIcon = document.querySelector('.menu-icon');

    // Verifica se o clique foi fora do menu e do ícone de menu
    if (nav && nav.style.display === "flex" && !nav.contains(event.target) && !menuIcon.contains(event.target)) {
        nav.style.display = "none"; // Fecha o menu
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("contactForm");

    form.addEventListener("submit", async (event) => {
        event.preventDefault(); // Impede o comportamento padrão do formulário

        // Captura os valores do formulário
        const nome = document.getElementById("nome").value;
        const email = document.getElementById("email").value;
        const mensagem = document.getElementById("mensagem").value;

        // Cria um objeto com os dados do formulário
        const formData = {
            nome: nome,
            email: email,
            mensagem: mensagem
        };

        try {
            // Envia os dados para o backend usando fetch
            const response = await fetch("./backend/envio_formulario.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                alert("Mensagem enviada com sucesso!");
                form.reset(); // Limpa o formulário após o envio
            } else {
                alert("Ocorreu um erro ao enviar a mensagem. Tente novamente.");
            }
        } catch (error) {
            console.error("Erro ao enviar o formulário:", error);
            alert("Erro ao enviar a mensagem. Verifique sua conexão ou tente novamente mais tarde.");
        }
    });
});