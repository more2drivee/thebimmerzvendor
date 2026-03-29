<div class="darkveil-wrapper">
<canvas id="animation2"></canvas>

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
const canvas3 = document.getElementById('floating-orbs');
const ctx3 = canvas3.getContext('2d');
canvas3.width = window.innerWidth;
canvas3.height = window.innerHeight;

const orbs = [];
for(let i=0;i<40;i++){
    orbs.push({
        x: Math.random()*canvas3.width,
        y: Math.random()*canvas3.height,
        r: 10 + Math.random()*15,
        vx: (Math.random()-0.5)*0.2,
        vy: (Math.random()-0.5)*0.2,
        alpha: 0.2 + Math.random()*0.5
    });
}

function drawOrbs(){
    ctx3.clearRect(0,0,canvas3.width,canvas3.height);
    orbs.forEach(o=>{
        o.x += o.vx; o.y += o.vy;
        if(o.x<0)o.x=canvas3.width;
        if(o.x>canvas3.width)o.x=0;
        if(o.y<0)o.y=canvas3.height;
        if(o.y>canvas3.height)o.y=0;

        const g = ctx3.createRadialGradient(o.x,o.y,0,o.x,o.y,o.r);
        g.addColorStop(0,'rgba(124,107,255,'+o.alpha+')');
        g.addColorStop(0.5,'rgba(124,107,255,0.1)');
        g.addColorStop(1,'rgba(0,0,0,0)');

        ctx3.fillStyle = g;
        ctx3.beginPath();
        ctx3.arc(o.x,o.y,o.r,0,Math.PI*2);
        ctx3.fill();
    });
    requestAnimationFrame(drawOrbs);
}
drawOrbs();
</script>