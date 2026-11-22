<div>
    <div class="container-fluid py-4">
        <div class="row justify-content-center g-4">
            {{-- Kolom untuk Game --}}
            <div class="col-12 col-md-7 col-lg-6 d-flex flex-column align-items-center">
                <div class="game-header mb-3 w-100 text-center">
                    <h2 class="fw-bold text-gradient mb-1" style="font-family: 'Press Start 2P', monospace; letter-spacing:2px; font-size:clamp(1.2rem,4vw,2.2rem);">TETRIS</h2>
                    <p class="text-muted small mb-2 d-none d-md-block">
                        Gunakan <kbd>←</kbd> <kbd>→</kbd> <kbd>↓</kbd> untuk bergerak, <kbd>Q</kbd>/<kbd>W</kbd> untuk rotasi
                    </p>
                </div>
                <div class="game-canvas-wrapper position-relative w-100 mx-auto mb-3" style="max-width: 420px;">
                    <canvas id="tetris" class="border border-4 rounded-5 shadow-lg bg-dark tetris-canvas-glow"
                        style="width:100%;height:auto;aspect-ratio:3/5;min-width:160px;max-width:420px;transition:box-shadow .2s;"
                        tabindex="0"></canvas>
                    <div id="gameOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center overlay-flex"
                        style="display:none;z-index:2;">
                        <h3 class="text-danger fw-bold mb-2 animate__animated animate__tada " style="font-family:'Press Start 2P',monospace;letter-spacing:2px;">GAME OVER</h3>
                        <button id="restartButton" class="btn btn-warning px-4 py-2 fw-bold shadow animate__animated animate__pulse animate__infinite">Main Lagi</button>
                    </div>
                    <div class="d-flex justify-content-between w-100 position-absolute top-0 px-2 pt-2" style="pointer-events:none;">
                        <span class="badge bg-gradient-info text-white shadow-sm fs-6 d-inline-block animate__animated animate__fadeInDown">🎮</span>
                        <span class="badge bg-gradient-success text-white shadow-sm fs-6 d-inline-block animate__animated animate__fadeInDown">v2.0</span>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-3 w-100">
                    <div class="score-box bg-gradient-primary text-white rounded-4 px-4 py-2 shadow score-glow">
                        <span class="fw-bold">Skor:</span> <strong id="score" class="fs-5">0</strong>
                    </div>
                    <div class="score-box bg-gradient-success text-white rounded-4 px-4 py-2 shadow score-glow">
                        <span class="fw-bold">Level:</span> <strong id="level" class="fs-5">1</strong>
                    </div>
                    <div class="score-box bg-gradient-info text-white rounded-4 px-4 py-2 shadow score-glow">
                        <span class="fw-bold">Lines:</span> <strong id="lines" class="fs-5">0</strong>
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row gap-2 mt-4 w-100 justify-content-center align-items-center">
                    <button id="startButton" class="btn btn-primary px-4 py-2 fw-bold shadow-lg animate__animated animate__pulse animate__infinite">Mulai Game</button>
                    <div class="d-block d-md-none mt-2 text-center w-100">
                        <small class="text-muted">
                            <span class="d-block mb-1">Kontrol:</span>
                            <span class="badge bg-gradient-primary text-white me-1">Geser Kiri/Kanan</span>
                            <span class="badge bg-gradient-success text-white me-1">Geser Bawah</span>
                            <span class="badge bg-gradient-info text-white">Tap Atas: Rotasi</span>
                        </small>
                    </div>
                </div>
            </div>

            {{-- Kolom untuk Skor --}}
            <div class="col-12 col-md-5 col-lg-4">
                <div class="card shadow-lg border-0 h-100">
                    <div class="card-header bg-gradient-primary text-white d-flex align-items-center">
                        <h6 class="mb-0 flex-grow-1">Papan Skor</h6>
                        <span class="badge bg-gradient-success ms-2 d-none d-md-inline">🔥</span>
                    </div>
                    <div class="card-body">
                        <h5>
                            Skor Tertinggi:
                            <span class="text-success animate__animated animate__flash animate__repeat-2">
                                {{ number_format($highScore, 0, ',', '.') }}
                            </span>
                        </h5>
                        <hr>
                        <h6>5 Permainan Terakhir Anda:</h6>
                        <ul class="list-group list-group-flush">
                            @forelse ($myLastScores as $score)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">{{ number_format($score->score, 0, ',', '.') }}</span>
                                    <small class="text-muted">{{ $score->created_at->diffForHumans() }}</small>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">Belum ada riwayat permainan.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    // Responsive canvas
    const canvas = document.getElementById('tetris');
    const context = canvas.getContext('2d');
    const scoreElement = document.getElementById('score');
    const levelElement = document.getElementById('level');
    const linesElement = document.getElementById('lines');
    const startButton = document.getElementById('startButton');
    const overlay = document.getElementById('gameOverlay');
    const restartButton = document.getElementById('restartButton');

    // Responsive canvas size
    function resizeCanvas() {
        // Maintain 3:5 aspect ratio, max 420px width, responsive for mobile
        let parent = canvas.parentElement;
        let width = Math.min(parent.offsetWidth, 420);
        let height = Math.round(width * 5 / 3);
        canvas.width = width;
        canvas.height = height;
        context.setTransform(1,0,0,1,0,0);
        context.scale(width/12, height/20);
    }
    window.addEventListener('resize', resizeCanvas);

    // Game variables
    let score = 0;
    let level = 1;
    let linesCleared = 0;
    let dropCounter = 0;
    let dropInterval = 1000;
    let lastTime = 0;
    let isGameOver = false;
    let arena = createMatrix(12, 20);

    const player = {
        pos: { x: 0, y: 0 },
        matrix: null,
    };

    // Modern, vibrant colors
    const colors = [
        null,
        '#FF0D72', // T
        '#FFD500', // O
        '#0DC2FF', // L
        '#F538FF', // J
        '#0DFF72', // I
        '#FF8E0D', // S
        '#3877FF'  // Z
    ];

    // Gradient for block
    function getBlockGradient(color) {
        let grad = context.createLinearGradient(0,0,1,1);
        grad.addColorStop(0, color);
        grad.addColorStop(1, "#fff");
        return grad;
    }

    function arenaSweep() {
        let rowCount = 1;
        outer: for (let y = arena.length - 1; y >= 0; --y) {
            for (let x = 0; x < arena[y].length; ++x) {
                if (arena[y][x] === 0) {
                    continue outer;
                }
            }
            const row = arena.splice(y, 1)[0].fill(0);
            arena.unshift(row);
            ++y;
            score += rowCount * 10;
            linesCleared++;
            rowCount *= 2;
            // Level up every 10 lines
            if (linesCleared > 0 && linesCleared % 10 === 0) {
                level++;
                dropInterval = Math.max(80, 1000 - (level * 80));
                showLevelUp();
            }
        }
    }

    function showLevelUp() {
        // Level up animation with confetti
        let el = document.createElement('div');
        el.innerHTML = '<span class="animate__animated animate__bounceIn text-warning" style="font-size:2.2rem;font-family:\'Press Start 2P\',monospace;text-shadow:0 0 10px #000,0 0 20px #fff;">LEVEL UP!</span>';
        el.className = 'position-absolute top-50 start-50 translate-middle text-center';
        el.style.zIndex = 10;
        canvas.parentElement.appendChild(el);
        // Confetti effect
        for(let i=0;i<12;i++){
            let conf = document.createElement('div');
            conf.className = 'confetti';
            conf.style.left = (10 + Math.random()*80) + '%';
            conf.style.background = colors[1 + (i%7)];
            conf.style.animationDelay = (Math.random()*0.5)+'s';
            el.appendChild(conf);
        }
        setTimeout(() => el.remove(), 1400);
    }

    function collide(arena, player) {
        const [m, o] = [player.matrix, player.pos];
        for (let y = 0; y < m.length; ++y) {
            for (let x = 0; x < m[y].length; ++x) {
                if (m[y][x] !== 0 &&
                    (arena[y + o.y] && arena[y + o.y][x + o.x]) !== 0) {
                    return true;
                }
            }
        }
        return false;
    }

    function createMatrix(w, h) {
        const matrix = [];
        while (h--) matrix.push(new Array(w).fill(0));
        return matrix;
    }

    function createPiece(type) {
        if (type === 'T') return [[0,0,0],[1,1,1],[0,1,0]];
        else if (type === 'O') return [[2,2],[2,2]];
        else if (type === 'L') return [[0,3,0],[0,3,0],[0,3,3]];
        else if (type === 'J') return [[0,4,0],[0,4,0],[4,4,0]];
        else if (type === 'I') return [[0,5,0,0],[0,5,0,0],[0,5,0,0],[0,5,0,0]];
        else if (type === 'S') return [[0,6,6],[6,6,0],[0,0,0]];
        else if (type === 'Z') return [[7,7,0],[0,7,7],[0,0,0]];
    }

    function draw() {
        // Animated background gradient
        let grad = context.createLinearGradient(0,0,0,20);
        grad.addColorStop(0, "#232526");
        grad.addColorStop(0.5, "#414345");
        grad.addColorStop(1, "#232526");
        context.fillStyle = grad;
        context.fillRect(0, 0, 12, 20);

        drawMatrix(arena, { x: 0, y: 0 });
        drawMatrix(player.matrix, player.pos, true);
    }

    function drawMatrix(matrix, offset, isPlayer = false) {
        matrix.forEach((row, y) => {
            row.forEach((value, x) => {
                if (value !== 0) {
                    context.save();
                    context.globalAlpha = isPlayer ? 1 : 0.93;
                    context.shadowColor = colors[value];
                    context.shadowBlur = isPlayer ? 16 : 7;
                    context.fillStyle = getBlockGradient(colors[value]);
                    context.fillRect(x + offset.x, y + offset.y, 1, 1);
                    context.strokeStyle = "#fff";
                    context.lineWidth = 0.09;
                    context.strokeRect(x + offset.x, y + offset.y, 1, 1);
                    context.restore();
                }
            });
        });
    }

    function merge(arena, player) {
        player.matrix.forEach((row, y) => {
            row.forEach((value, x) => {
                if (value !== 0) {
                    arena[y + player.pos.y][x + player.pos.x] = value;
                }
            });
        });
    }

    function playerDrop() {
        player.pos.y++;
        if (collide(arena, player)) {
            player.pos.y--;
            merge(arena, player);
            playerReset();
            arenaSweep();
            updateScore();
        }
        dropCounter = 0;
    }

    function playerMove(dir) {
        player.pos.x += dir;
        if (collide(arena, player)) {
            player.pos.x -= dir;
        }
    }

    function playerReset() {
        
        const pieces = 'ILJOTSZ';
        player.matrix = createPiece(pieces[Math.random() * pieces.length | 0]);
        player.pos.y = 0;
        player.pos.x = (arena[0].length / 2 | 0) - (player.matrix[0].length / 2 | 0);
        if (collide(arena, player)) {
            // GAME OVER
            isGameOver = true;
            showGameOver();
           
            // Kirim skor ke Livewire
            Livewire.dispatch('gameover', {
                payload: {
                    score: score,
                    lines: linesCleared,
                    level: level
                }
            });
        }else{
            
        }
    }

    function showGameOver() {
        // Tampilkan overlay game over
        isGameOver = true;
        overlay.style.display = 'flex'; // tampilkan overlay
        
        // Tambahkan event listener untuk restart game
        // restartBtn.addEventListener('click', function () {
            
           
        // });

        // Juga tutup overlay jika user tekan Escape saat game over
        function escHandler(ev) {
            if (overlay.style.display === 'flex' && (ev.key === 'Escape' || ev.key === 'Esc')) {
                overlay.style.display = 'none';
                isGameOver = false;
                startGame();
                document.removeEventListener('keydown', escHandler);
            }
        }
        document.addEventListener('keydown', escHandler);
    }

    function playerRotate(dir) {
        const pos = player.pos.x;
        let offset = 1;
        rotate(player.matrix, dir);
        while (collide(arena, player)) {
            player.pos.x += offset;
            offset = -(offset + (offset > 0 ? 1 : -1));
            if (offset > player.matrix[0].length) {
                rotate(player.matrix, -dir);
                player.pos.x = pos;
                return;
            }
        }
    }

    function rotate(matrix, dir) {
        for (let y = 0; y < matrix.length; ++y) {
            for (let x = 0; x < y; ++x) {
                [matrix[x][y], matrix[y][x]] = [matrix[y][x], matrix[x][y]];
            }
        }
        if (dir > 0) matrix.forEach(row => row.reverse());
        else matrix.reverse();
    }

    // FPS control
    const TARGET_FPS = 60;
    const FRAME_DURATION = 1000 / TARGET_FPS;
    let frameAccumulator = 0;

    function update(time = 0) {
        if (isGameOver) return;
        const deltaTime = time - lastTime;
        lastTime = time;
        frameAccumulator += deltaTime;

        // FPS lock
        while (frameAccumulator >= FRAME_DURATION) {
            dropCounter += FRAME_DURATION;
            if (dropCounter > dropInterval) {
                playerDrop();
            }
            frameAccumulator -= FRAME_DURATION;
        }

        draw();
        requestAnimationFrame(update);
    }

    function updateScore() {
        scoreElement.innerText = score;
        levelElement.innerText = level;
        linesElement.innerText = linesCleared;
    }

    // Keyboard controls (responsive)
    let keyDown = {};
    document.addEventListener('keydown', event => {
        if (isGameOver) return;
        if (keyDown[event.code]) return; // prevent repeat
        keyDown[event.code] = true;
        switch (event.code) {
            case 'ArrowLeft': playerMove(-1); break;
            case 'ArrowRight': playerMove(1); break;
            case 'ArrowDown': playerDrop(); break;
            case 'KeyQ': playerRotate(-1); break;
            case 'KeyW': playerRotate(1); break;
        }
    });
    document.addEventListener('keyup', event => {
        keyDown[event.code] = false;
    });

    // Touch controls for mobile
    let touchStartX = null, touchStartY = null;
    let touchStartTime = null;
    canvas.addEventListener('touchstart', e => {
        if (isGameOver) return;
        if (e.touches.length === 1) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            touchStartTime = Date.now();
        }
    });
    canvas.addEventListener('touchend', e => {
        if (isGameOver) return;
        if (touchStartX === null || touchStartY === null) return;
        let dx = e.changedTouches[0].clientX - touchStartX;
        let dy = e.changedTouches[0].clientY - touchStartY;
        let dt = Date.now() - touchStartTime;
        if (Math.abs(dx) > Math.abs(dy)) {
            if (dx > 30) playerMove(1);
            else if (dx < -30) playerMove(-1);
        } else {
            if (dy > 30) playerDrop();
            else if (dy < -30) playerRotate(1);
            else if (dt < 200) playerRotate(1); // quick tap = rotate
        }
        touchStartX = touchStartY = null;
        touchStartTime = null;
    });

    function startGame() {
        isGameOver = false;
        overlay.style.display = 'none';
        arena.forEach(row => row.fill(0));
        score = 0;
        level = 1;
        linesCleared = 0;
        dropInterval = 1000;
        updateScore();
       
        playerReset();
        lastTime = performance.now();
        frameAccumulator = 0;
        resizeCanvas();
        update();
    }
    // startButton.addEventListener('click', showGameOver);
    startButton.addEventListener('click', startGame);
    restartButton.addEventListener('click',function () {
        overlay.style.display = 'none';   // Sembunyikan overlay
        isGameOver = false;
        startGame();                      // Mulai ulang game
    });

    // Inisialisasi awal
    resizeCanvas();
    context.imageSmoothingEnabled = false;
    context.setTransform(1,0,0,1,0,0);
    context.scale(canvas.width/12, canvas.height/20);

    // Focus canvas for keyboard
    canvas.addEventListener('click', () => canvas.focus());
});
</script>
<style>
@import url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');

