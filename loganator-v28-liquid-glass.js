
(function(){
  "use strict";
  const KEY="loganator-v28-liquid-glass";
  const colors=[
    "#ff3b30","#ff453a","#ff375f","#ff2d55","#ff2f92","#ff2d95","#ff1493","#e91e63","#d81b60","#c2185b",
    "#ff6b6b","#ff7a59","#ff7043","#ff5722","#f4511e","#ff8a00","#ff9500","#ff9f0a","#ffb000","#ffc107",
    "#ffd60a","#ffcc00","#fdd835","#fbc02d","#eab308","#84cc16","#8bc34a","#7cb342","#43a047","#22c55e",
    "#34c759","#30d158","#10b981","#00a878","#009688","#00a6a6","#00b8d9","#06b6d4","#22d3ee","#00c7be",
    "#00a9ff","#0a84ff","#007aff","#2563eb","#3b82f6","#4f46e5","#5856d6","#5e5ce6","#6366f1","#7c5cff",
    "#8b5cf6","#a855f7","#af52de","#9333ea","#c026d3","#d946ef","#ec4899","#f43f5e","#fb7185","#f97316",
    "#a16207","#92400e","#b45309","#ca8a04","#65a30d","#15803d","#166534","#047857","#0f766e","#115e59",
    "#0369a1","#075985","#1d4ed8","#3730a3","#312e81","#4338ca","#5b21b6","#6d28d9","#7e22ce","#86198f",
    "#be185d","#9f1239","#881337","#334155","#475569","#64748b","#6b7280","#71717a","#52525b","#3f3f46",
    "#27272a","#18181b","#111827","#0f172a","#020617","#ffffff","#f8fafc","#f1f5f9","#e2e8f0","#cbd5e1",
    "#94a3b8","#64748b","#78716c","#57534e","#44403c","#292524","#1c1917","#14532d","#713f12","#7f1d1d",
    "#4c1d95","#581c87","#701a75","#831843","#164e63","#083344","#172554","#1e3a8a","#312e81","#365314"
  ];

  let state={accent:"#7c5cff",blur:28,glass:.62,motion:true,dark:true};
  try{state=Object.assign(state,JSON.parse(localStorage.getItem(KEY)||"{}"));}catch(e){}

  const style=document.createElement("style");
  style.id="lgcc-runtime-style";
  document.head.appendChild(style);

  function save(){localStorage.setItem(KEY,JSON.stringify(state));}
  function apply(){
    document.documentElement.style.setProperty("--lgcc-accent",state.accent);
    document.documentElement.style.setProperty("--lgcc-blur",state.blur+"px");
    style.textContent=`
      #lgcc-panel .lgcc-btn:hover,#lgcc-panel .lgcc-switch input:checked+.lgcc-slider{background:${state.accent}!important}
      #lgcc-panel .lgcc-btn.primary{background:${state.accent}!important}
      #lgcc-panel .lgcc-color.active{outline-color:${state.accent}!important}
      ${state.dark?"":"#lgcc-panel{background:rgba(245,247,252,.78)!important;color:#111}#lgcc-panel .lgcc-close,#lgcc-panel .lgcc-btn{color:#111!important;background:rgba(0,0,0,.07)!important}"}
    `;
    document.documentElement.classList.toggle("lgcc-no-motion",!state.motion);
    const blur=document.getElementById("lgcc-blur");
    const glass=document.getElementById("lgcc-glass");
    const blurVal=document.getElementById("lgcc-blur-val");
    const glassVal=document.getElementById("lgcc-glass-val");
    if(blur){blur.value=state.blur;blurVal.textContent=state.blur+"px"}
    if(glass){glass.value=Math.round(state.glass*100);glassVal.textContent=Math.round(state.glass*100)+"%"}
    const dark=document.getElementById("lgcc-dark"); if(dark) dark.checked=state.dark;
    const motion=document.getElementById("lgcc-motion"); if(motion) motion.checked=state.motion;
    document.querySelectorAll(".lgcc-color").forEach(b=>b.classList.toggle("active",b.dataset.c===state.accent));
    save();
  }

  const toggle=document.createElement("button");
  toggle.id="lgcc-toggle";toggle.type="button";toggle.title="Ouvrir le Control Center";
  toggle.textContent="⚙️";document.body.appendChild(toggle);

  const back=document.createElement("div");back.id="lgcc-backdrop";document.body.appendChild(back);
  const panel=document.createElement("aside");panel.id="lgcc-panel";panel.setAttribute("aria-label","LOGANATOR Control Center");
  panel.innerHTML=`
    <div class="lgcc-head">
      <div><div class="lgcc-title">LOGANATOR Control Center</div><div class="lgcc-sub">V28 · Liquid Glass</div></div>
      <button class="lgcc-close" type="button" aria-label="Fermer">×</button>
    </div>
    <div class="lgcc-scroll">
      <div class="lgcc-card">
        <h3>Apparence</h3>
        <div class="lgcc-row"><span>Flou</span><input id="lgcc-blur" type="range" min="8" max="55" step="1"><span class="lgcc-value" id="lgcc-blur-val"></span></div>
        <div class="lgcc-row"><span>Transparence</span><input id="lgcc-glass" type="range" min="30" max="90" step="1"><span class="lgcc-value" id="lgcc-glass-val"></span></div>
        <div class="lgcc-row"><span>Mode sombre</span><label class="lgcc-switch"><input id="lgcc-dark" type="checkbox"><span class="lgcc-slider"></span></label></div>
        <div class="lgcc-row"><span>Animations</span><label class="lgcc-switch"><input id="lgcc-motion" type="checkbox"><span class="lgcc-slider"></span></label></div>
      </div>
      <div class="lgcc-card">
        <h3>Couleur d'accent · 120+ choix</h3>
        <div class="lgcc-colors" id="lgcc-colors"></div>
      </div>
      <div class="lgcc-card">
        <h3>Actions</h3>
        <div class="lgcc-actions">
          <button class="lgcc-btn primary" id="lgcc-save" type="button">Enregistrer</button>
          <button class="lgcc-btn" id="lgcc-reset" type="button">Réinitialiser</button>
        </div>
      </div>
      <div class="lgcc-card">
        <h3>Version</h3>
        <div style="font-size:12px;opacity:.65;line-height:1.5">Control Center intégré au vrai site LOGANATOR. Les réglages sont mémorisés dans ce navigateur.</div>
      </div>
    </div>`;
  document.body.appendChild(panel);

  const colorsBox=panel.querySelector("#lgcc-colors");
  colors.forEach(c=>{
    const b=document.createElement("button");b.type="button";b.className="lgcc-color";b.dataset.c=c;b.style.background=c;b.title=c;
    b.addEventListener("click",()=>{state.accent=c;apply();toast("Couleur appliquée");});
    colorsBox.appendChild(b);
  });

  function open(){panel.classList.add("open");back.classList.add("open");apply()}
  function close(){panel.classList.remove("open");back.classList.remove("open")}
  toggle.addEventListener("click",open);
  back.addEventListener("click",close);
  panel.querySelector(".lgcc-close").addEventListener("click",close);
  document.addEventListener("keydown",e=>{if(e.key==="Escape")close()});

  panel.querySelector("#lgcc-blur").addEventListener("input",e=>{state.blur=+e.target.value;apply()});
  panel.querySelector("#lgcc-glass").addEventListener("input",e=>{state.glass=+e.target.value/100;apply()});
  panel.querySelector("#lgcc-dark").addEventListener("change",e=>{state.dark=e.target.checked;apply()});
  panel.querySelector("#lgcc-motion").addEventListener("change",e=>{state.motion=e.target.checked;apply()});
  panel.querySelector("#lgcc-save").addEventListener("click",()=>{save();toast("Réglages enregistrés")});
  panel.querySelector("#lgcc-reset").addEventListener("click",()=>{
    state={accent:"#7c5cff",blur:28,glass:.62,motion:true,dark:true};apply();toast("Réglages réinitialisés")
  });

  const toastEl=document.createElement("div");toastEl.className="lgcc-toast";document.body.appendChild(toastEl);
  let toastTimer;
  function toast(msg){toastEl.textContent=msg;toastEl.classList.add("show");clearTimeout(toastTimer);toastTimer=setTimeout(()=>toastEl.classList.remove("show"),1400)}
  apply();
})();
