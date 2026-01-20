<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Retornar dados estáticos das contas por enquanto
$contas = [
    ['id' => 1, 'nome' => 'Conta Corrente', 'tipo' => 'corrente', 'saldo' => 1000],
    ['id' => 2, 'nome' => 'Poupança', 'tipo' => 'poupanca', 'saldo' => 5000],
    ['id' => 3, 'nome' => 'Carteira', 'tipo' => 'dinheiro', 'saldo' => 200]
];

echo json_encode($contas);
?>