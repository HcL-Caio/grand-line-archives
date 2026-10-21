<?php
/**
 * JSONL -> MySQL; execute via PHP CLI.
 * php scripts/importar_cartas.php database/data/oplaytcg_en.jsonl
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Execute somente via terminal.\n");
}
$path = $argv[1] ?? '';
if ($path === '' || !is_readable($path)) {
    fwrite(STDERR, "Uso: php scripts/importar_cartas.php caminho/arquivo.jsonl\n");
    exit(1);
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_NAME') ?: 'grand_line_archives';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Falha na conexão PDO. Confira DB_HOST/DB_NAME/DB_USER/DB_PASS e importe o schema.sql primeiro.\n");
    exit(2);
}

$insertCard = $pdo->prepare(<<<'SQL'
INSERT INTO cartas
(codigo,nome,tipo,raridade,cores,atributo,custo,poder,contra_ataque,vida,bloco,colecao_codigo,efeito,gatilho,url_fonte)
VALUES
(:codigo,:nome,:tipo,:raridade,:cores,:atributo,:custo,:poder,:contra_ataque,:vida,:bloco,:colecao_codigo,:efeito,:gatilho,:url_fonte)
ON DUPLICATE KEY UPDATE
nome=VALUES(nome),tipo=VALUES(tipo),raridade=VALUES(raridade),cores=VALUES(cores),
atributo=VALUES(atributo),custo=VALUES(custo),poder=VALUES(poder),
contra_ataque=VALUES(contra_ataque),vida=VALUES(vida),bloco=VALUES(bloco),
colecao_codigo=VALUES(colecao_codigo),efeito=VALUES(efeito),gatilho=VALUES(gatilho),
url_fonte=VALUES(url_fonte)
SQL);
$insertPrint = $pdo->prepare(<<<'SQL'
INSERT INTO impressoes_cartas
(carta_codigo,idioma,chave_impressao,colecao_codigo,arte,url_imagem)
VALUES (:carta_codigo,:idioma,:chave_impressao,:colecao_codigo,:arte,:url_imagem)
ON DUPLICATE KEY UPDATE
colecao_codigo=VALUES(colecao_codigo),arte=VALUES(arte),url_imagem=VALUES(url_imagem)
SQL);

$reader = new SplFileObject($path, 'r');
$count = 0;
$printed = 0;
$invalid = 0;
$pdo->beginTransaction();
try {
    while (!$reader->eof()) {
        $line = trim($reader->fgets());
        if ($line === '') {
            continue;
        }
        $card = json_decode($line, true);
        if (!is_array($card) || empty($card['codigo']) || empty($card['nome']) || empty($card['url_fonte'])) {
            $invalid++;
            continue;
        }
        $keys = ['codigo','nome','tipo','raridade','cores','atributo','custo','poder','contra_ataque','vida','bloco','colecao_codigo','efeito','gatilho','url_fonte'];
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $card[$key] ?? null;
        }
        $insertCard->execute($values);
        foreach ($card['impressoes'] ?? [] as $printing) {
            if (empty($printing['url_imagem'])) {
                continue;
            }
            $url = $printing['url_imagem'];
            $insertPrint->execute([
                'carta_codigo' => $card['codigo'],
                'idioma' => 'en',
                'chave_impressao' => hash('sha256', $url),
                'colecao_codigo' => $printing['colecao_codigo'] ?? null,
                'arte' => $printing['arte'] ?? null,
                'url_imagem' => $url,
            ]);
            $printed++;
        }
        $count++;
        if ($count % 100 === 0) {
            $pdo->commit();
            $pdo->beginTransaction();
            echo "Processadas: {$count} cartas\n";
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Erro de importação: {$e->getMessage()}\n");
    exit(3);
}
echo "Linhas válidas processadas: {$count}; impressões processadas: {$printed}; linhas inválidas: {$invalid}.\n";
echo "Confira: SELECT COUNT(*) FROM cartas; SELECT COUNT(*) FROM impressoes_cartas;\n";
