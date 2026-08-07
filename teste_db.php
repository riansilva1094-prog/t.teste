<?php
// =============================================
// TESTE DE CONEXÃO COM O BANCO DE DADOS
// =============================================

echo "<h1>🔍 Teste de Conexão - LocaFácil</h1>";

// Carregar configuração do banco
require_once __DIR__ . '/includes/db.php';

echo "<h2>✅ Conexão estabelecida com sucesso!</h2>";

// Testar uma consulta simples
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $result = $stmt->fetch();
    echo "<p>📊 Total de veículos cadastrados: <strong>" . $result['total'] . "</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
    $result = $stmt->fetch();
    echo "<p>📂 Total de categorias: <strong>" . $result['total'] . "</strong></p>";
    
    // Listar veículos disponíveis
    $stmt = $pdo->query("
        SELECT v.*, c.nome as categoria 
        FROM veiculos v 
        JOIN categorias c ON v.categoriaId = c.id 
        WHERE v.status = 'disponivel' 
        LIMIT 5
    ");
    $veiculos = $stmt->fetchAll();
    
    if (count($veiculos) > 0) {
        echo "<h3>🚗 Veículos disponíveis (amostra):</h3>";
        echo "<ul>";
        foreach ($veiculos as $v) {
            echo "<li><strong>{$v['marca']} {$v['modelo']}</strong> - R$ " . number_format($v['diaria'], 2, ',', '.') . "/dia - {$v['categoria']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<p style='color: green;'>✅ Todos os testes passaram com sucesso!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao consultar o banco: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Informações do servidor:</strong></p>";
echo "<ul>";
echo "<li>Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>PHP: " . phpversion() . "</li>";
echo "<li>MySQL: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</li>";
echo "</ul>";
?>