.text-gradient {
    background: linear-gradient(90deg, #FF0D72, #0DC2FF, #0DFF72, #FFD500, #F538FF, #FF8E0D, #3877FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.bg-gradient-primary {
    background: linear-gradient(90deg, #3877FF 0%, #0DC2FF 100%) !important;
}
.bg-gradient-success {
    background: linear-gradient(90deg, #0DFF72 0%, #FFD500 100%) !important;
}
.bg-gradient-info {
    background: linear-gradient(90deg, #F538FF 0%, #FF8E0D 100%) !important;
}
.tetris-canvas-glow {
    box-shadow: 0 0 32px 4px #0DC2FF55, 0 0 0 4px #232526 inset;
    transition: box-shadow .3s;
}
.tetris-canvas-glow:focus {
    box-shadow: 0 0 48px 8px #FFD50099, 0 0 0 4px #232526 inset;
    outline: none;
}
.score-glow {
    box-shadow: 0 0 12px 2px #FFD50044;
}
.overlay-blur {
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(2px);
}
.confetti {
    position: absolute;
    top: 0;
    width: 10px;
    height: 18px;
    border-radius: 2px;
    opacity: 0.8;
    animation: confetti-fall 1.1s cubic-bezier(.6,.2,.4,1) forwards;
}
@keyframes confetti-fall {
    0% { transform: translateY(-20px) rotate(0deg);}
    80% { opacity: 1;}
    100% { transform: translateY(120px) rotate(360deg); opacity: 0;}
}
@media (max-width: 992px) {
    .game-canvas-wrapper { max-width: 95vw !important; }
}
@media (max-width: 768px) {
    .game-canvas-wrapper { max-width: 100vw !important; }
    .score-box { min-width: 90px; }
    .rounded-5 { border-radius: 1.5rem !important; }
    .rounded-4 { border-radius: 1rem !important; }
    .rounded-3 { border-radius: .7rem !important; }
    .shadow-lg { box-shadow: 0 0 32px 0 #0DC2FF33 !important; }
}
@media (max-width: 576px) {
    .game-header h2 { font-size: 1.2rem !important; }
    .score-box { font-size: 1rem !important; }
    .card { font-size: .98rem; }
}
</style>
@endpush