// Small confetti burst (no library needed).
// Usage: launchConfetti();
function launchConfetti(duration) {
    duration = duration || 3500;

    var canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:10000;';
    document.body.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    var colours = ['#a97954', '#c29b87', '#2c6762', '#532c2e', '#f2c94c', '#eb5757', '#6fcf97', '#56ccf2'];
    var pieces = [];

    // Two cannons, one from each bottom corner, plus a rain from the top.
    function burst(x, y, angle, count) {
        for (var i = 0; i < count; i++) {
            var a = angle + (Math.random() - 0.5) * 0.9;
            var speed = 9 + Math.random() * 11;
            pieces.push({
                x: x, y: y,
                vx: Math.cos(a) * speed,
                vy: Math.sin(a) * speed,
                w: 6 + Math.random() * 7,
                h: 10 + Math.random() * 8,
                rot: Math.random() * Math.PI * 2,
                vr: (Math.random() - 0.5) * 0.35,
                colour: colours[Math.floor(Math.random() * colours.length)],
                circle: Math.random() < 0.25
            });
        }
    }
    burst(0, canvas.height, -Math.PI / 3, 70);
    burst(canvas.width, canvas.height, -Math.PI * 2 / 3, 70);
    for (var i = 0; i < 60; i++) {
        pieces.push({
            x: Math.random() * canvas.width, y: -20 - Math.random() * 200,
            vx: (Math.random() - 0.5) * 3, vy: 2 + Math.random() * 3,
            w: 6 + Math.random() * 6, h: 10 + Math.random() * 6,
            rot: Math.random() * Math.PI * 2, vr: (Math.random() - 0.5) * 0.3,
            colour: colours[Math.floor(Math.random() * colours.length)],
            circle: Math.random() < 0.25
        });
    }

    var start = performance.now();
    function frame(now) {
        var elapsed = now - start;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        var fade = elapsed > duration - 800 ? Math.max(0, (duration - elapsed) / 800) : 1;
        ctx.globalAlpha = fade;

        pieces.forEach(function (p) {
            p.vy += 0.28;          // gravity
            p.vx *= 0.99;          // air drag
            p.vy *= 0.99;
            p.x += p.vx;
            p.y += p.vy;
            p.rot += p.vr;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot);
            ctx.fillStyle = p.colour;
            if (p.circle) {
                ctx.beginPath();
                ctx.arc(0, 0, p.w / 2, 0, Math.PI * 2);
                ctx.fill();
            } else {
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            }
            ctx.restore();
        });

        if (elapsed < duration) {
            requestAnimationFrame(frame);
        } else {
            window.removeEventListener('resize', resize);
            canvas.remove();
        }
    }
    requestAnimationFrame(frame);
}
