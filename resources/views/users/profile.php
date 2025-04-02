<!-- resources/views/users/profile.php -->
<?php
$title = 'My Profile';
ob_start();
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h1 class="h4 mb-0">Profile Settings</h1>
                </div>

                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success'] ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['errors']) && isset($_SESSION['errors']['general'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['errors']['general'][0] ?>
                        </div>
                    <?php endif; ?>

                    <form action="/profile/update" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control <?= isset($_SESSION['errors']['username']) ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                            <?php if (isset($_SESSION['errors']['username'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['username'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?= isset($_SESSION['errors']['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                            <?php if (isset($_SESSION['errors']['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['email'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="avatar-url" class="form-label">Avatar URL (optional)</label>
                            <input type="url" class="form-control <?= isset($_SESSION['errors']['avatar_url']) ? 'is-invalid' : '' ?>" id="avatar-url" name="avatar_url" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>">
                            <?php if (isset($_SESSION['errors']['avatar_url'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['avatar_url'][0] ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Enter the URL of an image to use as your avatar</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="h4 mb-0">Change Password</h2>
                </div>

                <div class="card-body">
                    <form action="/profile/update-password" method="post">
                        <div class="mb-3">
                            <label for="current-password" class="form-label">Current Password</label>
                            <input type="password" class="form-control <?= isset($_SESSION['errors']['current_password']) ? 'is-invalid' : '' ?>" id="current-password" name="current_password">
                            <?php if (isset($_SESSION['errors']['current_password'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['current_password'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="new-password" class="form-label">New Password</label>
                            <input type="password" class="form-control <?= isset($_SESSION['errors']['new_password']) ? 'is-invalid' : '' ?>" id="new-password" name="new_password">
                            <?php if (isset($_SESSION['errors']['new_password'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['new_password'][0] ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm-password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control <?= isset($_SESSION['errors']['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm-password" name="confirm_password">
                            <?php if (isset($_SESSION['errors']['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?= $_SESSION['errors']['confirm_password'][0] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
unset($_SESSION['errors']);
unset($_SESSION['success']);
?>