<?php
require_login();

$user = current_user();
$userId = (int)$user['id'];

// Lấy thông tin user mới nhất từ CSDL
$userData = db_select_one("SELECT * FROM users WHERE id = ?", [$userId]);

// Đảm bảo cột avatar tồn tại trên database cũ.
try {
    $hasAvatarColumn = db_select_one("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar'");
    if ((int)($hasAvatarColumn['c'] ?? 0) === 0) {
        db()->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER role");
        $userData = db_select_one("SELECT * FROM users WHERE id = ?", [$userId]);
    }
} catch (Throwable $e) {
    // Giữ trang hồ sơ hoạt động nếu DB không cho phép kiểm tra schema tự động.
}

// Đếm tổng số phim trong danh sách xem
$watchlistStat = db_select_one("SELECT COUNT(*) AS total FROM watchlist WHERE user_id = ?", [$userId]);
$totalWatchlist = (int)($watchlistStat['total'] ?? 0);

$errorMessage = '';
$successMessage = '';

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    csrf_validate();

    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $errorMessage = 'Vui lòng điền đầy đủ các trường mật khẩu!';
    } elseif (!password_verify($currentPass, $userData['password'])) {
        $errorMessage = 'Mật khẩu hiện tại không chính xác!';
    } elseif (strlen($newPass) < 6) {
        $errorMessage = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($newPass !== $confirmPass) {
        $errorMessage = 'Mật khẩu xác nhận không khớp!';
    } else {
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        db_execute("UPDATE users SET password = ? WHERE id = ?", [$newHash, $userId]);
        $successMessage = 'Đổi mật khẩu thành công!';
        // Cập nhật lại hash trong biến userData
        $userData['password'] = $newHash;
    }
}
?>

