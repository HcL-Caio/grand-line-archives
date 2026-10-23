# Planejamento do Grand Line Archives

## 1. Núcleo público (implementação inicial)
Consulta no MySQL, busca por nome/código, filtros combináveis, paginação, ficha técnica, impressões e glossário publicado. O código foi adicionado em branch separada, mas aguarda testes integrados no XAMPP.

## 2. Conteúdo revisado
Testar coleta pequena de fonte autorizada, conferir 60 cartas e pelo menos 20 ilustrações, classificar cores, tipos impressos, fontes e referências sem presumir origem de artes.

## 3. Contas e favoritos (implementação inicial)
Cadastro, login/logout com sessão, hash de senha, token CSRF e favorito vinculado ao usuário foram enviados à branch. Ainda é preciso testar com banco real, completar endurecimento de autenticação e criar operações administrativas verificando o perfil.

## 4. Administração
CRUD e validação de entidades, bloqueio de exclusões com dependências, revisão de explicações e referências narrativas antes da publicação.

## 5. Relatórios e entrega
Conferência de contagens distintas de cartas e de impressões, exportação PDF, testes de segurança/integração, layout responsivo, evidências para apresentação e documentação final.

## Fora do escopo
Simulador de partidas, criação de baralhos, preços em tempo real, comentários públicos e sincronização contínua de catálogos externos.

## Organização
Controllers recebem requisições; Models consultam o banco com PDO; Views correspondem à interface no diretório public. Regras mais complexas poderão ser movidas a Services.
