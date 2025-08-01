        </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Mac Cake</h3>
                <p>Conectando você aos melhores confeiteiros artesanais da sua região.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/share/15rMVmc6te/" aria-label="Facebook" target="_blank">
                    <i class="fab fa-facebook-f"></i>
                    <a href="https://www.instagram.com/mac.cake?igsh=MXNrZWgxbGRrNHIrzg==" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://github.com/maccake2025/" aria-label="GitHub" target="_blank">
                    <i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Links Rápidos</h3>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/pages/products.php">Produtos</a></li>
                    <li><a href="/pages/about.php">Sobre Nós</a></li>
                    <li><a href="/pages/contact.php">Contato</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Categorias</h3>
                <ul>
                    <?php
                    require_once __DIR__ . '/../config/database.php';
                    $db = new Database();
                    $conn = $db->connect();
                    
                    $stmt = $conn->query("SELECT DISTINCT categoria FROM produtos WHERE categoria IS NOT NULL AND ativo = 1 LIMIT 6");
                    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($categories as $category): ?>
                        <li><a href="/pages/products.php?category=<?= urlencode($category) ?>"><?= htmlspecialchars($category) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            

        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Mac Cake. Todos os direitos reservados.</p>
            
        </div>
    </footer>

    <!-- Modal de Login (exibido via JavaScript) -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Entrar na sua conta</h2>
            <form id="modalLoginForm" action="/pages/auth/login.php" method="POST">
                <div class="form-group">
                    <label for="modalEmail">Email</label>
                    <input type="email" id="modalEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="modalPassword">Senha</label>
                    <input type="password" id="modalPassword" name="password" required>
                </div>
                <button type="submit" class="btn">Entrar</button>
                <div class="form-footer">
                    <a href="/pages/auth/register.php">Criar conta</a>
                    <a href="/pages/auth/forgot_password.php">Esqueceu a senha?</a>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript adicional -->
    <script>
        // Menu Mobile
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('active');
        });
        
        // Dropdown do usuário
        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown) {
            const userBtn = userDropdown.querySelector('.user-btn');
            
            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });
            
            // Fechar ao clicar fora
            document.addEventListener('click', function() {
                userDropdown.classList.remove('active');
            });
        }
        
        // Modal de login
        const loginModal = document.getElementById('loginModal');
        const loginLinks = document.querySelectorAll('.auth-link[href*="login"]');
        const closeModal = document.querySelector('.close-modal');
        
        if (loginLinks.length > 0) {
            loginLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    loginModal.style.display = 'block';
                });
            });
            
            closeModal.addEventListener('click', function() {
                loginModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(e) {
                if (e.target === loginModal) {
                    loginModal.style.display = 'none';
                }
            });
        }
        
        // Atualizar contador do carrinho globalmente
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se há itens no carrinho via AJAX se necessário
            <?php if (isset($_SESSION['user_id'])): ?>
                fetch('/includes/get_cart_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const cartCountElement = document.querySelector('.cart-count');
                        if (data.count > 0) {
                            if (cartCountElement) {
                                cartCountElement.textContent = data.count;
                            } else {
                                // Criar elemento se não existir
                                const cartIcon = document.querySelector('.cart-icon');
                                if (cartIcon) {
                                    const count = document.createElement('span');
                                    count.className = 'cart-count';
                                    count.textContent = data.count;
                                    cartIcon.appendChild(count);
                                }
                            }
                        } else if (cartCountElement) {
                            cartCountElement.remove();
                        }
                    });
            <?php endif; ?>
        });
    </script>
</body>
</html>