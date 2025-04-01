<!-- resources/views/boards/show.php -->
<?php
$title = "Board: {$board['title']}";

$extraStyles = <<<EOT
<style>
    body, html {
        height: 100%;
        margin: 0;
        overflow: hidden;
    }
    
    main {
        padding: 0;
        height: calc(100vh - 56px);
    }
    
    .board-container {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }
    
    #canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        cursor: default;
    }
    
    .toolbar {
        position: absolute;
        left: 16px;
        top: 16px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 8px;
        display: flex;
        flex-direction: column;
        z-index: 100;
    }
    
    .toolbar .tool-button {
        width: 40px;
        height: 40px;
        margin: 4px;
        border-radius: 4px;
        border: none;
        background-color: white;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
    }
    
    .toolbar .tool-button:hover {
        background-color: #f0f0f0;
    }
    
    .toolbar .tool-button.active {
        background-color: #e0e0e0;
    }
    
    .toolbar .divider {
        width: 40px;
        height: 1px;
        margin: 8px 4px;
        background-color: #ddd;
    }
    
    .color-panel {
        position: absolute;
        right: 16px;
        top: 16px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 12px;
        z-index: 100;
    }
    
    .color-panel .form-group {
        margin-bottom: 12px;
    }
    
    .color-panel label {
        display: block;
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .color-panel input[type="color"] {
        width: 100%;
        height: 30px;
        padding: 0;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .collaboration-panel {
        position: absolute;
        right: 16px;
        bottom: 16px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 12px;
        z-index: 100;
        width: 250px;
    }
    
    .collaboration-panel h5 {
        margin: 0 0 8px 0;
        font-size: 14px;
    }
    
    .users-list {
        max-height: 200px;
        overflow-y: auto;
    }
    
    .user-item {
        display: flex;
        align-items: center;
        padding: 4px 0;
    }
    
    .user-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 8px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .zoom-controls {
        position: absolute;
        left: 16px;
        bottom: 16px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 8px;
        display: flex;
        z-index: 100;
    }
    
    .zoom-controls button {
        width: 32px;
        height: 32px;
        border: none;
        background-color: white;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .zoom-controls .zoom-level {
        min-width: 60px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    
    /* カーソル表示用のスタイル */
    .remote-cursor {
        position: absolute;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        pointer-events: none;
        z-index: 1000;
        transform: translate(-8px, -8px);
    }
    
    .remote-cursor::after {
        content: attr(data-username);
        position: absolute;
        top: -20px;
        left: 0;
        background-color: #000;
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
    }
</style>
EOT;

$extraScripts = <<<EOT
<script src="/assets/js/canvas.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const boardId = {$board['id']};
        const userId = {$_SESSION['user_id']};
        
        // キャンバスの初期化
        const canvas = new Canvas(boardId, userId);
        
        // ズームコントロール
        const zoomInBtn = document.getElementById('zoom-in');
        const zoomOutBtn = document.getElementById('zoom-out');
        const zoomResetBtn = document.getElementById('zoom-reset');
        const zoomLevel = document.getElementById('zoom-level');
        
        zoomInBtn.addEventListener('click', () => {
            canvas.setZoom(canvas.scale * 1.2);
            updateZoomLevel();
        });
        
        zoomOutBtn.addEventListener('click', () => {
            canvas.setZoom(canvas.scale / 1.2);
            updateZoomLevel();
        });
        
        zoomResetBtn.addEventListener('click', () => {
            canvas.setZoom(1);
            updateZoomLevel();
        });
        
        function updateZoomLevel() {
            zoomLevel.textContent = `${Math . round(canvas . scale * 100)}%`;
        }
        
        // 初期表示
        updateZoomLevel();
    });
</script>
EOT;

ob_start();
?>

<div class="board-container" style="background-color: <?= $board['background_color'] ?>;">
    <canvas id="canvas"></canvas>

    <div class="toolbar">
        <button class="tool-button active" data-tool="select" title="Select">
            <i class="fas fa-mouse-pointer"></i>
        </button>
        <button class="tool-button" data-tool="text" title="Text">
            <i class="fas fa-font"></i>
        </button>
        <button class="tool-button" data-tool="note" title="Sticky Note">
            <i class="fas fa-sticky-note"></i>
        </button>

        <div class="divider"></div>

        <button class="tool-button" data-tool="shape" data-shape="rectangle" title="Rectangle">
            <i class="fas fa-square"></i>
        </button>
        <button class="tool-button" data-tool="shape" data-shape="circle" title="Circle">
            <i class="fas fa-circle"></i>
        </button>
        <button class="tool-button" data-tool="shape" data-shape="triangle" title="Triangle">
            <i class="fas fa-play"></i>
        </button>

        <div class="divider"></div>

        <button class="tool-button" data-tool="line" data-line="straight" title="Line">
            <i class="fas fa-minus"></i>
        </button>
        <button class="tool-button" data-tool="line" data-line="arrow" title="Arrow">
            <i class="fas fa-arrow-right"></i>
        </button>
        <button class="tool-button" data-tool="connector" data-line="straight" title="Connector">
            <i class="fas fa-project-diagram"></i>
        </button>
    </div>

    <div class="color-panel">
        <div class="form-group">
            <label for="fill-color">Fill Color</label>
            <input type="color" id="fill-color" value="#ffffff">
        </div>
        <div class="form-group">
            <label for="stroke-color">Stroke Color</label>
            <input type="color" id="stroke-color" value="#000000">
        </div>
        <div class="form-group">
            <label for="text-color">Text Color</label>
            <input type="color" id="text-color" value="#000000">
        </div>
    </div>

    <div class="collaboration-panel">
        <h5>Collaborators</h5>
        <div class="users-list" id="users-list">
            <div class="user-item">
                <div class="user-avatar" style="background-color: #ff5722;">
                    <?= substr($_SESSION['username'], 0, 1) ?>
                </div>
                <div class="user-name">
                    <?= htmlspecialchars($_SESSION['username']) ?> (you)
                </div>
            </div>
            <!-- 他のユーザーはJavaScriptで動的に追加 -->
        </div>
    </div>

    <div class="zoom-controls">
        <button id="zoom-out" title="Zoom Out">
            <i class="fas fa-minus"></i>
        </button>
        <div id="zoom-level" class="zoom-level">100%</div>
        <button id="zoom-in" title="Zoom In">
            <i class="fas fa-plus"></i>
        </button>
        <button id="zoom-reset" title="Reset Zoom">
            <i class="fas fa-compress"></i>
        </button>
    </div>

    <!-- ボード情報 -->
    <input type="hidden" id="board-id" value="<?= $board['id'] ?>">
    <input type="hidden" id="user-id" value="<?= $_SESSION['user_id'] ?>">
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>