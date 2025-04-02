<!-- resources/views/objects/create.php -->
<?php
$title = 'Create Object';
ob_start();
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h1 class="h4 mb-0">Create New Object</h1>
                    <a href="/boards/<?= $board['id'] ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Board
                    </a>
                </div>

                <div class="card-body">
                    <?php if (isset($_SESSION['errors']) && isset($_SESSION['errors']['general'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['errors']['general'][0] ?>
                        </div>
                    <?php endif; ?>

                    <form action="/boards/<?= $board['id'] ?>/objects" method="post">
                        <div class="mb-3">
                            <label for="type" class="form-label">Object Type</label>
                            <select class="form-select <?= isset($_SESSION['errors']['type']) ? 'is-invalid' : '' ?>" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="text">Text</option>
                                <option value="note">Note</option>
                                <option value="shape">Shape</option>
                                <option value="line">Line</option>
                                <option value="connector">Connector</option>
                            </select>
                            <?php if (isset($_SESSION['errors']['type'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['type'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Text & Note Options -->
                        <div class="object-options text-options note-options d-none">
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Shape Options -->
                        <div class="object-options shape-options d-none">
                            <div class="mb-3">
                                <label class="form-label">Shape Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shape_type" id="shape-rectangle" value="rectangle" checked>
                                        <label class="form-check-label" for="shape-rectangle">
                                            Rectangle
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shape_type" id="shape-circle" value="circle">
                                        <label class="form-check-label" for="shape-circle">
                                            Circle
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shape_type" id="shape-triangle" value="triangle">
                                        <label class="form-check-label" for="shape-triangle">
                                            Triangle
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Line Options -->
                        <div class="object-options line-options d-none">
                            <div class="mb-3">
                                <label class="form-label">Line Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="line_type" id="line-straight" value="straight" checked>
                                        <label class="form-check-label" for="line-straight">
                                            Straight
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="line_type" id="line-arrow" value="arrow">
                                        <label class="form-check-label" for="line-arrow">
                                            Arrow
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="line_type" id="line-curve" value="curve">
                                        <label class="form-check-label" for="line-curve">
                                            Curve
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Common Properties -->
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <div class="row">
                                <div class="col">
                                    <label for="position-x" class="form-label">X</label>
                                    <input type="number" class="form-control" id="position-x" name="position_x" value="100">
                                </div>
                                <div class="col">
                                    <label for="position-y" class="form-label">Y</label>
                                    <input type="number" class="form-control" id="position-y" name="position_y" value="100">
                                </div>
                            </div>
                        </div>

                        <div class="object-options text-options note-options shape-options d-none">
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="width" class="form-label">Width</label>
                                    <input type="number" class="form-control" id="width" name="width" value="200">
                                </div>
                                <div class="col">
                                    <label for="height" class="form-label">Height</label>
                                    <input type="number" class="form-control" id="height" name="height" value="100">
                                </div>
                            </div>
                        </div>

                        <div class="object-options shape-options line-options d-none">
                            <div class="mb-3">
                                <label for="stroke-color" class="form-label">Stroke Color</label>
                                <input type="color" class="form-control form-control-color" id="stroke-color" name="stroke_color" value="#000000">
                            </div>

                            <div class="mb-3">
                                <label for="line-width" class="form-label">Line Width</label>
                                <input type="number" class="form-control" id="line-width" name="line_width" value="2" min="1" max="10">
                            </div>
                        </div>

                        <div class="object-options text-options note-options shape-options d-none">
                            <div class="mb-3">
                                <label for="fill-color" class="form-label">Fill Color</label>
                                <input type="color" class="form-control form-control-color" id="fill-color" name="fill_color" value="#ffffff">
                            </div>
                        </div>

                        <div class="object-options text-options note-options d-none">
                            <div class="mb-3">
                                <label for="text-color" class="form-label">Text Color</label>
                                <input type="color" class="form-control form-control-color" id="text-color" name="text_color" value="#000000">
                            </div>

                            <div class="mb-3">
                                <label for="font-size" class="form-label">Font Size</label>
                                <input type="number" class="form-control" id="font-size" name="font_size" value="16" min="8" max="72">
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="/boards/<?= $board['id'] ?>" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Object</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const typeSelect = document.getElementById('type');
        const objectOptions = document.querySelectorAll('.object-options');

        typeSelect.addEventListener('change', () => {
            const selectedType = typeSelect.value;

            // 全てのオプションを非表示
            objectOptions.forEach(option => {
                option.classList.add('d-none');
            });

            // 選択されたタイプに対応するオプションを表示
            if (selectedType) {
                const targetOptions = document.querySelectorAll(`.${selectedType}-options`);
                targetOptions.forEach(option => {
                    option.classList.remove('d-none');
                });
            }
        });

        // プロパティJSONを生成する処理
        document.querySelector('form').addEventListener('submit', (e) => {
            e.preventDefault();

            const type = typeSelect.value;
            const properties = {};

            // 共通プロパティ
            const positionX = parseFloat(document.getElementById('position-x').value);
            const positionY = parseFloat(document.getElementById('position-y').value);

            if (type === 'text' || type === 'note' || type === 'shape') {
                // 位置とサイズ
                properties.x = positionX;
                properties.y = positionY;
                properties.width = parseFloat(document.getElementById('width').value);
                properties.height = parseFloat(document.getElementById('height').value);

                // 塗りつぶし色
                properties.fillColor = document.getElementById('fill-color').value;
            }

            if (type === 'text' || type === 'note') {
                // テキスト関連
                properties.textColor = document.getElementById('text-color').value;
                properties.fontSize = parseFloat(document.getElementById('font-size').value);
            }

            if (type === 'shape') {
                // 図形タイプ
                const shapeTypeInputs = document.querySelectorAll('input[name="shape_type"]');
                for (const input of shapeTypeInputs) {
                    if (input.checked) {
                        properties.shapeType = input.value;
                        break;
                    }
                }

                // 線の色と太さ
                properties.strokeColor = document.getElementById('stroke-color').value;
                properties.lineWidth = parseFloat(document.getElementById('line-width').value);
            }

            if (type === 'line') {
                // 線の始点と終点
                properties.x1 = positionX;
                properties.y1 = positionY;
                properties.x2 = positionX + 100; // 仮の終点
                properties.y2 = positionY + 100;

                // 線のタイプ
                const lineTypeInputs = document.querySelectorAll('input[name="line_type"]');
                for (const input of lineTypeInputs) {
                    if (input.checked) {
                        properties.lineType = input.value;
                        break;
                    }
                }

                // 線の色と太さ
                properties.strokeColor = document.getElementById('stroke-color').value;
                properties.lineWidth = parseFloat(document.getElementById('line-width').value);
            }

            // プロパティをJSON化して隠しフィールドに追加
            const propertiesInput = document.createElement('input');
            propertiesInput.type = 'hidden';
            propertiesInput.name = 'properties';
            propertiesInput.value = JSON.stringify(properties);
            e.target.appendChild(propertiesInput);

            // フォーム送信
            e.target.submit();
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
unset($_SESSION['errors']);
?>