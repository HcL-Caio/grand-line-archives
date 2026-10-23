// Aplicação consumindo dados reais da API PHP/MySQL; não usa cartas fictícias.
const api = new URL("api/", document.baseURI);
const ui = Object.fromEntries(["search","type","color","cost","card-count","card-status","card-grid","empty-state","pager","previous","next","page-label","card-dialog","card-detail","glossary-search","glossary-status","glossary-list"].map(id => [id,document.getElementById(id)]));
let page=1, pages=0, request=0;

function node(tag,className,text){
  const e=document.createElement(tag);
  if(className)e.className=className;
  if(text!==undefined&&text!==null)e.textContent=String(text);
  return e;
}
function safeUrl(value,images=false){
  try{
    const url=new URL(value);
    return url.protocol==="https:"&&(!images||url.hostname==="cards.oplaytcg.com")?url.href:null;
  }catch{return null;}
}
function artwork(url,name){
  const wrapper=node("div","card-art");
  const safe=safeUrl(url,true);
  if(!safe){wrapper.append(node("span","","✦"));return wrapper;}
  const img=node("img");img.src=safe;img.alt="Ilustração de "+name;img.loading="lazy";
  img.addEventListener("error",()=>wrapper.replaceChildren(node("span","","✦")),{once:true});
  wrapper.append(img);return wrapper;
}
async function json(path,params={}){
  const url=new URL(path,api);
  for(const [key,value] of Object.entries(params))if(value!==""&&value!==null)url.searchParams.set(key,value);
  const response=await fetch(url,{headers:{Accept:"application/json"}});
  const data=await response.json().catch(()=>{throw Error("Servidor não retornou JSON. Inicie o PHP.")});
  if(!response.ok)throw Error(data.erro||"Erro de consulta");
  return data;
}
function drawCards(cards){
  const fragment=document.createDocumentFragment();
  for(const card of cards){
    const box=node("article","game-card");
    const meta=node("div","card-meta");
    meta.append(node("span","",card.raridade||"Sem raridade"),node("span","",card.tipo||"Sem categoria"));
    const bottom=node("div","card-bottom");
    bottom.append(node("span","","Poder"),node("b","",card.poder??"N/A"));
    const button=node("button","card-open","Ver ficha ↗");button.type="button";
    button.addEventListener("click",()=>showCard(card.codigo));
    box.append(artwork(card.imagem,card.nome),meta,node("small","card-code",card.codigo),node("h3","",card.nome),node("p","",card.efeito||"Efeito não informado."),bottom,button);
    fragment.append(box);
  }
  ui["card-grid"].replaceChildren(fragment);
}
async function loadCards(){
  const ticket=++request;
  ui["card-status"].textContent="Carregando catálogo...";
  ui.pager.hidden=true;
  try{
    const result=await json("cartas",{busca:ui.search.value.trim(),tipo:ui.type.value,cor:ui.color.value,custo:ui.cost.value,pagina:page,limite:12});
    if(ticket!==request)return;
    drawCards(result.dados);
    const info=result.paginacao;pages=info.paginas;
    ui["card-count"].textContent=info.total+(info.total===1?" carta":" cartas");
    ui["empty-state"].hidden=result.dados.length>0;
    ui["card-status"].textContent=info.total?"":"Nenhum resultado encontrado.";
    ui.pager.hidden=pages<=1;
    ui["page-label"].textContent="Página "+page+" de "+pages;
    ui.previous.disabled=page<=1;ui.next.disabled=page>=pages;
  }catch(error){
    if(ticket!==request)return;
    ui["card-grid"].replaceChildren();ui["empty-state"].hidden=true;
    ui["card-count"].textContent="Indisponível";
    ui["card-status"].textContent=error.message+" Confira banco e instalação.";
  }
}
function field(label,value){
  const e=node("div","detail-field");e.append(node("small","",label),node("strong","",value??"Não informado"));return e;
}
async function showCard(code){
  const dialog=ui["card-dialog"],target=ui["card-detail"];
  target.replaceChildren(node("p","","Carregando ficha..."));
  if(!dialog.open)dialog.showModal();
  try{
    const result=await json("cartas/"+encodeURIComponent(code));
    if(!dialog.open)return;
    const c=result.dados,title=node("h2","",c.nome);title.id="detail-title";
    const stats=node("div","detail-stats");
    for(const [label,value] of [["Custo",c.custo],["Poder",c.poder],["Counter",c.contra_ataque],["Vida",c.vida],["Atributo",c.atributo],["Raridade",c.raridade],["Cores",c.cores],["Bloco",c.bloco]])stats.append(field(label,value));
    const gallery=node("div","print-grid");
    for(const variant of c.impressoes){
      const item=node("div","print-item");
      item.append(artwork(variant.url_imagem,c.nome),node("small","",[variant.colecao_codigo,variant.idioma,variant.arte].filter(Boolean).join(" • ")||"Impressão sem descrição"));
      gallery.append(item);
    }
    if(!c.impressoes.length)gallery.append(node("p","","Nenhuma impressão cadastrada."));
    const source=node("a","source-link","Consultar fonte ↗"),href=safeUrl(c.url_fonte);
    if(href){source.href=href;source.target="_blank";source.rel="noopener noreferrer";}
    target.replaceChildren(title,node("p","detail-subtitle",c.codigo+" • "+(c.tipo||"Sem categoria")),stats,node("h3","","Texto técnico"),node("p","detail-copy",c.efeito||"Efeito não informado."),node("p","detail-copy",c.gatilho?"Trigger: "+c.gatilho:"Trigger não informado."),node("p","editorial-note","Explicação didática e contexto narrativo pendentes de revisão editorial."),node("h3","","Impressões ("+c.impressoes.length+")"),gallery,source);
  }catch(error){target.replaceChildren(node("p","",error.message));}
}
let timer;
ui.search.addEventListener("input",()=>{clearTimeout(timer);timer=setTimeout(()=>{page=1;loadCards()},300);});
for(const item of [ui.type,ui.color,ui.cost])item.addEventListener("change",()=>{page=1;loadCards();});
document.getElementById("clear-filters").addEventListener("click",()=>{for(const item of [ui.search,ui.type,ui.color,ui.cost])item.value="";page=1;loadCards();});
ui.previous.addEventListener("click",()=>{if(page>1){page--;loadCards();}});
ui.next.addEventListener("click",()=>{if(page<pages){page++;loadCards();}});
document.getElementById("close-dialog").addEventListener("click",()=>ui["card-dialog"].close());
async function loadGlossary(){
  ui["glossary-status"].textContent="Carregando...";
  try{
    const result=await json("glossario",{busca:ui["glossary-search"].value.trim()});
    const terms=result.dados.map(term=>{
      const e=node("article","glossary-item");
      e.append(node("h3","",term.termo),node("p","",term.definicao));
      if(term.traducao)e.append(node("small","","Tradução: "+term.traducao));
      const href=safeUrl(term.fonte_url);
      if(href){const link=node("a","","Fonte ↗");link.href=href;link.rel="noopener noreferrer";link.target="_blank";e.append(link);}
      return e;
    });
    ui["glossary-list"].replaceChildren(...terms);
    ui["glossary-status"].textContent=terms.length?"":"Ainda não há verbetes publicados.";
  }catch(error){ui["glossary-status"].textContent=error.message;}
}
let glossaryTimer;
ui["glossary-search"].addEventListener("input",()=>{clearTimeout(glossaryTimer);glossaryTimer=setTimeout(loadGlossary,300);});
loadCards();loadGlossary();
