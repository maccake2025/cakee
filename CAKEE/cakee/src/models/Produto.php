<?php
require_once('src/config/database.php');

class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $preco;
    private $imagem;
    private $sugestao;

    public function __construct($nome, $descricao, $preco, $imagem, $sugestao = 0, $id = null) {
        $this->id = $id;
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->preco = $preco;
        $this->imagem = $imagem;
        $this->sugestao = $sugestao;
    }

    // Métodos de acesso (getters e setters)
    public function getId() {
        return $this->id;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function getPreco() {
        return $this->preco;
    }

    public function setPreco($preco) {
        $this->preco = $preco;
    }

    public function getImagem() {
        return $this->imagem;
    }

    public function setImagem($imagem) {
        $this->imagem = $imagem;
    }

    public function getSugestao() {
        return $this->sugestao;
    }

    public function setSugestao($sugestao) {
        $this->sugestao = $sugestao;
    }

    // Função para salvar um novo produto no banco
    public static function salvar(Produto $produto) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, imagem, sugestao) VALUES (:nome, :descricao, :preco, :imagem, :sugestao)");
        $stmt->bindParam(':nome', $produto->getNome());
        $stmt->bindParam(':descricao', $produto->getDescricao());
        $stmt->bindParam(':preco', $produto->getPreco());
        $stmt->bindParam(':imagem', $produto->getImagem());
        $stmt->bindParam(':sugestao', $produto->getSugestao());
        return $stmt->execute();
    }

    // Função para listar todos os produtos
    public static function listar() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM produtos ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Função para listar os produtos sugeridos
    public static function listarSugestoes() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE sugestao = 1 ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Função para listar um produto específico pelo id
    public static function buscarPorId($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Função para atualizar um produto
    public static function atualizar(Produto $produto) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE produtos SET nome = :nome, descricao = :descricao, preco = :preco, imagem = :imagem, sugestao = :sugestao WHERE id = :id");
        $stmt->bindParam(':nome', $produto->getNome());
        $stmt->bindParam(':descricao', $produto->getDescricao());
        $stmt->bindParam(':preco', $produto->getPreco());
        $stmt->bindParam(':imagem', $produto->getImagem());
        $stmt->bindParam(':sugestao', $produto->getSugestao());
        $stmt->bindParam(':id', $produto->getId());
        return $stmt->execute();
    }

    // Função para deletar um produto
    public static function deletar($id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
