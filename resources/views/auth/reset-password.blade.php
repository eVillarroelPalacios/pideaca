<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Recuperar contraseña - PideAca</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f1f5f9;
            font-family: Arial, Helvetica, sans-serif;
        }
        .reset-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
        }
        .reset-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .reset-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
        .reset-title { font-size: 18px; font-weight: 700; color: #0c2a4d; }
        .reset-text { font-size: 13px; line-height: 1.6; color: #475569; }
        .field-label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .field-input {
            width: 100%;
            padding: 10px 36px 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 0;
            font-size: 13px;
            outline: none;
            font-family: inherit;
        }
        .field-input:focus { border-color: #D24C19; }
        .field-wrap { position: relative; }
        .eye-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #6b7280;
            display: flex;
            align-items: center;
        }
        .err-msg { display: none; font-size: 11.5px; color: #dc2626; margin-top: 5px; }
        .alert {
            display: block;
            font-size: 12.5px;
            padding: 9px 12px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }
        .btn-main {
            padding: 11px;
            background: #D24C19;
            color: #ffffff;
            border: 1px solid #D24C19;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
        }
        .btn-main:hover { background: #ffffff; color: #D24C19; }
        .btn-ghost {
            display: block;
            text-align: center;
            padding: 10px;
            background: #ffffff;
            color: #D24C19;
            border: 1px solid #d1d5db;
            border-radius: 0;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .badge-ok {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="reset-head">
            <img src="{{ asset('images/logo.png') }}" alt="PideAca" style="height:44px;width:auto;max-width:160px;object-fit:contain;display:block;" />
        </div>

        <div class="reset-body">
            @if (!$valid)
                <div class="reset-title">Enlace no válido</div>
                <p class="reset-text">
                    Este enlace de recuperación ya venció, ya fue usado o no existe.
                    Pedí uno nuevo desde el inicio de sesión.
                </p>
                <span class="alert">El enlace no es válido o expiró.</span>
                <a href="{{ url('/') }}" class="btn-main" style="text-decoration:none;display:block;text-align:center;">Pedir un enlace nuevo</a>
            @else
                <div class="reset-title">Creá tu contraseña nueva</div>
                <p class="reset-text">
                    @if ($userName)
                        Hola <strong>{{ $userName }}</strong>, ingresá la nueva contraseña para tu cuenta.
                    @else
                        Ingresá la nueva contraseña para tu cuenta.
                    @endif
                    El enlace vence en {{ \App\Http\Controllers\Auth\PasswordResetController::EXPIRATION_MINUTES }} minutos.
                </p>

                @if ($errors->any())
                    <span class="alert">{{ $errors->first() }}</span>
                @endif

                <form method="POST" action="{{ route('password.reset.store') }}" style="display:flex;flex-direction:column;gap:14px;" onsubmit="return checkResetPasswords();">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}" />

                    <div>
                        <label for="reset-password" class="field-label">Nueva contraseña</label>
                        <div class="field-wrap">
                            <input type="password" id="reset-password" name="password" class="field-input" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
                            <button type="button" class="eye-btn" onclick="toggleResetField('reset-password', this)" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </button>
                        </div>
                        <span class="err-msg" id="reset-password-error"></span>
                    </div>

                    <div>
                        <label for="reset-password-confirm" class="field-label">Confirmar nueva contraseña</label>
                        <div class="field-wrap">
                            <input type="password" id="reset-password-confirm" name="password_confirmation" class="field-input" placeholder="Repetí la contraseña" autocomplete="new-password" />
                            <button type="button" class="eye-btn" onclick="toggleResetField('reset-password-confirm', this)" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </button>
                        </div>
                        <span class="err-msg" id="reset-password-confirm-error"></span>
                    </div>

                    <button type="submit" class="btn-main">Guardar contraseña</button>
                </form>

                <a href="{{ url('/') }}" class="btn-ghost">Volver al inicio</a>
            @endif
        </div>
    </div>

    <script>
        function toggleResetField(id, btn) {
            var el = document.getElementById(id);
            if (!el) return;
            var show = el.type === 'password';
            el.type = show ? 'text' : 'password';
            btn.innerHTML = show
                ? '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243"/></svg>'
                : '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>';
        }

        function showResetError(id, text) {
            var el = document.getElementById(id);
            if (!el) return;
            el.textContent = text;
            el.style.display = 'block';
        }

        function checkResetPasswords() {
            var pass = document.getElementById('reset-password');
            var confirm = document.getElementById('reset-password-confirm');
            var passErr = document.getElementById('reset-password-error');
            var confirmErr = document.getElementById('reset-password-confirm-error');

            passErr.style.display = 'none';
            confirmErr.style.display = 'none';

            if (pass.value.length < 8) {
                showResetError('reset-password-error', 'La contraseña debe tener al menos 8 caracteres.');
                pass.focus();
                return false;
            }
            if (pass.value !== confirm.value) {
                showResetError('reset-password-confirm-error', 'Las contraseñas no coinciden.');
                confirm.focus();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
