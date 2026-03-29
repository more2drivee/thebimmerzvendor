<div class="darkveil-wrapper">
<canvas id="animation1"></canvas>
</div>

<!-- <link rel="stylesheet" href="{{ asset('css/darkveil.css') }}"> -->
<style>
html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
}
.darkveil-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%; 
    min-height: 400px;
    overflow: hidden;
    background: #0d0f1a;
    z-index: 1;
}
.darkveil-canvas {
    width: 100%;
    height: 100%;
    display: block;
    position: absolute;
    top: 0;
    left: 0;
}
</style>
<script>
const canvas1 = document.getElementById('animation1');
const ctx1 = canvas1.getContext('2d');
canvas1.width = window.innerWidth;
canvas1.height = window.innerHeight;

const particles1 = Array.from({length:120},()=>({
  x:Math.random()*canvas1.width,
  y:Math.random()*canvas1.height,
  r:0.5+Math.random()*2,
  vx:(Math.random()-0.5)*0.3,
  vy:(Math.random()-0.5)*0.3,
  alpha:0.2+Math.random()*0.5
}));

function animate1(){
  ctx1.clearRect(0,0,canvas1.width,canvas1.height);
  particles1.forEach(p=>{
    p.x += p.vx; p.y += p.vy;
    if(p.x<0)p.x=canvas1.width; if(p.x>canvas1.width)p.x=0;
    if(p.y<0)p.y=canvas1.height; if(p.y>canvas1.height)p.y=0;

    ctx1.globalAlpha = p.alpha;
    ctx1.fillStyle="#7c6bff";
    ctx1.shadowColor="#a855f7";
    ctx1.shadowBlur=6;
    ctx1.beginPath();
    ctx1.arc(p.x,p.y,p.r,0,Math.PI*2);
    ctx1.fill();
  });
  requestAnimationFrame(animate1);
}
animate1();
</script>