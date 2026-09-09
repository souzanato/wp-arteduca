/**
 * Bebelume — Validação client-side
 * Login e Cadastro: valida antes de submeter, sem requisição ao servidor.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // ── Mensagens ──────────────────────────────────────────────────────
        var msgs = {
            empty: {
                default:  'Por favor, preencha este campo.',
                email:    'Por favor, informe seu e-mail.',
                password: 'Por favor, informe sua senha.',
            },
            email:            'Informe um e-mail válido.',
            tooShort:         'Mínimo {min} caracteres.',
            passwordMismatch: 'As senhas não coincidem.',
            cpfInvalid:       'CPF inválido.',
        };

        // ── CPF ────────────────────────────────────────────────────────────
        function validateCpf(cpf) {
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
            var sum, rest, i;
            sum = 0;
            for (i = 1; i <= 9; i++) sum += parseInt(cpf[i-1]) * (11 - i);
            rest = (sum * 10) % 11;
            if (rest >= 10) rest = 0;
            if (rest !== parseInt(cpf[9])) return false;
            sum = 0;
            for (i = 1; i <= 10; i++) sum += parseInt(cpf[i-1]) * (12 - i);
            rest = (sum * 10) % 11;
            if (rest >= 10) rest = 0;
            return rest === parseInt(cpf[10]);
        }

        // ── UI de erro/sucesso ─────────────────────────────────────────────
        function showError(field, msg) {
            field.classList.add('bbl-invalid');
            field.classList.remove('bbl-valid');
            var wrap = field.closest('p') || field.parentNode;
            var existing = wrap.querySelector('.bbl-field-error');
            if (existing) { existing.textContent = msg; return; }
            var el = document.createElement('span');
            el.className = 'bbl-field-error';
            el.textContent = msg;
            wrap.appendChild(el);
        }

        function clearError(field) {
            field.classList.remove('bbl-invalid', 'bbl-valid');
            var wrap = field.closest('p') || field.parentNode;
            var existing = wrap.querySelector('.bbl-field-error');
            if (existing) existing.remove();
        }

        function markValid(field) {
            field.classList.add('bbl-valid');
            field.classList.remove('bbl-invalid');
            var wrap = field.closest('p') || field.parentNode;
            var existing = wrap.querySelector('.bbl-field-error');
            if (existing) existing.remove();
        }

        // ── Valida campo ───────────────────────────────────────────────────
        function validateField(field, fieldMap) {
            var val  = field.value.trim();
            var type = field.getAttribute('type') || 'text';
            var name = field.getAttribute('name') || '';
            var req  = field.hasAttribute('required');
            var min  = parseInt(field.getAttribute('minlength') || '0');

            if (req && val === '') {
                showError(field, msgs.empty[type] || msgs.empty.default);
                return false;
            }
            if (!req && val === '') { clearError(field); return true; }

            if (type === 'email') {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    showError(field, msgs.email); return false;
                }
            }

            if (name === 'bbl_cpf') {
                if (!validateCpf(val)) {
                    showError(field, msgs.cpfInvalid); return false;
                }
            }

            if (min && val.length < min) {
                showError(field, msgs.tooShort.replace('{min}', min)); return false;
            }

            if (name === 'bbl_password_confirm' && fieldMap) {
                var pwd = fieldMap['bbl_password'];
                if (pwd && val !== pwd.value) {
                    showError(field, msgs.passwordMismatch); return false;
                }
            }

            markValid(field);
            return true;
        }

        // ── Inicializa formulário ──────────────────────────────────────────
        function initForm(formId, names) {
            var form = document.getElementById(formId);
            if (!form) return;

            var fieldMap = {};
            names.forEach(function (n) {
                var el = form.querySelector('[name="' + n + '"]');
                if (el) fieldMap[n] = el;
            });

            form.setAttribute('novalidate', '');

            Object.keys(fieldMap).forEach(function (n) {
                var f = fieldMap[n];
                var touched = false; // só valida no blur após o usuário ter digitado

                f.addEventListener('input', function () {
                    touched = true;
                    if (f.classList.contains('bbl-invalid')) validateField(f, fieldMap);
                });
                f.addEventListener('blur', function () {
                    // valida no blur só se: já foi tocado, ou já estava inválido
                    if (touched || f.classList.contains('bbl-invalid')) {
                        validateField(f, fieldMap);
                    }
                });
            });

            form.addEventListener('submit', function (e) {
                var ok = true;
                Object.keys(fieldMap).forEach(function (n) {
                    if (!validateField(fieldMap[n], fieldMap)) ok = false;
                });
                if (!ok) {
                    e.preventDefault();
                    var first = form.querySelector('.bbl-invalid');
                    if (first) first.focus();
                }
            });
        }

        // Login: campos nativos do WP
        initForm('loginform', ['log', 'pwd']);

        // Cadastro: e-mail (nativo WP) + senha + confirmação
        initForm('registerform', [
            'user_email', 'bbl_password', 'bbl_password_confirm',
        ]);

        // Completar cadastro: pós-Google
        initForm('bbl_complete_form', [
            'bbl_first_name', 'bbl_last_name', 'bbl_cpf',
        ]);
    });
})();
