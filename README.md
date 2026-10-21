# Grand Line Archives

Projeto acadêmico de Desenvolvimento Web: card game inspirado no universo de One Piece.

> Status: planejamento e estruturação. As funcionalidades descritas são propostas, ainda não implementadas.

## Objetivo
Desenvolver uma aplicação web para explorar cartas, organizar baralhos e, conforme os requisitos da disciplina, realizar partidas.

## Tecnologias propostas
- HTML5, CSS3 e JavaScript
- PHP, com organização MVC
- MySQL/MariaDB
- Git e GitHub

A equipe ainda deve confirmar os requisitos e as tecnologias definitivas.

## Organização
```text
app/
  Controllers/
  Models/
  Views/
config/
database/
docs/
public/
  assets/
    css/
    js/
    images/
tests/
```

## Planejamento
1. Validar requisitos e regras de jogo.
2. Planejar telas, entidades e banco de dados.
3. Construir interface e lógica de aplicação.
4. Integrar, testar e documentar.

## Primeira demonstração do frontend
Abra `public/index.html` diretamente no navegador. A busca e os filtros funcionam com dados fictícios, sem servidor. Isso é somente um protótipo: não há autenticação, partidas ou persistência de dados.

Veja [Planejamento](docs/PLANEJAMENTO.md) e [Decisões](docs/DECISOES.md).

## Execução
Ainda não há aplicação executável. As instruções locais serão adicionadas após implementar e testar o ambiente.

## Aviso
Projeto educacional não oficial, sem afiliação com os titulares de One Piece. Evite publicar imagens ou outros ativos protegidos sem autorização.

## Banco de dados e importação OPlayTCG
O esquema MySQL está em `database/schema.sql`. O coletor e importador estão em `scripts/`.
Siga o [guia de importação](docs/IMPORTACAO_OPLAYTCG.md) para testar e importar as cartas. Nenhum dado coletado foi enviado ao repositório.
