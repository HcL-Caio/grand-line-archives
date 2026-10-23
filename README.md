# Grand Line Archives

Projeto acadêmico do CEUB: **enciclopédia sobre One Piece Card Game**. A aplicação organiza cartas, impressões, fontes e referências editoriais. Não é um simulador de partidas.

## Tecnologias
PHP 8.1+ (PDO MySQL e mbstring), MySQL 8+/MariaDB, HTML5, CSS, JavaScript e Bootstrap 5. Para a coleta opcional de dados, Python 3.10+ e BeautifulSoup4.

## O que já existe
- Catálogo de cartas com API PHP MVC: pesquisa por nome/código, filtros por categoria, cor e custo, paginação.
- Ficha técnica e impressões relacionadas, a partir do banco.
- Rota de glossário que devolve somente verbetes publicados.
- Cadastro/login/logout com sessão PHP, senhas com hash e proteção CSRF; favoritos por usuário, com inclusão/remoção.
- Tabelas editoriais para usuários, favoritos, cores, ilustrações, fontes, referências narrativas e distribuições.
- Teste isolado de validação dos filtros em tests/filters.php.

**Ainda faltam:** CRUD administrativo, controle de publicação editorial, relatórios/PDF, revisão de cartas reais e integração totalmente testada. Login/favoritos foram implementados, mas exigem validação no MySQL e no navegador. O banco começa vazio.

## Instalação no XAMPP

1. Inicie Apache e MySQL.
2. Importe database/schema.sql no phpMyAdmin.
3. Depois importe database/02_enciclopedia.sql no mesmo servidor.
4. Coloque a pasta do projeto em C:\xampp\htdocs\grand-line-archives e abra http://localhost/grand-line-archives/public/ . O mod_rewrite e o .htaccess precisam estar habilitados no Apache.
5. Alternativa: no terminal na raiz do projeto, execute:
   
       C:\xampp\php\php.exe -S localhost:8000 -t public public/router.php

   Depois abra http://localhost:8000/ .
6. Confira a conexão em http://localhost:8000/api/health . Se o banco estiver vazio, o catálogo mostrará zero resultados, sem inventar dados.

As configurações padrão são DB_HOST=127.0.0.1, DB_PORT=3306, DB_NAME=grand_line_archives, DB_USER=root, DB_PASS vazio. Ajuste pelas variáveis de ambiente no ambiente local. Não envie senhas ao GitHub. Não abra o HTML via file://, pois ele depende do servidor PHP.

## API

- GET /api/health: conexão MySQL.
- GET /api/cartas?busca=Luffy&tipo=Leader&cor=Red&custo=5&pagina=1&limite=12: catálogo paginado, limites de 1 a 60 itens.
- GET /api/cartas/OP01-001: detalhes da carta e impressões.
- GET /api/glossario?busca=Counter: termos publicados.
- GET /api/sessao: usuário autenticado (ou nulo) e token CSRF.
- POST /api/registrar: JSON com nome, email e senha (mínimo 10 bytes); autentica após criar.
- POST /api/login: JSON com email e senha.
- POST /api/logout: encerra sessão.
- GET /api/favoritos: lista apenas do usuário autenticado.
- POST /api/favoritos: JSON com codigo da carta.
- DELETE /api/favoritos/OP01-001: remove favorito do usuário autenticado.
- POST/DELETE exigem cabeçalho X-CSRF-Token retornado por /api/sessao e cookie da mesma sessão.

As cores são filtradas por cartas e cores associadas nas tabelas normalizadas. O coletor atual não popula cores automaticamente, então o filtro de cor retornará vazio até a classificação ser preenchida e revisada.

## Importação e verificação

Consulte docs/IMPORTACAO_OPLAYTCG.md. Nenhum dado coletado foi versionado, e a coleta completa não foi executada. O objetivo acadêmico é selecionar inicialmente **60 cartas revisadas e 20 ilustrações pesquisadas**, em vez de publicar milhares de registros sem conferência. Respeite as condições da fonte.

## Testes

    php tests/filters.php
    php tests/session.php
    php -l public/index.php
    php -l app/Models/Card.php
    node --check public/assets/js/app.js
    node --check public/assets/js/auth.js

Também é necessário testar no navegador com MySQL real, conferir erros de conexão, busca, filtros simultâneos, paginação, URLs de imagens e responsividade. Veja docs/STATUS_MVP.md.

## Direitos

Aplicação educacional não oficial, sem vínculo com os titulares de One Piece. As artes e informações externas pertencem a seus autores e fontes; não redistribua arquivos sem autorização.
