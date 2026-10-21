// Dados fictícios para validar somente a interface. Sem banco de dados nesta versão.
const cards = [
  { id: 1, name: "Capitão do Amanhecer", type: "Capitão", rarity: "Lendária", description: "Um líder destemido que enfrenta até as tempestades mais violentas.", power: 950, icon: "☀", accent: "#b38143" },
  { id: 2, name: "Lâmina da Maré", type: "Espadachim", rarity: "Épica", description: "Uma espadachim cuja técnica acompanha o ritmo das ondas.", power: 810, icon: "⚔", accent: "#3c8890" },
  { id: 3, name: "Estrela do Norte", type: "Navegador", rarity: "Rara", description: "Conhece os ventos e encontra rotas em mares desconhecidos.", power: 670, icon: "✦", accent: "#5271a2" },
  { id: 4, name: "Olho do Horizonte", type: "Atirador", rarity: "Rara", description: "Enxerga oportunidades mesmo onde ninguém vê um caminho.", power: 720, icon: "◎", accent: "#74619b" }
];

const grid = document.querySelector("#card-grid");
const search = document.querySelector("#search");
const type = document.querySelector("#type");
const count = document.querySelector("#card-count");
const empty = document.querySelector("#empty-state");

function renderCards() {
  const query = search.value.trim().toLocaleLowerCase("pt-BR");
  const selection = type.value;
  const filtered = cards.filter(card =>
    (selection === "todos" || card.type === selection) &&
    (card.name.toLocaleLowerCase("pt-BR").includes(query) || card.description.toLocaleLowerCase("pt-BR").includes(query))
  );
  grid.replaceChildren();
  for (const card of filtered) {
    const article = document.createElement("article");
    article.className = "game-card";
    const art = document.createElement("div");
    art.className = "card-art";
    art.style.setProperty("--accent", card.accent);
    art.textContent = card.icon;
    art.setAttribute("aria-hidden", "true");
    const meta = document.createElement("div");
    meta.className = "card-meta";
    const rarity = document.createElement("span");
    rarity.textContent = card.rarity;
    const category = document.createElement("span");
    category.textContent = card.type;
    meta.append(rarity, category);
    const name = document.createElement("h3");
    name.textContent = card.name;
    const desc = document.createElement("p");
    desc.textContent = card.description;
    const bottom = document.createElement("div");
    bottom.className = "card-bottom";
    const caption = document.createElement("span");
    caption.textContent = "Poder (demo)";
    const power = document.createElement("b");
    power.textContent = card.power;
    bottom.append(caption, power);
    article.append(art, meta, name, desc, bottom);
    grid.append(article);
  }
  count.textContent = filtered.length + (filtered.length === 1 ? " carta" : " cartas");
  empty.hidden = filtered.length !== 0;
}

search.addEventListener("input", renderCards);
type.addEventListener("change", renderCards);
renderCards();
