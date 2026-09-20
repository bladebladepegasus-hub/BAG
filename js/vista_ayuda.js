'use strict';
document.querySelectorAll('.example').forEach(b=>b.addEventListener('click',()=>{location.href='vista_chat.html?q='+encodeURIComponent(b.textContent.trim());}));
