<div class="darkveil-wrapper">
<canvas id="animation4"></canvas>
</div>

<!-- <link rel="stylesheet" href="{{ asset('css/darkveil.css') }}"> -->
<style>

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
const canvas4 = document.getElementById('animation4');
const ctx4 = canvas4.getContext('2d');
canvas4.width = window.innerWidth;
canvas4.height = window.innerHeight;

const stars=Array.from({length:8},()=>({
  x:Math.random()*canvas4.width,
  y:Math.random()*canvas4.height*0.5,
  vx:Math.random()*2+2,
  vy:Math.random()*1-0.5,
  len:40+Math.random()*40,
  alpha:0,
  life:0,
  maxLife:60+Math.random()*60
}));

function animate4(){
  ctx4.clearRect(0,0,canvas4.width,canvas4.height);
  stars.forEach(s=>{
    s.life++;
    s.x+=s.vx; s.y+=s.vy;
    s.alpha=Math.min(1,s.life/30);

    const tailX=s.x-s.vx*(s.len/Math.sqrt(s.vx**2+s.vy**2));
    const tailY=s.y-s.vy*(s.len/Math.sqrt(s.vx**2+s.vy**2));

    const grad=ctx4.createLinearGradient(tailX,tailY,s.x,s.y);
    grad.addColorStop(0,'rgba(255,255,255,0)');
    grad.addColorStop(1,`rgba(255,255,255,${s.alpha})`);

    ctx4.strokeStyle=grad;
    ctx4.lineWidth=1.5;
    ctx4.beginPath();
    ctx4.moveTo(tailX,tailY);
    ctx4.lineTo(s.x,s.y);
    ctx4.stroke();

    if(s.life>s.maxLife){ s.x=Math.random()*canvas4.width; s.y=Math.random()*canvas4.height*0.5; s.life=0; s.vx=Math.random()*2+2; s.vy=Math.random()*1-0.5; }
  });
  requestAnimationFrame(animate4);
}
animate4();
</script>