const e=r=>r?parseFloat(r).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,","):"0.00",t=r=>!r&&r!==0?"-":new Intl.NumberFormat("en-US",{style:"currency",currency:"USD"}).format(r);export{e as a,t as f};
