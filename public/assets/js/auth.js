// Autenticação com sessão PHP, cookie HttpOnly e token anti-CSRF em operações de escrita.
const authApi=new URL("api/",document.baseURI);
const authUI=Object.fromEntries(["auth-open","logout","user-name","auth-dialog","auth-form","auth-title","auth-name","auth-email","auth-password","auth-error","auth-submit","auth-switch","auth-close","name-field","favoritos","favorites-list","favorites-status"].map(id=>[id,document.getElementById(id)]));
let authState={usuario:null,csrf:null},authMode="login";
let favoriteCodes=new Set();

function authNode(tag,text,css=""){const item=document.createElement(tag);item.textContent=text||"";if(css)item.className=css;return item;}
async function authGet(path){
  const r=await fetch(new URL(path,authApi),{credentials:"same-origin",headers:{Accept:"application/json"}});
  const data=await r.json();
  if(!r.ok)throw Error(data.erro||"Falha na requisição");
  return data;
}
async function authWrite(path,method,body={}){
  const r=await fetch(new URL(path,authApi),{method,credentials:"same-origin",headers:{"Content-Type":"application/json","X-CSRF-Token":authState.csrf||""},body:JSON.stringify(body)});
  const data=await r.json();
  if(!r.ok)throw Error(data.erro||"Falha na requisição");
  return data;
}
function openAuth(mode="login"){
  authMode=mode;
  const registering=mode==="registrar";
  authUI["auth-title"].textContent=registering?"Criar conta":"Entrar";
  authUI["auth-submit"].textContent=registering?"Cadastrar":"Entrar";
  authUI["auth-switch"].textContent=registering?"Já tenho conta":"Criar conta";
  authUI["name-field"].hidden=!registering;
  authUI["auth-name"].required=registering;
  authUI["auth-password"].autocomplete=registering?"new-password":"current-password";
  authUI["auth-error"].textContent="";
  if(!authUI["auth-dialog"].open)authUI["auth-dialog"].showModal();
}
async function refreshAuth(){
  try{
    authState=await authGet("sessao");
    showSession();
  }catch(error){
    authUI["auth-open"].disabled=true;
    authUI["user-name"].textContent="Sessão indisponível";
  }
}
function showSession(){
  const logged=Boolean(authState.usuario);
  authUI["user-name"].textContent=logged?authState.usuario.nome:"";
  authUI["auth-open"].hidden=logged;
  authUI.logout.hidden=!logged;
  authUI.favoritos.hidden=!logged;
  if(logged)loadFavorites();else{favoriteCodes.clear();authUI["favorites-list"].replaceChildren();}
}
async function loadFavorites(){
  if(!authState.usuario)return;
  try{
    const result=await authGet("favoritos");
    const cards=result.dados;
    favoriteCodes=new Set(cards.map(c=>c.codigo));
    const entries=cards.map(card=>{
      const wrapper=authNode("article","","favorite-entry");
      wrapper.append(authNode("small",card.codigo),authNode("h3",card.nome));
      const controls=authNode("div","","favorite-buttons");
      const open=authNode("button","Ver ficha","favorite-open");open.type="button";
      open.addEventListener("click",()=>window.showCard(card.codigo));
      const remove=authNode("button","Remover","favorite-remove");remove.type="button";
      remove.addEventListener("click",async()=>{
        remove.disabled=true;
        try{await authWrite("favoritos/"+encodeURIComponent(card.codigo),"DELETE");await loadFavorites();}
        catch(error){authUI["favorites-status"].textContent=error.message;remove.disabled=false;}
      });
      controls.append(open,remove);wrapper.append(controls);return wrapper;
    });
    authUI["favorites-list"].replaceChildren(...entries);
    authUI["favorites-status"].textContent=cards.length?"":"Você ainda não favoritou nenhuma carta.";
  }catch(error){authUI["favorites-status"].textContent=error.message;}
}
function mountFavorite(target,code){
  const button=authNode("button","","");button.id="favorite-action";button.type="button";
  const status=authNode("p","","");
  function update(){button.textContent=!authState.usuario?"Entrar para favoritar":favoriteCodes.has(code)?"★ Remover favorito":"☆ Adicionar aos favoritos";}
  update();
  button.addEventListener("click",async()=>{
    if(!authState.usuario){openAuth("login");return;}
    button.disabled=true;status.textContent="";
    try{
      if(favoriteCodes.has(code))await authWrite("favoritos/"+encodeURIComponent(code),"DELETE");
      else await authWrite("favoritos","POST",{codigo:code});
      await loadFavorites();update();
    }catch(error){status.textContent=error.message;}finally{button.disabled=false;}
  });
  target.append(button,status);
}
authUI["auth-open"].addEventListener("click",()=>openAuth("login"));
authUI["auth-close"].addEventListener("click",()=>authUI["auth-dialog"].close());
authUI["auth-switch"].addEventListener("click",()=>openAuth(authMode==="login"?"registrar":"login"));
authUI["auth-form"].addEventListener("submit",async event=>{
  event.preventDefault();
  authUI["auth-error"].textContent="";
  authUI["auth-submit"].disabled=true;
  try{
    const body={email:authUI["auth-email"].value.trim(),senha:authUI["auth-password"].value};
    if(authMode==="registrar")body.nome=authUI["auth-name"].value.trim();
    authState=await authWrite(authMode,"POST",body);
    authUI["auth-dialog"].close();authUI["auth-form"].reset();
    showSession();
  }catch(error){authUI["auth-error"].textContent=error.message;}
  finally{authUI["auth-submit"].disabled=false;}
});
authUI.logout.addEventListener("click",async()=>{
  try{authState=await authWrite("logout","POST");showSession();}
  catch(error){authUI["user-name"].textContent=error.message;}
});
window.GLAAuth={mountFavorite};
refreshAuth();
