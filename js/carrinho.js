// Dados dos produtos disponíveis
const produtos = [
    { id: 1, nome: "Bolo de Chocolate", preco: 50.0, imagem: "./imagens/carrinho2.png" },
    { id: 2, nome: "Bolo de Morango", preco: 45.0, imagem: "./imagens/carrinho1.png" }
];

// Variável global para armazenar os itens no carrinho
let carrinho = [];

// Função para renderizar os itens no carrinho
function renderizarCarrinho() {
    const carrinhoItens = document.getElementById("carrinhoItens");
    const carrinhoVazio = document.getElementById("carrinhoVazio");
    const valorTotalElement = document.getElementById("valorTotal");

    // Limpa o conteúdo atual
    carrinhoItens.innerHTML = "";

    if (carrinho.length === 0) {
        carrinhoVazio.style.display = "block";
        valorTotalElement.textContent = "R$ 0,00";
        return;
    }

    carrinhoVazio.style.display = "none";

    // Calcula o total
    let total = 0;

    carrinho.forEach(item => {
        const produto = produtos.find(p => p.id === item.id);

        const itemDiv = document.createElement("div");
        itemDiv.classList.add("item-carrinho");

        itemDiv.innerHTML = `
            <div class="item-info">
                <img src="${produto.imagem}" alt="${produto.nome}" class="item-imagem">
                <div class="detalhes-item">
                    <h3>${produto.nome}</h3>
                    <p>R$ ${produto.preco.toFixed(2)}</p>
                </div>
            </div>
            <div class="quantidade-item">
                <button class="btn-menos" onclick="ajustarQuantidade(${produto.id}, -1)">-</button>
                <span class="quantidade">${item.quantidade}</span>
                <button class="btn-mais" onclick="ajustarQuantidade(${produto.id}, 1)">+</button>
            </div>
            <button class="btn-remover" onclick="removerItem(${produto.id})">Remover</button>
        `;

        carrinhoItens.appendChild(itemDiv);

        total += produto.preco * item.quantidade;
    });

    valorTotalElement.textContent = `R$ ${total.toFixed(2)}`;
}

// Função para adicionar um item ao carrinho
function adicionarAoCarrinho(id) {
    const itemExistente = carrinho.find(item => item.id === id);

    if (itemExistente) {
        itemExistente.quantidade++;
    } else {
        carrinho.push({ id, quantidade: 1 });
    }

    renderizarCarrinho();
}

// Função para ajustar a quantidade de um item
function ajustarQuantidade(id, quantidade) {
    const item = carrinho.find(item => item.id === id);

    if (item) {
        item.quantidade += quantidade;

        if (item.quantidade <= 0) {
            removerItem(id);
        }
    }

    renderizarCarrinho();
}

// Função para remover um item do carrinho
function removerItem(id) {
    carrinho = carrinho.filter(item => item.id !== id);
    renderizarCarrinho();
}

// Função para finalizar a compra
document.querySelector(".btn-finalizar").addEventListener("click", () => {
    if (carrinho.length === 0) {
        alert("Seu carrinho está vazio!");
        return;
    }

    alert("Compra finalizada com sucesso!");
    carrinho = [];
    renderizarCarrinho();
});

// Exemplo de inicialização: Adicionando alguns produtos ao carrinho
window.onload = () => {
    adicionarAoCarrinho(1); // Adiciona Bolo de Chocolate
    adicionarAoCarrinho(2); // Adiciona Bolo de Morango
};


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