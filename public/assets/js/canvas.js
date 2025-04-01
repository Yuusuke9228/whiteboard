// public/assets/js/canvas.js
class Canvas {
    constructor(boardId, userId) {
        this.boardId = boardId;
        this.userId = userId;
        this.canvas = document.getElementById('canvas');
        this.ctx = this.canvas.getContext('2d');

        // キャンバスのサイズを設定
        this.resizeCanvas();

        // オブジェクト管理
        this.objects = [];
        this.selectedObject = null;

        // ズームとパン
        this.scale = 1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.isDragging = false;
        this.lastX = 0;
        this.lastY = 0;

        // ツール
        this.currentTool = 'select'; // select, text, note, shape, line, connector
        this.shapeType = 'rectangle'; // rectangle, circle, triangle
        this.lineType = 'straight'; // straight, arrow, curve

        // 色とスタイル
        this.fillColor = '#ffffff';
        this.strokeColor = '#000000';
        this.textColor = '#000000';
        this.fontSize = 16;
        this.lineWidth = 2;

        // 編集中の状態
        this.isCreating = false;
        this.isEditing = false;
        this.editingObject = null;
        this.startX = 0;
        this.startY = 0;

        // WebSocketの設定
        this.initWebSocket();

        // イベントリスナー
        this.initEventListeners();

        // オブジェクトの読み込み
        this.loadObjects();
    }

