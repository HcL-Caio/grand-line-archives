# Estado do MVP (23/10/2026)

## Implementação enviada ao GitHub
- API em PHP MVC para listar cartas, consultar ficha e impressões.
- Busca por nome/código, filtro por cor/categoria/custo e paginação com limite.
- Interface web Bootstrap/CSS e JavaScript que lê resultados da API.
- Glossário somente de termos publicados.
- Expansão relacional de usuários, favoritos, cores, artes, referências, fontes, produtos e distribuição.
- Cadastro/login/logout com sessão PHP, hash, CSRF e favoritos exclusivos do usuário.
- Testes unitários de validação de parâmetros e sessão, com CI.

## Limitações
- Testes com MySQL e navegador no XAMPP ainda não foram executados neste ambiente; não tratar a integração como homologada.
- A importação real das cartas ainda não ocorreu. Banco vazio é normal após criar as tabelas.
- O extrator anterior não fornece cores de forma confiável; revisar e cadastrar o vínculo de cores antes de exigir resultados nesse filtro.
- Definições de glossário e referências narrativas só aparecem após revisão/publicação.
- O fluxo de autenticação/favoritos foi programado, mas carece de teste integrado. CRUD administrativo, revisão editorial e PDF ainda dependem de desenvolvimento posterior.
- Links de imagens dependem de disponibilidade e permissões da fonte.

## Critérios de teste local
1. Rodar schema.sql, depois 02_enciclopedia.sql.
2. GET /api/health deve retornar status ok.
3. GET /api/cartas deve retornar JSON com dados e paginacao, mesmo sem cartas.
4. GET /api/cartas/INEXISTENTE deve retornar 404 JSON.
5. Importar amostra autorizada, conferir detalhes e impressões.
6. Validar filtros combinados e paginação, inclusive custo zero.
7. Testar cadastro, login inválido, logout, CSRF ausente, favoritos isolados entre duas contas.
8. Conferir layout em celular, imagens ausentes e erros de banco.
