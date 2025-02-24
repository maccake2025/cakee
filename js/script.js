document.addEventListener("DOMContentLoaded", function () {
    const carousel = document.querySelector(".carousel");
    const prevBtn = document.querySelector(".prev");
    const nextBtn = document.querySelector(".next");
    let scrollAmount = 0;
    let startX = 0;
    let currentX = 0;
    let isTouching = false;

    // Função para atualizar a posição do carrossel
    function moveCarousel() {
        carousel.style.transition = "none"; // Remove a transição para o movimento instantâneo
        carousel.style.transform = `translateX(-${scrollAmount}px)`;
    }

    // Função para adicionar a transição de animação suave quando o movimento terminar
    function enableTransition() {
        carousel.style.transition = "transform 0.3s ease"; // Suaviza a transição
    }

    // Evento de clique no botão de próximo
    nextBtn.addEventListener("click", function () {
        scrollAmount += 220; // Ajuste o valor conforme necessário
        enableTransition();
        moveCarousel();
    });

    // Evento de clique no botão de anterior
    prevBtn.addEventListener("click", function () {
        scrollAmount -= 220;
        enableTransition();
        moveCarousel();
    });

    // Eventos de toque
    carousel.addEventListener("touchstart", function (e) {
        isTouching = true;
        startX = e.touches[0].clientX; // Pega a posição inicial do toque
    });

    carousel.addEventListener("touchmove", function (e) {
        if (!isTouching) return;

        currentX = e.touches[0].clientX; // Pega a posição atual do toque
        let diffX = startX - currentX; // Calcula a diferença entre o início e o movimento

        // Atualiza a posição do carrossel com base no movimento
        carousel.style.transition = "none"; // Remove a transição durante o movimento
        carousel.style.transform = `translateX(-${scrollAmount + diffX}px)`;
    });

    carousel.addEventListener("touchend", function () {
        if (!isTouching) return;

        // Calcula a direção do movimento e ajusta o scrollAmount
        if (startX - currentX > 50) {
            scrollAmount += 220; // Deslizar para a direita
        } else if (currentX - startX > 50) {
            scrollAmount -= 220; // Deslizar para a esquerda
        }

        // Finaliza a posição do carrossel com uma transição suave
        enableTransition();
        moveCarousel();

        isTouching = false; // Desliga o controle de toque
    });
});



//Responsividade

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