    // キャンバスのサイズを設定
    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
        this.render();
    }

    // イベントリスナーの初期化
    initEventListeners() {
        // ウィンドウのリサイズイベント
        window.addEventListener('resize', () => this.resizeCanvas());

        // マウスイベント
        this.canvas.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        this.canvas.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        this.canvas.addEventListener('wheel', (e) => this.handleWheel(e));

        // キーボードイベント
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));

        // ツール選択
        document.querySelectorAll('.tool-button').forEach(button => {
            button.addEventListener('click', () => {
                this.currentTool = button.dataset.tool;
                this.shapeType = button.dataset.shape || this.shapeType;
                this.lineType = button.dataset.line || this.lineType;

                // ツールボタンのアクティブ状態を更新
                document.querySelectorAll('.tool-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                button.classList.add('active');
            });
        });

        // 色の選択
        document.getElementById('fill-color').addEventListener('change', (e) => {
            this.fillColor = e.target.value;
            if (this.selectedObject) {
                this.selectedObject.fillColor = this.fillColor;
                this.updateObject(this.selectedObject);
                this.render();
            }
        });

        document.getElementById('stroke-color').addEventListener('change', (e) => {
            this.strokeColor = e.target.value;
            if (this.selectedObject) {
                this.selectedObject.strokeColor = this.strokeColor;
                this.updateObject(this.selectedObject);
                this.render();
            }
        });

        document.getElementById('text-color').addEventListener('change', (e) => {
            this.textColor = e.target.value;
            if (this.selectedObject && (this.selectedObject.type === 'text' || this.selectedObject.type === 'note')) {
                this.selectedObject.textColor = this.textColor;
                this.updateObject(this.selectedObject);
                this.render();
            }
        });
    }

    // WebSocketの初期化
    initWebSocket() {
        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${wsProtocol}//${window.location.host}/ws`;

        this.socket = new WebSocket(wsUrl);

        this.socket.onopen = () => {
            console.log('WebSocket接続が確立されました');

            // ボードに参加
            this.socket.send(JSON.stringify({
                type: 'join',
                boardId: this.boardId,
                userId: this.userId
            }));
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);

            switch (data.type) {
                case 'object_created':
                    this.handleObjectCreated(data.object);
                    break;
                case 'object_updated':
                    this.handleObjectUpdated(data.object);
                    break;
                case 'object_deleted':
                    this.handleObjectDeleted(data.objectId);
                    break;
                case 'user_joined':
                    console.log(`ユーザー ${data.username} がボードに参加しました`);
                    break;
                case 'user_left':
                    console.log(`ユーザー ${data.username} がボードを離れました`);
                    break;
            }
        };

        this.socket.onclose = () => {
            console.log('WebSocket接続が閉じられました');

            // 再接続を試みる
            setTimeout(() => this.initWebSocket(), 3000);
        };

        this.socket.onerror = (error) => {
            console.error('WebSocketエラー:', error);
        };
    }

    // オブジェクトの読み込み
    async loadObjects() {
        try {
            const response = await fetch(`/api/boards/${this.boardId}/objects`);
            const data = await response.json();

            if (data.success) {
                this.objects = data.objects.map(obj => {
                    return {
                        ...obj,
                        properties: JSON.parse(obj.properties)
                    };
                });

                this.render();
            } else {
                console.error('オブジェクトの読み込みに失敗しました:', data.message);
            }
        } catch (error) {
            console.error('オブジェクトの読み込み中にエラーが発生しました:', error);
        }
    }

    // マウスダウンイベントの処理
    handleMouseDown(e) {
        const x = (e.clientX - this.offsetX) / this.scale;
        const y = (e.clientY - this.offsetY) / this.scale;

        // パンモード (スペースキーを押しながら、または中ボタンクリック)
        if (e.buttons === 4 || this.isSpaceDown) {
            this.isDragging = true;
            this.lastX = e.clientX;
            this.lastY = e.clientY;
            this.canvas.style.cursor = 'grabbing';
            return;
        }

        switch (this.currentTool) {
            case 'select':
                // オブジェクトの選択
                this.selectedObject = this.getObjectAt(x, y);

                if (this.selectedObject) {
                    // ドラッグ開始
                    this.isDragging = true;
                    this.lastX = x;
                    this.lastY = y;

                    // リサイズハンドルのチェック
                    const handle = this.getResizeHandleAt(x, y);
                    if (handle) {
                        this.resizingHandle = handle;
                    }

                    this.render();
                } else {
                    // 何も選択されていない場合はパンモードに
                    this.isDragging = true;
                    this.lastX = e.clientX;
                    this.lastY = e.clientY;
                    this.canvas.style.cursor = 'grabbing';
                }
                break;

            case 'text':
                // テキストの作成開始
                this.isCreating = true;
                this.startX = x;
                this.startY = y;
                break;

            case 'note':
                // 付箋の作成開始
                this.isCreating = true;
                this.startX = x;
                this.startY = y;
                break;

            case 'shape':
                // 図形の作成開始
                this.isCreating = true;
                this.startX = x;
                this.startY = y;
                break;

            case 'line':
                // 線の作成開始
                this.isCreating = true;
                this.startX = x;
                this.startY = y;
                break;

            case 'connector':
                // コネクタの作成開始
                this.isCreating = true;
                this.startX = x;
                this.startY = y;
                break;
        }
    }

    // マウス移動イベントの処理
    handleMouseMove(e) {
        const x = (e.clientX - this.offsetX) / this.scale;
        const y = (e.clientY - this.offsetY) / this.scale;

        if (this.isDragging) {
            if (this.selectedObject && !this.isSpaceDown && e.buttons !== 4) {
                // オブジェクトのドラッグ
                if (this.resizingHandle) {
                    // リサイズ処理
                    this.resizeSelectedObject(x, y);
                } else {
                    // 移動処理
                    const dx = x - this.lastX;
                    const dy = y - this.lastY;

                    this.selectedObject.properties.x += dx;
                    this.selectedObject.properties.y += dy;
                }

                this.lastX = x;
                this.lastY = y;
                this.render();
            } else {
                // キャンバスのパン
                const dx = e.clientX - this.lastX;
                const dy = e.clientY - this.lastY;

                this.offsetX += dx;
                this.offsetY += dy;

                this.lastX = e.clientX;
                this.lastY = e.clientY;
                this.render();
            }
        } else if (this.isCreating) {
            // オブジェクト作成中のプレビュー
            this.render();
            this.drawCreatingObject(this.startX, this.startY, x, y);
        } else {
            // カーソルの更新
            const obj = this.getObjectAt(x, y);
            if (obj) {
                this.canvas.style.cursor = 'pointer';

                // リサイズハンドル
                const handle = this.getResizeHandleAt(x, y);
                if (handle) {
                    switch (handle) {
                        case 'nw':
                        case 'se':
                            this.canvas.style.cursor = 'nwse-resize';
                            break;
                        case 'ne':
                        case 'sw':
                            this.canvas.style.cursor = 'nesw-resize';
                            break;
                        case 'n':
                        case 's':
                            this.canvas.style.cursor = 'ns-resize';
                            break;
                        case 'e':
                        case 'w':
                            this.canvas.style.cursor = 'ew-resize';
                            break;
                    }
                }
            } else {
                this.canvas.style.cursor = 'default';
            }
        }
    }

    // マウスアップイベントの処理
    handleMouseUp(e) {
        const x = (e.clientX - this.offsetX) / this.scale;
        const y = (e.clientY - this.offsetY) / this.scale;

        if (this.isCreating) {
            // オブジェクトの作成
            if (Math.abs(x - this.startX) > 5 || Math.abs(y - this.startY) > 5) {
                let newObject = null;

                switch (this.currentTool) {
                    case 'text':
                        newObject = this.createTextObject(this.startX, this.startY, x, y);
                        break;

                    case 'note':
                        newObject = this.createNoteObject(this.startX, this.startY, x, y);
                        break;

                    case 'shape':
                        newObject = this.createShapeObject(this.startX, this.startY, x, y);
                        break;

                    case 'line':
                        newObject = this.createLineObject(this.startX, this.startY, x, y);
                        break;

                    case 'connector':
                        newObject = this.createConnectorObject(this.startX, this.startY, x, y);
                        break;
                }

                if (newObject) {
                    this.createObject(newObject);
                }
            }
        } else if (this.isDragging && this.selectedObject && !this.isSpaceDown && e.button !== 1) {
            // オブジェクトの更新
            this.updateObject(this.selectedObject);
        }

        // 状態のリセット
        this.isDragging = false;
        this.isCreating = false;
        this.resizingHandle = null;
        this.canvas.style.cursor = 'default';
    }

    // ホイールイベントの処理（ズーム）
    handleWheel(e) {
        e.preventDefault();

        const zoom = e.deltaY < 0 ? 1.1 : 0.9;

        // マウス位置を基準にズーム
        const mouseX = e.clientX;
        const mouseY = e.clientY;

        // マウス位置のキャンバス上の座標（ズーム前）
        const canvasX = (mouseX - this.offsetX) / this.scale;
        const canvasY = (mouseY - this.offsetY) / this.scale;

        // スケールの更新
        this.scale *= zoom;
        this.scale = Math.max(0.1, Math.min(this.scale, 5)); // スケールの制限

        // オフセットの調整
        this.offsetX = mouseX - canvasX * this.scale;
        this.offsetY = mouseY - canvasY * this.scale;

        this.render();
    }

    // キーダウンイベントの処理
    handleKeyDown(e) {
        // スペースキーでパンモード
        if (e.code === 'Space') {
            this.isSpaceDown = true;
            this.canvas.style.cursor = 'grab';
        }

        // Deleteキーでオブジェクト削除
        if (e.code === 'Delete' && this.selectedObject) {
            this.deleteObject(this.selectedObject.id);
            this.selectedObject = null;
            this.render();
        }

        // Escapeキーで選択解除
        if (e.code === 'Escape') {
            this.selectedObject = null;
            this.isCreating = false;
            this.isEditing = false;
            this.render();
        }

        // Ctrl+Zでアンドゥ
        if (e.ctrlKey && e.code === 'KeyZ') {
            this.undo();
        }

        // Ctrl+Yでリドゥ
        if (e.ctrlKey && e.code === 'KeyY') {
            this.redo();
        }
    }

    // テキストオブジェクトの作成
    createTextObject(x1, y1, x2, y2) {
        const text = prompt('テキストを入力してください:');
        if (!text) return null;

        const minX = Math.min(x1, x2);
        const minY = Math.min(y1, y2);
        const width = Math.abs(x2 - x1);
        const height = Math.abs(y2 - y1);

        return {
            type: 'text',
            content: text,
            properties: {
                x: minX,
                y: minY,
                width: width,
                height: height,
                textColor: this.textColor,
                fontSize: this.fontSize
            }
        };
    }

    // 付箋オブジェクトの作成
    createNoteObject(x1, y1, x2, y2) {
        const text = prompt('付箋のテキストを入力してください:');
        if (!text) return null;

        const minX = Math.min(x1, x2);
        const minY = Math.min(y1, y2);
        const width = Math.abs(x2 - x1);
        const height = Math.abs(y2 - y1);

        return {
            type: 'note',
            content: text,
            properties: {
                x: minX,
                y: minY,
                width: Math.max(width, 100),
                height: Math.max(height, 100),
                fillColor: this.fillColor,
                textColor: this.textColor,
                fontSize: this.fontSize
            }
        };
    }

    // 図形オブジェクトの作成
    createShapeObject(x1, y1, x2, y2) {
        const minX = Math.min(x1, x2);
        const minY = Math.min(y1, y2);
        const width = Math.abs(x2 - x1);
        const height = Math.abs(y2 - y1);

        return {
            type: 'shape',
            content: null,
            properties: {
                x: minX,
                y: minY,
                width: width,
                height: height,
                shapeType: this.shapeType,
                fillColor: this.fillColor,
                strokeColor: this.strokeColor,
                lineWidth: this.lineWidth
            }
        };
    }

    // 線オブジェクトの作成
    createLineObject(x1, y1, x2, y2) {
        return {
            type: 'line',
            content: null,
            properties: {
                x1: x1,
                y1: y1,
                x2: x2,
                y2: y2,
                lineType: this.lineType,
                strokeColor: this.strokeColor,
                lineWidth: this.lineWidth
            }
        };
    }

    // コネクタオブジェクトの作成
    createConnectorObject(x1, y1, x2, y2) {
        // 接続先オブジェクトの検索
        const startObject = this.getObjectAt(x1, y1);
        const endObject = this.getObjectAt(x2, y2);

        if (!startObject || !endObject || startObject === endObject) {
            return null;
        }

        return {
            type: 'connector',
            content: null,
            properties: {
                startObjectId: startObject.id,
                endObjectId: endObject.id,
                startPoint: { x: x1, y: y1 },
                endPoint: { x: x2, y: y2 },
                lineType: this.lineType,
                strokeColor: this.strokeColor,
                lineWidth: this.lineWidth
            }
        };
    }

    // オブジェクトの作成（API呼び出し）
    async createObject(object) {
        try {
            const response = await fetch(`/api/boards/${this.boardId}/objects`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: object.type,
                    content: object.content,
                    properties: JSON.stringify(object.properties)
                })
            });

            const data = await response.json();

            if (data.success) {
                // ローカルでオブジェクトを追加
                object.id = data.objectId;
                this.objects.push(object);
                this.selectedObject = object;
                this.render();
            } else {
                console.error('オブジェクトの作成に失敗しました:', data.message);
            }
        } catch (error) {
            console.error('オブジェクトの作成中にエラーが発生しました:', error);
        }
    }

    // オブジェクトの更新（API呼び出し）
    async updateObject(object) {
        try {
            const response = await fetch(`/api/boards/${this.boardId}/objects/${object.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: object.type,
                    content: object.content,
                    properties: JSON.stringify(object.properties)
                })
            });

            const data = await response.json();

            if (!data.success) {
                console.error('オブジェクトの更新に失敗しました:', data.message);
            }
        } catch (error) {
            console.error('オブジェクトの更新中にエラーが発生しました:', error);
        }
    }

    // オブジェクトの削除（API呼び出し）
    async deleteObject(objectId) {
        try {
            const response = await fetch(`/api/boards/${this.boardId}/objects/${objectId}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                // ローカルでオブジェクトを削除
                this.objects = this.objects.filter(obj => obj.id !== objectId);
                this.render();
            } else {
                console.error('オブジェクトの削除に失敗しました:', data.message);
            }
        } catch (error) {
            console.error('オブジェクトの削除中にエラーが発生しました:', error);
        }
    }

    // WebSocketからのオブジェクト作成通知の処理
    handleObjectCreated(object) {
        // 自分が作成したオブジェクトは既に追加済み
        if (object.user_id === this.userId) return;

        // プロパティを解析
        object.properties = JSON.parse(object.properties);

        // ローカルでオブジェクトを追加
        this.objects.push(object);
        this.render();
    }

    // WebSocketからのオブジェクト更新通知の処理
    handleObjectUpdated(object) {
        // 自分が更新したオブジェクトは既に更新済み
        if (object.user_id === this.userId) return;

        // プロパティを解析
        object.properties = JSON.parse(object.properties);

        // ローカルでオブジェクトを更新
        const index = this.objects.findIndex(obj => obj.id === object.id);
        if (index !== -1) {
            this.objects[index] = object;
            this.render();
        }
    }

    // WebSocketからのオブジェクト削除通知の処理
    handleObjectDeleted(objectId) {
        // 自分が削除したオブジェクトは既に削除済み
        const deletedObj = this.objects.find(obj => obj.id === objectId);
        if (!deletedObj || deletedObj.user_id === this.userId) return;

        // ローカルでオブジェクトを削除
        this.objects = this.objects.filter(obj => obj.id !== objectId);

        // 選択中のオブジェクトだった場合は選択解除
        if (this.selectedObject && this.selectedObject.id === objectId) {
            this.selectedObject = null;
        }

        this.render();
    }

    // キャンバスの描画
    render() {
        // キャンバスをクリア
        this.ctx.fillStyle = '#f0f0f0';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

        // グリッドの描画
        this.drawGrid();

        // 変換行列の設定
        this.ctx.save();
        this.ctx.translate(this.offsetX, this.offsetY);
        this.ctx.scale(this.scale, this.scale);

        // オブジェクトの描画
        this.objects.forEach(obj => {
            this.drawObject(obj);
        });

        // 選択オブジェクトの枠とハンドルの描画
        if (this.selectedObject) {
            this.drawSelectionFrame(this.selectedObject);
        }

        // 変換行列をリセット
        this.ctx.restore();
    }

    // グリッドの描画
    drawGrid() {
        const gridSize = 20 * this.scale;
        const offsetX = this.offsetX % gridSize;
        const offsetY = this.offsetY % gridSize;

        this.ctx.strokeStyle = '#e0e0e0';
        this.ctx.lineWidth = 0.5;

        // 縦線
        for (let x = offsetX; x < this.canvas.width; x += gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(x, 0);
            this.ctx.lineTo(x, this.canvas.height);
            this.ctx.stroke();
        }

        // 横線
        for (let y = offsetY; y < this.canvas.height; y += gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(0, y);
            this.ctx.lineTo(this.canvas.width, y);
            this.ctx.stroke();
        }
    }

    // オブジェクトの描画
    drawObject(obj) {
        switch (obj.type) {
            case 'text':
                this.drawTextObject(obj);
                break;

            case 'note':
                this.drawNoteObject(obj);
                break;

            case 'shape':
                this.drawShapeObject(obj);
                break;

            case 'line':
                this.drawLineObject(obj);
                break;

            case 'connector':
                this.drawConnectorObject(obj);
                break;
        }
    }

    // 作成中のオブジェクトの描画
    drawCreatingObject(x1, y1, x2, y2) {
        switch (this.currentTool) {
            case 'text':
                this.drawCreatingText(x1, y1, x2, y2);
                break;

            case 'note':
                this.drawCreatingNote(x1, y1, x2, y2);
                break;

            case 'shape':
                this.drawCreatingShape(x1, y1, x2, y2);
                break;

            case 'line':
                this.drawCreatingLine(x1, y1, x2, y2);
                break;

            case 'connector':
                this.drawCreatingConnector(x1, y1, x2, y2);
                break;
        }
    }

    // テキストオブジェクトの描画
    drawTextObject(obj) {
        const props = obj.properties;

        this.ctx.font = `${props.fontSize}px Arial`;
        this.ctx.fillStyle = props.textColor;
        this.ctx.textBaseline = 'top';

        // テキストを折り返して描画
        const words = obj.content.split(' ');
        let line = '';
        let lineHeight = props.fontSize * 1.2;
        let y = props.y;

        for (let i = 0; i < words.length; i++) {
            const testLine = line + words[i] + ' ';
            const metrics = this.ctx.measureText(testLine);

            if (metrics.width > props.width && i > 0) {
                this.ctx.fillText(line, props.x, y);
                line = words[i] + ' ';
                y += lineHeight;
            } else {
                line = testLine;
            }
        }

        this.ctx.fillText(line, props.x, y);
    }

    // 付箋オブジェクトの描画
    drawNoteObject(obj) {
        const props = obj.properties;

        // 付箋の背景
        this.ctx.fillStyle = props.fillColor;
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 1;

        // 影の描画
        this.ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
        this.ctx.shadowBlur = 5;
        this.ctx.shadowOffsetX = 2;
        this.ctx.shadowOffsetY = 2;

        this.ctx.beginPath();
        this.ctx.rect(props.x, props.y, props.width, props.height);
        this.ctx.fill();
        this.ctx.stroke();

        // 影をリセット
        this.ctx.shadowColor = 'transparent';
        this.ctx.shadowBlur = 0;
        this.ctx.shadowOffsetX = 0;
        this.ctx.shadowOffsetY = 0;

        // テキストの描画
        this.ctx.font = `${props.fontSize}px Arial`;
        this.ctx.fillStyle = props.textColor;
        this.ctx.textBaseline = 'top';

        // テキストを折り返して描画
        const words = obj.content.split(' ');
        let line = '';
        let lineHeight = props.fontSize * 1.2;
        let y = props.y + 10; // 上部にパディングを追加

        for (let i = 0; i < words.length; i++) {
            const testLine = line + words[i] + ' ';
            const metrics = this.ctx.measureText(testLine);

            if (metrics.width > props.width - 20 && i > 0) { // 左右にパディングを追加
                this.ctx.fillText(line, props.x + 10, y);
                line = words[i] + ' ';
                y += lineHeight;
            } else {
                line = testLine;
            }
        }

        this.ctx.fillText(line, props.x + 10, y);
    }

    // 図形オブジェクトの描画
    drawShapeObject(obj) {
        const props = obj.properties;

        this.ctx.fillStyle = props.fillColor;
        this.ctx.strokeStyle = props.strokeColor;
        this.ctx.lineWidth = props.lineWidth;

        switch (props.shapeType) {
            case 'rectangle':
                this.ctx.beginPath();
                this.ctx.rect(props.x, props.y, props.width, props.height);
                this.ctx.fill();
                this.ctx.stroke();
                break;

            case 'circle':
                const centerX = props.x + props.width / 2;
                const centerY = props.y + props.height / 2;
                const radius = Math.min(props.width, props.height) / 2;

                this.ctx.beginPath();
                this.ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                this.ctx.fill();
                this.ctx.stroke();
                break;

            case 'triangle':
                this.ctx.beginPath();
                this.ctx.moveTo(props.x + props.width / 2, props.y);
                this.ctx.lineTo(props.x + props.width, props.y + props.height);
                this.ctx.lineTo(props.x, props.y + props.height);
                this.ctx.closePath();
                this.ctx.fill();
                this.ctx.stroke();
                break;
        }
    }

    // 線オブジェクトの描画
    drawLineObject(obj) {
        const props = obj.properties;

        this.ctx.strokeStyle = props.strokeColor;
        this.ctx.lineWidth = props.lineWidth;

        switch (props.lineType) {
            case 'straight':
                this.ctx.beginPath();
                this.ctx.moveTo(props.x1, props.y1);
                this.ctx.lineTo(props.x2, props.y2);
                this.ctx.stroke();
                break;

            case 'arrow':
                // 矢印の描画
                const angle = Math.atan2(props.y2 - props.y1, props.x2 - props.x1);
                const headLength = 15; // 矢印の頭の長さ

                this.ctx.beginPath();
                this.ctx.moveTo(props.x1, props.y1);
                this.ctx.lineTo(props.x2, props.y2);
                this.ctx.stroke();

                // 矢印の頭
                this.ctx.beginPath();
                this.ctx.moveTo(props.x2, props.y2);
                this.ctx.lineTo(
                    props.x2 - headLength * Math.cos(angle - Math.PI / 6),
                    props.y2 - headLength * Math.sin(angle - Math.PI / 6)
                );
                this.ctx.lineTo(
                    props.x2 - headLength * Math.cos(angle + Math.PI / 6),
                    props.y2 - headLength * Math.sin(angle + Math.PI / 6)
                );
                this.ctx.closePath();
                this.ctx.fill();
                break;

            case 'curve':
                // ベジェ曲線の描画
                const midX = (props.x1 + props.x2) / 2;
                const midY = (props.y1 + props.y2) / 2 - 50; // 上に湾曲

                this.ctx.beginPath();
                this.ctx.moveTo(props.x1, props.y1);
                this.ctx.quadraticCurveTo(midX, midY, props.x2, props.y2);
                this.ctx.stroke();
                break;
        }
    }

    // コネクタオブジェクトの描画
    drawConnectorObject(obj) {
        const props = obj.properties;

        // 接続元と接続先のオブジェクトを取得
        const startObject = this.objects.find(o => o.id === props.startObjectId);
        const endObject = this.objects.find(o => o.id === props.endObjectId);

        if (!startObject || !endObject) return;

        // 接続点の計算
        const startPoint = this.calculateConnectionPoint(startObject, endObject);
        const endPoint = this.calculateConnectionPoint(endObject, startObject);

        this.ctx.strokeStyle = props.strokeColor;
        this.ctx.lineWidth = props.lineWidth;

        switch (props.lineType) {
            case 'straight':
                this.ctx.beginPath();
                this.ctx.moveTo(startPoint.x, startPoint.y);
                this.ctx.lineTo(endPoint.x, endPoint.y);
                this.ctx.stroke();
                break;

            case 'arrow':
                // 矢印の描画
                const angle = Math.atan2(endPoint.y - startPoint.y, endPoint.x - startPoint.x);
                const headLength = 15; // 矢印の頭の長さ

                this.ctx.beginPath();
                this.ctx.moveTo(startPoint.x, startPoint.y);
                this.ctx.lineTo(endPoint.x, endPoint.y);
                this.ctx.stroke();

                // 矢印の頭
                this.ctx.beginPath();
                this.ctx.moveTo(endPoint.x, endPoint.y);
                this.ctx.lineTo(
                    endPoint.x - headLength * Math.cos(angle - Math.PI / 6),
                    endPoint.y - headLength * Math.sin(angle - Math.PI / 6)
                );
                this.ctx.lineTo(
                    endPoint.x - headLength * Math.cos(angle + Math.PI / 6),
                    endPoint.y - headLength * Math.sin(angle + Math.PI / 6)
                );
                this.ctx.closePath();
                this.ctx.fill();
                break;

            case 'curve':
                // ベジェ曲線の描画
                const dist = Math.sqrt(
                    Math.pow(endPoint.x - startPoint.x, 2) +
                    Math.pow(endPoint.y - startPoint.y, 2)
                );
                const midX = (startPoint.x + endPoint.x) / 2;
                const midY = (startPoint.y + endPoint.y) / 2 - dist / 3; // 上に湾曲

                this.ctx.beginPath();
                this.ctx.moveTo(startPoint.x, startPoint.y);
                this.ctx.quadraticCurveTo(midX, midY, endPoint.x, endPoint.y);
                this.ctx.stroke();
                break;
        }
    }

    // オブジェクトの接続点を計算
    calculateConnectionPoint(obj1, obj2) {
        const props1 = obj1.properties;

        // オブジェクトの中心
        const center1 = {
            x: props1.x + (props1.width || 0) / 2,
            y: props1.y + (props1.height || 0) / 2
        };

        const props2 = obj2.properties;

        // オブジェクトの中心
        const center2 = {
            x: props2.x + (props2.width || 0) / 2,
            y: props2.y + (props2.height || 0) / 2
        };

        // オブジェクト間の角度
        const angle = Math.atan2(center2.y - center1.y, center2.x - center1.x);

        // オブジェクトの種類に応じて接続点を計算
        if (obj1.type === 'shape' && props1.shapeType === 'circle') {
            const radius = Math.min(props1.width, props1.height) / 2;
            return {
                x: center1.x + Math.cos(angle) * radius,
                y: center1.y + Math.sin(angle) * radius
            };
        } else {
            // 矩形の場合は、辺との交点を計算
            const halfWidth = (props1.width || 0) / 2;
            const halfHeight = (props1.height || 0) / 2;

            // tanθに基づいて接続点を決定
            const absAngle = Math.abs(angle);

            if (absAngle < Math.PI / 4 || absAngle > Math.PI * 3 / 4) {
                // 右または左の辺
                const x = angle > -Math.PI / 2 && angle < Math.PI / 2 ?
                    center1.x + halfWidth : center1.x - halfWidth;
                const y = center1.y + Math.tan(angle) * (x - center1.x);
                return { x, y };
            } else {
                // 上または下の辺
                const y = angle > 0 ? center1.y + halfHeight : center1.y - halfHeight;
                const x = center1.x + (y - center1.y) / Math.tan(angle);
                return { x, y };
            }
        }
    }

    // 創作中のテキストの描画
    drawCreatingText(x1, y1, x2, y2) {
        const minX = Math.min(x1, x2);
        const minY = Math.min(y1, y2);
        const width = Math.abs(x2 - x1);
        const height = Math.abs(y2 - y1);

        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 1;
        this.ctx.setLineDash([5, 5]);
        this.ctx.strokeRect(minX, minY, width, height);
        this.ctx.setLineDash([]);
    }

    // 創作中の付箋の描画
    drawCreatingNote(x1, y1, x2, y2) {
        const minX = Math.min(x1, x2);
        const minY = Math.min(y1, y2);
        const width = Math.abs(x2 - x1);
        const height = Math.abs(y2 - y1);

        this.ctx.fillStyle = this.fillColor;
        this.ctx.strokeStyle = '#000000';
        this.ctx.lineWidth = 1;

        this.ctx.beginPath();
        this.ctx.rect(minX, minY, width, height);
        this.ctx.globalAlpha = 0.5;
        this.ctx.fill();
        this.ctx.globalAlpha = 1.0;
        this.ctx.stroke();
    }

    // 創作中の図形の描画
    drawCreatingShape(x1, y1, x2, y2) {
        const minX = Math.min(x1, x2);
        const minY = Math.min(y1, y2);
        const width = Math.abs(x2 - x1);
        const height = Math.abs(y2 - y1);

        this.ctx.fillStyle = this.fillColor;
        this.ctx.strokeStyle = this.strokeColor;
        this.ctx.lineWidth = this.lineWidth;
        this.ctx.globalAlpha = 0.5;

        switch (this.shapeType) {
            case 'rectangle':
                this.ctx.beginPath();
                this.ctx.rect(minX, minY, width, height);
                this.ctx.fill();
                this.ctx.stroke();
                break;

            case 'circle':
                const centerX = (x1 + x2) / 2;
                const centerY = (y1 + y2) / 2;
                const radius = Math.min(width, height) / 2;

                this.ctx.beginPath();
                this.ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                this.ctx.fill();
                this.ctx.stroke();
                break;

            case 'triangle':
                this.ctx.beginPath();
                this.ctx.moveTo(minX + width / 2, minY);
                this.ctx.lineTo(minX + width, minY + height);
                this.ctx.lineTo(minX, minY + height);
                this.ctx.closePath();
                this.ctx.fill();
                this.ctx.stroke();
                break;
        }

        this.ctx.globalAlpha = 1.0;
    }

    // 創作中の線の描画
    drawCreatingLine(x1, y1, x2, y2) {
        this.ctx.strokeStyle = this.strokeColor;
        this.ctx.lineWidth = this.lineWidth;

        switch (this.lineType) {
            case 'straight':
                this.ctx.beginPath();
                this.ctx.moveTo(x1, y1);
                this.ctx.lineTo(x2, y2);
                this.ctx.stroke();
                break;

            case 'arrow':
                // 矢印の描画
                const angle = Math.atan2(y2 - y1, x2 - x1);
                const headLength = 15; // 矢印の頭の長さ

                this.ctx.beginPath();
                this.ctx.moveTo(x1, y1);
                this.ctx.lineTo(x2, y2);
                this.ctx.stroke();

                // 矢印の頭
                this.ctx.beginPath();
                this.ctx.moveTo(x2, y2);
                this.ctx.lineTo(
                    x2 - headLength * Math.cos(angle - Math.PI / 6),
                    y2 - headLength * Math.sin(angle - Math.PI / 6)
                );
                this.ctx.lineTo(
                    x2 - headLength * Math.cos(angle + Math.PI / 6),
                    y2 - headLength * Math.sin(angle + Math.PI / 6)
                );
                this.ctx.closePath();
                this.ctx.fill();
                break;

            case 'curve':
                // ベジェ曲線の描画
                const midX = (x1 + x2) / 2;
                const midY = (y1 + y2) / 2 - 50; // 上に湾曲

                this.ctx.beginPath();
                this.ctx.moveTo(x1, y1);
                this.ctx.quadraticCurveTo(midX, midY, x2, y2);
                this.ctx.stroke();
                break;
        }
    }

    // 創作中のコネクタの描画
    drawCreatingConnector(x1, y1, x2, y2) {
        // 通常の線と同じ描画
        this.drawCreatingLine(x1, y1, x2, y2);
    }

    // 選択枠の描画
    drawSelectionFrame(obj) {
        const props = obj.properties;

        if (obj.type === 'line' || obj.type === 'connector') {
            // 線の場合は端点に選択ハンドルを描画
            this.drawSelectionHandle(props.x1, props.y1);
            this.drawSelectionHandle(props.x2, props.y2);
            return;
        }

        // 矩形の選択枠
        this.ctx.strokeStyle = '#1e90ff';
        this.ctx.lineWidth = 2;
        this.ctx.setLineDash([5, 5]);
        this.ctx.strokeRect(props.x, props.y, props.width, props.height);
        this.ctx.setLineDash([]);

        // リサイズハンドルの描画
        this.drawResizeHandles(props.x, props.y, props.width, props.height);
    }

    // リサイズハンドルの描画
    drawResizeHandles(x, y, width, height) {
        const handles = {
            'nw': { x: x, y: y },
            'n': { x: x + width / 2, y: y },
            'ne': { x: x + width, y: y },
            'e': { x: x + width, y: y + height / 2 },
            'se': { x: x + width, y: y + height },
            's': { x: x + width / 2, y: y + height },
            'sw': { x: x, y: y + height },
            'w': { x: x, y: y + height / 2 }
        };

        for (const handle in handles) {
            this.drawSelectionHandle(handles[handle].x, handles[handle].y);
        }
    }

    // 選択ハンドルの描画
    drawSelectionHandle(x, y) {
        const size = 8;

        this.ctx.fillStyle = '#ffffff';
        this.ctx.strokeStyle = '#1e90ff';
        this.ctx.lineWidth = 2;

        this.ctx.beginPath();
        this.ctx.rect(x - size / 2, y - size / 2, size, size);
        this.ctx.fill();
        this.ctx.stroke();
    }

    // 位置(x, y)のオブジェクトを取得
    getObjectAt(x, y) {
        // 配列を逆順に走査（前面のオブジェクトを先に取得）
        for (let i = this.objects.length - 1; i >= 0; i--) {
            const obj = this.objects[i];

            if (this.isPointInObject(x, y, obj)) {
                return obj;
            }
        }

        return null;
    }

    // 位置(x, y)がオブジェクト内かどうかを判定
    isPointInObject(x, y, obj) {
        const props = obj.properties;

        switch (obj.type) {
            case 'text':
            case 'note':
            case 'shape':
                // 矩形の判定
                if (props.shapeType === 'circle') {
                    // 円の場合
                    const centerX = props.x + props.width / 2;
                    const centerY = props.y + props.height / 2;
                    const radius = Math.min(props.width, props.height) / 2;

                    const distance = Math.sqrt(
                        Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2)
                    );

                    return distance <= radius;
                } else if (props.shapeType === 'triangle') {
                    // 三角形の場合
                    const p1 = { x: props.x + props.width / 2, y: props.y };
                    const p2 = { x: props.x + props.width, y: props.y + props.height };
                    const p3 = { x: props.x, y: props.y + props.height };

                    return this.isPointInTriangle(x, y, p1, p2, p3);
                } else {
                    // 矩形の場合
                    return x >= props.x && x <= props.x + props.width &&
                        y >= props.y && y <= props.y + props.height;
                }

            case 'line':
            case 'connector':
                // 線の場合
                const x1 = props.x1;
                const y1 = props.y1;
                const x2 = props.x2;
                const y2 = props.y2;

                // 線分との距離を計算
                const lineLength = Math.sqrt(
                    Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2)
                );

                if (lineLength === 0) return false;

                const dot = ((x - x1) * (x2 - x1) + (y - y1) * (y2 - y1)) / Math.pow(lineLength, 2);

                if (dot < 0 || dot > 1) return false;

                const closestX = x1 + dot * (x2 - x1);
                const closestY = y1 + dot * (y2 - y1);

                const distance = Math.sqrt(
                    Math.pow(x - closestX, 2) + Math.pow(y - closestY, 2)
                );

                return distance <= 5; // 線の太さに応じて調整
        }

        return false;
    }

    // 点が三角形内にあるかどうかを判定
    isPointInTriangle(x, y, p1, p2, p3) {
        const area = 0.5 * Math.abs(
            (p1.x * (p2.y - p3.y) + p2.x * (p3.y - p1.y) + p3.x * (p1.y - p2.y))
        );

        const area1 = 0.5 * Math.abs(
            (x * (p2.y - p3.y) + p2.x * (p3.y - y) + p3.x * (y - p2.y))
        );

        const area2 = 0.5 * Math.abs(
            (p1.x * (y - p3.y) + x * (p3.y - p1.y) + p3.x * (p1.y - y))
        );

        const area3 = 0.5 * Math.abs(
            (p1.x * (p2.y - y) + p2.x * (y - p1.y) + x * (p1.y - p2.y))
        );

        return Math.abs(area - (area1 + area2 + area3)) < 0.01;
    }

    // リサイズハンドルの取得
    getResizeHandleAt(x, y) {
        if (!this.selectedObject) return null;

        const props = this.selectedObject.properties;

        if (this.selectedObject.type === 'line' || this.selectedObject.type === 'connector') {
            return null;
        }

        const handleSize = 8;
        const handles = {
            'nw': { x: props.x, y: props.y },
            'n': { x: props.x + props.width / 2, y: props.y },
            'ne': { x: props.x + props.width, y: props.y },
            'e': { x: props.x + props.width, y: props.y + props.height / 2 },
            'se': { x: props.x + props.width, y: props.y + props.height },
            's': { x: props.x + props.width / 2, y: props.y + props.height },
            'sw': { x: props.x, y: props.y + props.height },
            'w': { x: props.x, y: props.y + props.height / 2 }
        };

        for (const handle in handles) {
            const hx = handles[handle].x;
            const hy = handles[handle].y;

            if (x >= hx - handleSize / 2 && x <= hx + handleSize / 2 &&
                y >= hy - handleSize / 2 && y <= hy + handleSize / 2) {
                return handle;
            }
        }

        return null;
    }

    // オブジェクトのリサイズ
    resizeSelectedObject(x, y) {
        if (!this.selectedObject || !this.resizingHandle) return;

        const props = this.selectedObject.properties;

        // 元の位置とサイズを保存
        const originalX = props.x;
        const originalY = props.y;
        const originalWidth = props.width;
        const originalHeight = props.height;

        // リサイズ処理
        switch (this.resizingHandle) {
            case 'nw':
                props.width += props.x - x;
                props.height += props.y - y;
                props.x = x;
                props.y = y;
                break;

            case 'n':
                props.height += props.y - y;
                props.y = y;
                break;

            case 'ne':
                props.width = x - props.x;
                props.height += props.y - y;
                props.y = y;
                break;

            case 'e':
                props.width = x - props.x;
                break;

            case 'se':
                props.width = x - props.x;
                props.height = y - props.y;
                break;

            case 's':
                props.height = y - props.y;
                break;

            case 'sw':
                props.width += props.x - x;
                props.height = y - props.y;
                props.x = x;
                break;

            case 'w':
                props.width += props.x - x;
                props.x = x;
                break;
        }

        // 最小サイズの制限
        if (props.width < 10) {
            props.width = 10;
            props.x = originalX + originalWidth - 10;
        }

        if (props.height < 10) {
            props.height = 10;
            props.y = originalY + originalHeight - 10;
        }
    }
}

