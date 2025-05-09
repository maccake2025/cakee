/**
 * Arquivo auth.js - Gerencia autenticação de usuários (login/logout) e atualização da UI
 * Dependências:
 * - STATE: Objeto global que armazena o estado da aplicação
 * - DOM: Objeto global com referências aos elementos da página
 * - Funções handleResponse, loadProfile, loadPublications (definidas em outros arquivos)
 */

// Função para verificar se o usuário está autenticado
function checkAuth() {
  // Faz uma requisição GET para o endpoint de autenticação
  return fetch("../back/api/auth.php", {
    method: "GET",
    credentials: "include", // Inclui cookies na requisição
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
  })
    .then(handleResponse) // Processa a resposta do servidor
    .then(function (data) {
      if (data.authenticated) {
        // Se autenticado, atualiza o estado e a UI
        STATE.currentUser = data.usuario;
        updateUIForLoggedInUser();
        return true;
      } else {
        // Se não autenticado, limpa o estado e atualiza a UI
        STATE.currentUser = null;
        updateUIForGuest();
        return false;
      }
    })
    .catch(function (error) {
      // Trata erros na verificação de autenticação
      console.error("Erro ao verificar autenticação:", error);
      return false;
    });
}

// Função para lidar com o clique no botão de login/logout
function handleLoginButtonClick() {
  if (STATE.currentUser) {
    // Se usuário está logado, faz logout
    logoutUser();
  } else {
    // Se não está logado, exibe o modal de login
    DOM.loginModal.show();
  }
}

// Função para fazer logout do usuário
function logoutUser() {
  // Faz requisição DELETE para o endpoint de autenticação
  fetch("../back/api/auth.php", {
    method: "DELETE",
    credentials: "include", // Inclui cookies na requisição
  })
    .then(handleResponse) // Processa a resposta do servidor
    .then(function (data) {
      if (data.success) {
        // Se logout bem-sucedido, atualiza estado e UI
        STATE.currentUser = null;
        updateUIForGuest();

        // Dispara evento para notificar outros módulos
        var event = new Event("userLoggedOut");
        window.dispatchEvent(event);
      }
    })
    .catch(function (error) {
      // Trata erros no logout
      console.error("Erro ao fazer logout:", error);
    });
}

// Função para lidar com o envio do formulário de login
function handleLoginSubmit() {
  // Obtém valores dos campos de email e senha
  const email = document.getElementById("email").value;
  const senha = document.getElementById("senha").value;

  // Valida se os campos foram preenchidos
  if (!email || !senha) {
    showLoginError("Email e senha são obrigatórios");
    return;
  }

  hideLoginError(); // Esconde mensagens de erro anteriores

  // Faz requisição POST para o endpoint de autenticação
  fetch("../back/api/auth.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, senha }), // Envia credenciais como JSON
    credentials: "include", // Inclui cookies na requisição
  })
    .then(handleResponse) // Processa a resposta do servidor
    .then((data) => {
      if (data.success) {
        // Se login bem-sucedido:
        STATE.currentUser = data.usuario; // Atualiza estado
        DOM.loginModal.hide(); // Fecha modal
        updateUIForLoggedInUser(); // Atualiza UI
        loadProfile(); // Recarrega perfil
        loadPublications(); // Recarrega publicações
      } else {
        // Se login falhou, lança erro
        throw new Error(data.error || "Credenciais inválidas");
      }
    })
    .catch((error) => showLoginError(error.message)); // Exibe erro para o usuário
}

// Atualiza a UI para estado de usuário logado
function updateUIForLoggedInUser() {
  DOM.loginButton.textContent = "Sair"; // Muda texto do botão para "Sair"
}

// Atualiza a UI para estado de visitante (não logado)
function updateUIForGuest() {
  DOM.loginButton.textContent = "Entrar"; // Muda texto do botão para "Entrar"
}

// Exibe mensagem de erro no formulário de login
function showLoginError(message) {
  DOM.loginError.classList.remove("d-none"); // Mostra elemento de erro
  DOM.loginError.textContent = message; // Define mensagem de erro
}

// Oculta mensagem de erro no formulário de login
function hideLoginError() {
  DOM.loginError.classList.add("d-none"); // Esconde elemento de erro
}

/**
 * Funcionamento geral:
 *
 * 1. checkAuth(): Verifica periodicamente se o usuário está autenticado
 * 2. handleLoginButtonClick(): Alterna entre login/logout baseado no estado atual
 * 3. logoutUser(): Encerra a sessão do usuário
 * 4. handleLoginSubmit(): Processa o formulário de login
 * 5. Funções auxiliares: Atualizam a UI conforme o estado de autenticação
 *
 * O sistema mantém o estado do usuário em STATE.currentUser e sincroniza
 * com a interface através das funções updateUIFor*.
 *
 * Observações:
 * - Há uma duplicação da função logoutUser() que precisa ser consolidada
 * - As requisições usam credentials: "include" para gerenciar sessões via cookies
 * - A comunicação com o backend é feita através de /back/api/auth.php
 */