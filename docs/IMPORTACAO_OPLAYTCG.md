# Importação OPlayTCG para MySQL

**Fonte:** https://oplaytcg.com/en/library

**Estado:** estrutura e scripts preparados; a coleta integral NÃO foi executada nem verificada.

## Por que duas tabelas?
Em 21/10/2026, o catálogo do OPlayTCG exibia 4.776 entradas, mas o índice de códigos distintos tinha 3.121 cartas. Uma mesma carta pode ter várias artes/impressões. Por isso:
- cartas: uma linha por código (por exemplo, OP01-001).
- impressoes_cartas: variantes e links de imagens ligados a cada código.

Não espere exatamente 4.776 linhas em cartas ou impressoes_cartas. O script cobre códigos do índice e tenta captar as impressões mostradas nas páginas individuais. Os totais precisam ser verificados ao final.

## Dependências
- Python 3.10+ e BeautifulSoup4
- PHP CLI 8.0+ com PDO MySQL habilitado
- MySQL 8+ ou MariaDB 10.5+ (pode usar XAMPP)
- Acesso permitido ao site de origem

## 1. Criar tabelas
Inicie MySQL no XAMPP. Abra http://localhost/phpmyadmin, clique em Importar e selecione database/schema.sql.

Alternativamente, execute o SQL pelo cliente mysql.

## 2. Testar coletor com apenas 5 cartas
Abra o terminal na pasta raiz do projeto:

    py -m pip install beautifulsoup4
    py scripts/coletar_oplaytcg.py --limite 5

No Linux/macOS, troque py por python3. O resultado será o arquivo local database/data/oplaytcg_en.jsonl.

Confira nomes, efeitos, quantidade de impressões e URLs antes de fazer a coleta completa. Dependendo da estrutura da página, alguns campos poderão ficar nulos. A extração usa HTML público, não uma API documentada.

## 3. Coletar catálogo
Após o teste:

    py scripts/coletar_oplaytcg.py --pausa 1.5

É uma operação demorada: 3.121 páginas e pausas de 1,5 s já representam pelo menos 78 minutos, além da rede. Não rode coletores paralelos. O script respeita robots.txt e desacelera ao receber 429. Caso seja bloqueado, interrompa e solicite autorização ou um export ao site. Reexecutar o comando retoma os códigos faltantes.

## 4. Importar para o banco
Após criar as tabelas:

    php scripts/importar_cartas.php database/data/oplaytcg_en.jsonl

No Windows com XAMPP, caso php não esteja no PATH:

    C:\xampp\php\php.exe scripts/importar_cartas.php database/data/oplaytcg_en.jsonl

Configuração padrão: servidor 127.0.0.1, porta 3306, banco grand_line_archives, usuário root, senha vazia. Caso necessário, configure DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS nas variáveis do ambiente. Não publique senhas no GitHub.

A importação usa inserção/atualização por chave, evitando duplicação ao reexecutar.

## 5. Conferir registros

    USE grand_line_archives;
    SELECT COUNT(*) AS total_cartas FROM cartas;
    SELECT COUNT(*) AS total_impressoes FROM impressoes_cartas;
    SELECT codigo, nome, tipo, custo, poder FROM cartas ORDER BY codigo LIMIT 10;
    SELECT codigo, nome FROM cartas WHERE tipo IS NULL LIMIT 20;

## Limitações e direitos
- Não há garantia de que o HTML atual seja estável: ajustes podem ser necessários.
- Cores não são inferidas de arte/CSS e ficam NULL nessa versão. Outros campos podem não estar disponíveis.
- As impressões capturadas podem não coincidir com todas as variantes exibidas no catálogo.
- Apenas links de imagens são armazenados. Não baixar artes para o repositório.
- OPlayTCG é um projeto comunitário não oficial; imagens, nomes e textos das cartas pertencem a seus titulares. Confira permissões de extração e uso dos dados, mantenha referência à origem e não publique a base completa sem autorização.