// Websocket サーバー通信クラス
class WebSocketService {
    constructor(boardId, userId) {
        this.boardId = boardId;
        this.userId = userId;
        this.callbacks = {};
        this.connected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;

        this.connect();
    }

    connect() {
        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${wsProtocol}//${window.location.host}/ws`;

        this.socket = new WebSocket(wsUrl);

        this.socket.onopen = () => {
            console.log('WebSocket接続が確立されました');
            this.connected = true;
            this.reconnectAttempts = 0;

            // ボードに参加
            this.send({
                type: 'join',
                boardId: this.boardId,
                userId: this.userId
            });

            if (this.callbacks.onConnect) {
                this.callbacks.onConnect();
            }
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);

            if (this.callbacks[data.type]) {
                this.callbacks[data.type](data);
            }
        };

        this.socket.onclose = () => {
            console.log('WebSocket接続が閉じられました');
            this.connected = false;

            if (this.callbacks.onDisconnect) {
                this.callbacks.onDisconnect();
            }

            // 再接続
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                setTimeout(() => this.connect(), this.reconnectDelay);
            }
        };

        this.socket.onerror = (error) => {
            console.error('WebSocketエラー:', error);

            if (this.callbacks.onError) {
                this.callbacks.onError(error);
            }
        };
    }

    send(data) {
        if (this.connected) {
            this.socket.send(JSON.stringify(data));
        } else {
            console.warn('WebSocketが接続されていません。メッセージを送信できません。');
        }
    }

    on(event, callback) {
        this.callbacks[event] = callback;
    }

    close() {
        if (this.socket) {
            this.socket.close();
        }
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    const boardId = document.getElementById('board-id').value;
    const userId = document.getElementById('user-id').value;

    const canvas = new Canvas(boardId, userId);
});