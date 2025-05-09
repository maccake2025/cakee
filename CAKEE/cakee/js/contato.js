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

