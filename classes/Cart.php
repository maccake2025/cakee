<?php
class Cart {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
    
    /**
     * Adiciona um item ao carrinho, validando estoque e ativo.
     */
    public function addItem($product_id, $quantity = 1) {
        // Verificar se o produto existe e está ativo
        $stmt = $this->conn->prepare("
            SELECT p.*, u.nome as vendedor_nome 
            FROM produtos p 
            JOIN usuarios u ON p.vendedor_id = u.id 
            WHERE p.id = ? AND p.ativo = 1
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Produto não encontrado ou inativo.');
        }

        // Verificar estoque
        $estoque = (int)$product['estoque'];
        $requested_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] + $quantity : $quantity;
        if ($requested_quantity > $estoque) {
            throw new Exception("Quantidade solicitada excede o estoque disponível ({$estoque}).");
        }
        
        // Adicionar ou atualizar item no carrinho
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        return true;
    }
    
    /**
     * Atualiza a quantidade de um item, validando estoque e mínimo.
     */
    public function updateItem($product_id, $quantity) {
        // Verificar se produto existe e está ativo
        $stmt = $this->conn->prepare("SELECT estoque FROM produtos WHERE id = ? AND ativo = 1");
        $stmt->execute([$product_id]);
        $estoque = $stmt->fetchColumn();
        if ($estoque === false) {
            throw new Exception('Produto não encontrado ou inativo.');
        }
        if ($quantity < 1) {
            unset($_SESSION['cart'][$product_id]);
            return true;
        }
        if ($quantity > $estoque) {
            throw new Exception("Quantidade solicitada excede o estoque disponível ({$estoque}).");
        }
        $_SESSION['cart'][$product_id] = $quantity;
        return true;
    }
    
    /**
     * Remove item do carrinho.
     */
    public function removeItem($product_id) {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            return true;
        }
        return false;
    }
    
    /**
     * Retorna itens do carrinho, incluindo dados do produto e estoque atualizado.
     */
    public function getItems() {
        $items = [];
        if (!empty($_SESSION['cart'])) {
            $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
            $product_ids = array_keys($_SESSION['cart']);
            $stmt = $this->conn->prepare("
                SELECT p.*, u.nome as vendedor_nome 
                FROM produtos p 
                JOIN usuarios u ON p.vendedor_id = u.id 
                WHERE p.id IN ($placeholders)
            ");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                $id = $product['id'];
                $quantity = $_SESSION['cart'][$id];

                // Preço promocional
                $preco = (float)$product['preco'];
                $preco_promocional = isset($product['preco_promocional']) ? (float)$product['preco_promocional'] : 0;

                // Estoque atualizado
                $estoque = (int)$product['estoque'];
                
                $items[] = [
                    'id' => $id,
                    'nome' => $product['nome'],
                    'preco' => $preco,
                    'preco_promocional' => $preco_promocional,
                    'imagem_principal' => $product['imagem_principal'],
                    'vendedor_nome' => $product['vendedor_nome'],
                    'quantity' => $quantity,
                    'estoque' => $estoque
                ];
            }
        }
        return $items;
    }
    
    /**
     * Calcula o subtotal do carrinho considerando preço promocional.
     */
    public function getSubtotal() {
        $subtotal = 0;
        $items = $this->getItems();
        foreach ($items as $item) {
            $unit_price = ($item['preco_promocional'] > 0) ? $item['preco_promocional'] : $item['preco'];
            $subtotal += $unit_price * $item['quantity'];
        }
        return $subtotal;
    }
    
    /**
     * Limpa o carrinho.
     */
    public function clear() {
        $_SESSION['cart'] = [];
    }
    
    /**
     * Conta o total de itens no carrinho.
     */
    public function countItems() {
        return array_sum($_SESSION['cart']);
    }
}
?>