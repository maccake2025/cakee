<?php
// Recebe: $product_id, $user_id (se logado)
require_once __DIR__ . '/../config/database.php';
session_start();
$db = new Database();
$conn = $db->connect();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$stmt = $conn->prepare("
    SELECT a.*, u.nome as cliente_nome, u.foto_perfil as cliente_foto
    FROM avaliacoes a
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE a.produto_id = ? ORDER BY a.data_avaliacao DESC
    LIMIT 10
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT AVG(nota) as avg_rating, COUNT(*) as total FROM avaliacoes WHERE produto_id = ?");
$stmt->execute([$product_id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
$average_rating = $r['avg_rating'] ? round($r['avg_rating'], 1) : 0;
$total_reviews = $r['total'] ? (int)$r['total'] : 0;

// Pega a avaliação do usuário logado
$user_review = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM avaliacoes WHERE usuario_id = ? AND produto_id = ? LIMIT 1");
    $stmt->execute([$user_id, $product_id]);
    $user_review = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="product-rating">
    <span class="stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fas fa-star<?= $average_rating >= $i ? ' filled' : '' ?>"></i>
        <?php endfor; ?>
    </span>
    <span class="rating-value"><?= $average_rating ?> / 5</span>
    <span class="reviews-count">(<?= $total_reviews ?> avaliações)</span>
</div>

<?php if ($user_id): ?>
    <?php if ($user_review): ?>
        <div class="alert info">Você já avaliou este produto.</div>
        <div class="review-form-card">
            <form class="edit-review-form" data-action="edit" data-review-id="<?= $user_review['id'] ?>">
                <label>Nota:</label>
                <div class="review-stars-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="nota" id="editstar<?= $i ?>" value="<?= $i ?>" <?= $user_review['nota'] == $i ? "checked" : "" ?>>
                        <label for="editstar<?= $i ?>"><i class="fas fa-star<?= $user_review['nota'] >= $i ? ' filled' : '' ?>"></i></label>
                    <?php endfor; ?>
                </div>
                <label>Comentário:</label>
                <textarea name="comentario" rows="3" required minlength="5"><?= htmlspecialchars($user_review['comentario']) ?></textarea>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar alteração</button>
                <button type="button" class="btn btn-danger delete-review-btn" data-review-id="<?= $user_review['id'] ?>"><i class="fas fa-trash"></i> Apagar</button>
            </form>
        </div>
    <?php else: ?>
        <div class="review-form-card">
            <h3>Deixe sua avaliação</h3>
            <form class="add-review-form" data-action="add">
                <label>Nota:</label>
                <div class="review-stars-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="nota" id="star<?= $i ?>" value="<?= $i ?>" required>
                        <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                    <?php endfor; ?>
                </div>
                <label>Comentário:</label>
                <textarea name="comentario" rows="3" required minlength="5"></textarea>
                <button type="submit" class="btn btn-primary"><i class="fas fa-star"></i> Avaliar Produto</button>
            </form>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert info">Faça login para avaliar este produto.</div>
<?php endif; ?>

<?php if (empty($reviews)): ?>
    <p>Este produto ainda não possui avaliações.</p>
<?php else: ?>
    <div class="reviews-list">
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <?php if ($review['cliente_foto']): ?>
                        <img src="/assets/images/uploads/profiles/<?= htmlspecialchars($review['cliente_foto']) ?>" 
                             alt="Foto de <?= htmlspecialchars($review['cliente_nome']) ?>"
                             class="review-avatar">
                    <?php else: ?>
                        <i class="fas fa-user-circle review-avatar"></i>
                    <?php endif; ?>
                    <span class="reviewer-name"><?= htmlspecialchars($review['cliente_nome']) ?></span>
                    <span class="review-date"><?= date('d/m/Y', strtotime($review['data_avaliacao'])) ?></span>
                    <span class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?= $review['nota'] >= $i ? ' filled' : '' ?>"></i>
                        <?php endfor; ?>
                    </span>
                </div>
                <div class="review-body">
                    <p><?= nl2br(htmlspecialchars($review['comentario'])) ?></p>
                </div>
                <?php if ($review['resposta_vendedor']): ?>
                    <div class="review-reply">
                        <strong>Resposta do vendedor:</strong>
                        <p><?= nl2br(htmlspecialchars($review['resposta_vendedor'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>