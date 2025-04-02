<!-- resources/views/objects/edit.php -->
<?php
$title = 'Edit Object';
ob_start();

// プロパティを取得して変数に格納
$properties = json_decode($object['properties'], true);
$type = $object['type'];
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h1 class="h4 mb-0">Edit Object</h1>
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

                    <form action="/boards/<?= $board['id'] ?>/objects/<?= $object['id'] ?>" method="post">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="mb-3">
                            <label for="type" class="form-label">Object Type</label>
                            <select class="form-select <?= isset($_SESSION['errors']['type']) ? 'is-invalid' : '' ?>" id="type" name="type" required disabled>
                                <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>Text</option>
                                <option value="note" <?= $type === 'note' ? 'selected' : '' ?>>Note</option>
                                <option value="shape" <?= $type === 'shape' ? 'selected' : '' ?>>Shape</option>
                                <option value="line" <?= $type === 'line' ? 'selected' : '' ?>>Line</option>
                                <option value="connector" <?= $type === 'connector' ? 'selected' : '' ?>>Connector</option>
                            </select>
                            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                            <?php if (isset($_SESSION['errors']['type'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['type'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Text & Note Options -->
                        <?php if ($type === 'text' || $type === 'note'): ?>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="3"><?= htmlspecialchars($object['content']) ?></textarea>
                            </div>
                        <?php endif; ?>

                        <!-- Shape Options -->
                        <?php if ($type === 'shape'): ?>
                            <div class="mb-3">
                                <label class="form-label">Shape Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shape_type" id="shape-rectangle" value="rectangle" <?= $properties['shapeType'] === 'rectangle' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="shape-rectangle">
                                            Rectangle
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shape_type" id="shape-circle" value="circle" <?= $properties['shapeType'] === 'circle' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="shape-circle">
                                            Circle
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shape_type" id="shape-triangle" value="triangle" <?= $properties['shapeType'] === 'triangle' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="shape-triangle">
                                            Triangle
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Line Options -->
                        <?php if ($type === 'line'): ?>
                            <div class="mb-3">
                                <label class="form-label">Line Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="line_type" id="line-straight" value="straight" <?= $properties['lineType'] === 'straight' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="line-straight">
                                            Straight
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="line_type" id="line-arrow" value="arrow" <?= $properties['lineType'] === 'arrow' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="line-arrow">
                                            Arrow
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="line_type" id="line-curve" value="curve" <?= $properties['lineType'] === 'curve' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="line-curve">
                                            Curve
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Common Properties for text, note, shape -->
                        <?php if ($type === 'text' || $type === 'note' || $type === 'shape'): ?>
                            <div class="mb-3">
                                <label class="form-label">Position</label>
                                <div class="row">
                                    <div class="col">
                                        <label for="position-x" class="form-label">X</label>
                                        <input type="number" class="form-control" id="position-x" name="position_x" value="<?= $properties['x'] ?>">
                                    </div>
                                    <div class="col">
                                        <label for="position-y" class="form-label">Y</label>
                                        <input type="number" class="form-control" id="position-y" name="position_y" value="<?= $properties['y'] ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label for="width" class="form-label">Width</label>
                                    <input type="number" class="form-control" id="width" name="width" value="<?= $properties['width'] ?>">
                                </div>
                                <div class="col">
                                    <label for="height" class="form-label">Height</label>
                                    <input type="number" class="form-control" id="height" name="height" value="<?= $properties['height'] ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Line properties -->
                        <?php if ($type === 'line'): ?>
                            <div class="mb-3">
                                <label class="form-label">Start Point</label>
                                <div class="row">
                                    <div class="col">
                                        <label for="x1" class="form-label">X1</label>
                                        <input type="number" class="form-control" id="x1" name="x1" value="<?= $properties['x1'] ?>">
                                    </div>
                                    <div class="col">
                                        <label for="y1" class="form-label">Y1</label>
                                        <input type="number" class="form-control" id="y1" name="y1" value="<?= $properties['y1'] ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">End Point</label>
                                <div class="row">
                                    <div class="col">
                                        <label for="x2" class="form-label">X2</label>
                                        <input type="number" class="form-control" id="x2" name="x2" value="<?= $properties['x2'] ?>">
                                    </div>
                                    <div class="col">
                                        <label for="y2" class="form-label">Y2</label>
                                        <input type="number" class="form-control" id="y2" name="y2" value="<?= $properties['y2'] ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Stroke & Line properties -->
                        <?php if ($type === 'shape' || $type === 'line'): ?>
                            <div class="mb-3">
                                <label for="stroke-color" class="form-label">Stroke Color</label>
                                <input type="color" class="form-control form-control-color" id="stroke-color" name="stroke_color" value="<?= $properties['strokeColor'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="line-width" class="form-label">Line Width</label>
                                <input type="number" class="form-control" id="line-width" name="line_width" value="<?= $properties['lineWidth'] ?>" min="1" max="10">
                            </div>
                        <?php endif; ?>

                        <!-- Fill color for shapes -->
                        <?php if ($type === 'text' || $type === 'note' || $type === 'shape'): ?>
                            <div class="mb-3">
                                <label for="fill-color" class="form-label">Fill Color</label>
                                <input type="color" class="form-control form-control-color" id="fill-color" name="fill_color" value="<?= $properties['fillColor'] ?? '#ffffff' ?>">
                            </div>
                        <?php endif; ?>

                        <!-- Text styling -->
                        <?php if ($type === 'text' || $type === 'note'): ?>
                            <div class="mb-3">
                                <label for="text-color" class="form-label">Text Color</label>
                                <input type="color" class="form-control form-control-color" id="text-color" name="text_color" value="<?= $properties['textColor'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="font-size" class="form-label">Font Size</label>
                                <input type="number" class="form-control" id="font-size" name="font_size" value="<?= $properties['fontSize'] ?>" min="8" max="72">
                            </div>
                        <?php endif; ?>

                        <div class="text-end">
                            <a href="/boards/<?= $board['id'] ?>" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Object</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h2 class="h5 mb-0">Delete Object</h2>
                </div>
                <div class="card-body">
                    <p>Once you delete an object, there is no going back. Please be certain.</p>
                    <form action="/boards/<?= $board['id'] ?>/objects/<?= $object['id'] ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this object?');">
                        <input type="hidden" name="_method" value="DELETE">
                        <div class="text-end">
                            <button type="submit" class="btn btn-danger">Delete Object</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // プロパティJSONを生成する処理
        document.querySelector('form').addEventListener('submit', (e) => {
            e.preventDefault();

            const type = '<?= $type ?>';
            const properties = {};

            if (type === 'text' || type === 'note' || type === 'shape') {
                // 位置とサイズ
                properties.x = parseFloat(document.getElementById('position-x').value);
                properties.y = parseFloat(document.getElementById('position-y').value);
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
                properties.x1 = parseFloat(document.getElementById('x1').value);
                properties.y1 = parseFloat(document.getElementById('y1').value);
                properties.x2 = parseFloat(document.getElementById('x2').value);
                properties.y2 = parseFloat(document.getElementById('y2').value);

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