<div style="max-width: 1000px; margin: 40px auto; padding: 0 15px;">
    
    <div style="margin-bottom: 30px;">
        <h2 style="color: var(--text); font-size: 26px; font-weight: 700; margin: 0 0 6px 0;">Hồ sơ cá nhân</h2>
        <p style="color: var(--muted); font-size: 14px; margin: 0;">Quản lý thông tin tài khoản và bảo mật của bạn.</p>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px;">
            ✕ <?php echo e($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #86efac; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px;">
            ✓ <?php echo e($successMessage); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 24px;">
        
        <!-- Cột trái: Thông tin tài khoản & Thống kê -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Card User Info -->
            <div class="profile-card">
                <div class="profile-avatar-frame" id="profile-avatar-frame">
                    <?php if (!empty($userData['avatar'])): ?>
                        <img id="profile-avatar-preview" src="<?php echo e(base_url($userData['avatar'])); ?>?v=<?php echo time(); ?>" alt="Ảnh đại diện">
                    <?php else: ?>
                        <span id="profile-avatar-initial"><?php echo e(mb_strtoupper(mb_substr($userData['username'] ?? 'U', 0, 1))); ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="profile-username"><?php echo e($userData['username'] ?? ''); ?></h3>
                <p class="profile-email"><?php echo e($userData['email'] ?? ''); ?></p>

                <div class="profile-avatar-upload">
                    <label class="profile-upload-button" for="avatar-input">
                        Đổi ảnh đại diện
                    </label>
                    <input id="avatar-input" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                    <p class="profile-upload-note">JPG, PNG hoặc WEBP • tối thiểu 300 × 300 px • tối đa 5 MB</p>
                    <div id="avatar-upload-message" class="profile-upload-message" role="status" aria-live="polite"></div>
                </div>

                <span style="display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background: <?php echo ($userData['role'] ?? '') === 'admin' ? 'rgba(234, 179, 8, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>; color: <?php echo ($userData['role'] ?? '') === 'admin' ? '#eab308' : '#60a5fa'; ?>; border: 1px solid <?php echo ($userData['role'] ?? '') === 'admin' ? '#eab308' : '#3b82f6'; ?>;">
                    <?php echo strtoupper($userData['role'] ?? 'USER'); ?>
                </span>
            </div>

            <!-- Card Watchlist Count -->
            <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 14px; padding: 22px 24px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="color: #8c8f96; font-size: 13px; margin-bottom: 4px;">Phim đang lưu</div>
                    <div style="color: #eab308; font-size: 26px; font-weight: 700;"><?php echo $totalWatchlist; ?></div>
                </div>
                <a href="<?php echo e(base_url('watchlist')); ?>" style="padding: 8px 14px; background: #222328; border: 1px solid #33363d; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; transition: 0.2s;" onmouseover="this.style.borderColor='#eab308';" onmouseout="this.style.borderColor='#33363d';">
                    Xem danh sách →
                </a>
            </div>

        </div>

        <!-- Cột phải: Form Đổi mật khẩu -->
        <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 14px; padding: 28px;">
            <h3 style="color: #fff; font-size: 18px; margin: 0 0 6px 0;">Bảo mật & Đổi mật khẩu</h3>
            <p style="color: #8c8f96; font-size: 13px; margin: 0 0 20px 0;">Nên sử dụng mật khẩu mạnh có ít nhất 6 ký tự.</p>

            <form action="<?php echo e(base_url('profile')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="change_password">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 6px;">Mật khẩu hiện tại</label>
                    <div class="password-field">
                        <input type="password" name="current_password" required placeholder="••••••••" style="width: 100%; box-sizing: border-box; padding: 11px 46px 11px 14px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#eab308';" onblur="this.style.borderColor='#2e3036';">
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Hiện mật khẩu" aria-pressed="false">
                            <svg class="password-eye password-eye--closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.7 12s3.1-5 9.3-5 9.3 5 9.3 5-3.1 5-9.3 5-9.3-5-9.3-5Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                            <svg class="password-eye password-eye--open" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M10.6 6.8A10.9 10.9 0 0 1 12 6.7c6.2 0 9.3 5.3 9.3 5.3a17.9 17.9 0 0 1-3.2 3.3M6.7 8.2C4.1 9.7 2.7 12 2.7 12s3.1 5 9.3 5c1.2 0 2.2-.2 3.2-.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 6px;">Mật khẩu mới</label>
                    <div class="password-field">
                        <input type="password" name="new_password" required placeholder="Tối thiểu 6 ký tự" style="width: 100%; box-sizing: border-box; padding: 11px 46px 11px 14px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#eab308';" onblur="this.style.borderColor='#2e3036';">
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Hiện mật khẩu" aria-pressed="false">
                            <svg class="password-eye password-eye--closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.7 12s3.1-5 9.3-5 9.3 5 9.3 5-3.1 5-9.3 5-9.3-5-9.3-5Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                            <svg class="password-eye password-eye--open" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M10.6 6.8A10.9 10.9 0 0 1 12 6.7c6.2 0 9.3 5.3 9.3 5.3a17.9 17.9 0 0 1-3.2 3.3M6.7 8.2C4.1 9.7 2.7 12 2.7 12s3.1 5 9.3 5c1.2 0 2.2-.2 3.2-.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 22px;">
                    <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 6px;">Xác nhận mật khẩu mới</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" required placeholder="••••••••" style="width: 100%; box-sizing: border-box; padding: 11px 46px 11px 14px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#eab308';" onblur="this.style.borderColor='#2e3036';">
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Hiện mật khẩu" aria-pressed="false">
                            <svg class="password-eye password-eye--closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.7 12s3.1-5 9.3-5 9.3 5 9.3 5-3.1 5-9.3 5-9.3-5-9.3-5Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                            <svg class="password-eye password-eye--open" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M10.6 6.8A10.9 10.9 0 0 1 12 6.7c6.2 0 9.3 5.3 9.3 5.3a17.9 17.9 0 0 1-3.2 3.3M6.7 8.2C4.1 9.7 2.7 12 2.7 12s3.1 5 9.3 5c1.2 0 2.2-.2 3.2-.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" style="padding: 11px 22px; background: #eab308; border: none; border-radius: 8px; color: #000; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#ca8a04';" onmouseout="this.style.background='#eab308';">
                    Cập nhật mật khẩu
                </button>
            </form>
        </div>

    </div>
</div>

<!-- Modal crop ảnh đại diện -->
<div id="avatar-crop-modal" class="avatar-crop-modal" aria-hidden="true">
    <div class="avatar-crop-modal__backdrop" data-avatar-crop-close></div>
    <div class="avatar-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="avatar-crop-title">
        <div class="avatar-crop-header">
            <div>
                <h3 id="avatar-crop-title">Căn chỉnh ảnh đại diện</h3>
                <p>Kéo ảnh để căn giữa, dùng thanh thu phóng để chọn phần ảnh phù hợp.</p>
            </div>
            <button type="button" class="avatar-crop-close" data-avatar-crop-close aria-label="Đóng">×</button>
        </div>
        <div class="avatar-crop-stage-wrap">
            <canvas id="avatar-crop-canvas" class="avatar-crop-canvas" width="360" height="360"></canvas>
        </div>
        <div class="avatar-crop-controls">
            <label for="avatar-zoom">Thu phóng</label>
            <input id="avatar-zoom" type="range" min="1" max="3" step="0.01" value="1">
            <span id="avatar-zoom-value">100%</span>
        </div>
        <div class="avatar-crop-actions">
            <button type="button" class="avatar-crop-cancel" data-avatar-crop-close>Hủy</button>
            <button type="button" id="avatar-crop-save" class="avatar-crop-save">Cắt & tải lên</button>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('avatar-input');
    const modal = document.getElementById('avatar-crop-modal');
    const canvas = document.getElementById('avatar-crop-canvas');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const zoomInput = document.getElementById('avatar-zoom');
    const zoomValue = document.getElementById('avatar-zoom-value');
    const saveBtn = document.getElementById('avatar-crop-save');
    const message = document.getElementById('avatar-upload-message');
    const avatarFrame = document.getElementById('profile-avatar-frame');
    const minSize = 300;
    const outputSize = 512;
    const stageSize = 360;
    let image = null;
    let baseScale = 1;
    let scale = 1;
    let offsetX = 0;
    let offsetY = 0;
    let drag = null;

    function showMessage(text, type) {
        if (!message) return;
        message.textContent = text || '';
        message.className = 'profile-upload-message ' + (type ? 'is-' + type : '');
    }

    function clampOffsets() {
        const w = image.naturalWidth * scale;
        const h = image.naturalHeight * scale;
        const minX = stageSize - w;
        const minY = stageSize - h;
        offsetX = Math.min(0, Math.max(minX, offsetX));
        offsetY = Math.min(0, Math.max(minY, offsetY));
    }

    function draw() {
        if (!ctx || !image) return;
        ctx.clearRect(0, 0, stageSize, stageSize);
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, stageSize, stageSize);
        ctx.save();
        ctx.beginPath();
        ctx.rect(0, 0, stageSize, stageSize);
        ctx.clip();
        ctx.drawImage(image, offsetX, offsetY, image.naturalWidth * scale, image.naturalHeight * scale);
        ctx.restore();

        // Lớp viền để người dùng thấy đúng vùng ảnh tròn sẽ được sử dụng.
        ctx.save();
        ctx.beginPath();
        ctx.arc(stageSize / 2, stageSize / 2, stageSize / 2 - 1, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(255,255,255,.9)';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();
    }

    function setZoom(multiplier, keepCenter = true) {
        if (!image) return;
        const centerX = stageSize / 2;
        const centerY = stageSize / 2;
        const oldScale = scale;
        scale = baseScale * Number(multiplier);
        if (keepCenter) {
            offsetX = centerX - (centerX - offsetX) * (scale / oldScale);
            offsetY = centerY - (centerY - offsetY) * (scale / oldScale);
        }
        clampOffsets();
        zoomValue.textContent = Math.round(Number(multiplier) * 100) + '%';
        draw();
    }

    function openModal(file) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            const img = new Image();
            img.onload = function () {
                image = img;
                baseScale = Math.max(stageSize / img.naturalWidth, stageSize / img.naturalHeight);
                scale = baseScale;
                offsetX = (stageSize - img.naturalWidth * scale) / 2;
                offsetY = (stageSize - img.naturalHeight * scale) / 2;
                zoomInput.value = '1';
                zoomValue.textContent = '100%';
                draw();
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            };
            img.onerror = function () {
                showMessage('Không thể đọc ảnh này.', 'error');
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        image = null;
        drag = null;
    }

    if (input) {
        input.addEventListener('change', function () {
            showMessage('', '');
            const file = input.files && input.files[0];
            if (!file) return;
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowed.includes(file.type)) {
                showMessage('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.', 'error');
                input.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showMessage('Ảnh phải nhỏ hơn hoặc bằng 5 MB.', 'error');
                input.value = '';
                return;
            }
            const probe = new Image();
            const url = URL.createObjectURL(file);
            probe.onload = function () {
                URL.revokeObjectURL(url);
                if (probe.naturalWidth < minSize || probe.naturalHeight < minSize) {
                    showMessage('Ảnh quá nhỏ. Vui lòng chọn ảnh có kích thước tối thiểu 300 × 300 px.', 'error');
                    input.value = '';
                    return;
                }
                openModal(file);
            };
            probe.onerror = function () {
                URL.revokeObjectURL(url);
                showMessage('Không thể đọc ảnh này.', 'error');
                input.value = '';
            };
            probe.src = url;
        });
    }

    document.querySelectorAll('[data-avatar-crop-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal();
            if (input) input.value = '';
        });
    });

    if (zoomInput) {
        zoomInput.addEventListener('input', function () {
            setZoom(this.value);
        });
    }

    if (canvas) {
        const startDrag = function (event) {
            if (!image) return;
            event.preventDefault();
            const point = event.touches ? event.touches[0] : event;
            drag = { x: point.clientX, y: point.clientY, offsetX, offsetY };
            canvas.setPointerCapture?.(event.pointerId);
        };
        const moveDrag = function (event) {
            if (!drag || !image) return;
            event.preventDefault();
            const point = event.touches ? event.touches[0] : event;
            offsetX = drag.offsetX + (point.clientX - drag.x);
            offsetY = drag.offsetY + (point.clientY - drag.y);
            clampOffsets();
            draw();
        };
        const endDrag = function () { drag = null; };
        canvas.addEventListener('pointerdown', startDrag);
        canvas.addEventListener('pointermove', moveDrag);
        canvas.addEventListener('pointerup', endDrag);
        canvas.addEventListener('pointercancel', endDrag);
        canvas.addEventListener('pointerleave', endDrag);
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            if (!image || !canvas) return;
            saveBtn.disabled = true;
            showMessage('Đang tải ảnh lên...', 'loading');
            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = outputSize;
            exportCanvas.height = outputSize;
            const exportCtx = exportCanvas.getContext('2d');
            const sourceX = Math.max(0, -offsetX / scale);
            const sourceY = Math.max(0, -offsetY / scale);
            const sourceSize = stageSize / scale;
            exportCtx.drawImage(image, sourceX, sourceY, sourceSize, sourceSize, 0, 0, outputSize, outputSize);

            exportCanvas.toBlob(function (blob) {
                if (!blob) {
                    saveBtn.disabled = false;
                    showMessage('Không thể tạo ảnh đã cắt.', 'error');
                    return;
                }
                const form = new FormData();
                form.append('action', 'upload_avatar');
                form.append('csrf_token', <?php echo json_encode(csrf_token()); ?>);
                form.append('avatar', blob, 'avatar.jpg');

                fetch(<?php echo json_encode(base_url('actions/avatar_upload.php')); ?>, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                }).then(function (res) {
                    return res.json().then(function (data) { return { ok: res.ok, data }; });
                }).then(function (result) {
                    if (!result.ok || !result.data.success) {
                        throw new Error(result.data && result.data.message ? result.data.message : 'Upload thất bại.');
                    }
                    const currentImg = avatarFrame.querySelector('img');
                    if (currentImg) {
                        currentImg.src = result.data.avatar_url;
                    } else {
                        avatarFrame.innerHTML = '<img id="profile-avatar-preview" alt="Ảnh đại diện">';
                        avatarFrame.querySelector('img').src = result.data.avatar_url;
                    }
                    const initial = document.getElementById('profile-avatar-initial');
                    if (initial) initial.remove();
                    showMessage(result.data.message, 'success');
                    closeModal();
                    if (input) input.value = '';
                }).catch(function (error) {
                    showMessage(error.message || 'Upload thất bại.', 'error');
                }).finally(function () {
                    saveBtn.disabled = false;
                });
            }, 'image/jpeg', 0.92);
        });
    }

    // Nút con mắt: chuyển từng ô mật khẩu giữa ẩn/hiện.
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const field = button.closest('.password-field');
            const inputEl = field && field.querySelector('input');
            if (!inputEl) return;
            const showing = inputEl.type === 'text';
            inputEl.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            button.classList.toggle('is-visible', !showing);
        });
    });
})();
</script>
