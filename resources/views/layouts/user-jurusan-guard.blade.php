@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ROLE_WAKIL_SARPRAS = 'wakil_sarpras';

            /*
             * Selector ini sekaligus memastikan opsi Waka Sarpras
             * menggunakan value="wakil_sarpras".
             */
            const WAKIL_SARPRAS_OPTION_SELECTOR =
                'option[value="wakil_sarpras"]';

            const rolesTanpaWorkshop = new Set([
                'admin',
                'super_admin',
                ROLE_WAKIL_SARPRAS,
            ]);

            function normalizeRole(value) {
                const normalized = String(value ?? '')
                    .trim()
                    .toLowerCase()
                    .replace(/[\s-]+/g, '_')
                    .replace(/_+/g, '_');

                const aliases = {
                    waka_sarpras: ROLE_WAKIL_SARPRAS,
                    wakil_sarpras: ROLE_WAKIL_SARPRAS,
                    wakil_sarana_prasarana: ROLE_WAKIL_SARPRAS,
                    wakil_sarana_dan_prasarana: ROLE_WAKIL_SARPRAS,
                    waka_sarana_prasarana: ROLE_WAKIL_SARPRAS,
                    waka_sarana_dan_prasarana: ROLE_WAKIL_SARPRAS,
                };

                return aliases[normalized] ?? normalized;
            }

            function findRoleField(form) {
                return form.querySelector(
                    'select[name="role"], ' +
                    'input[name="role"], ' +
                    '[data-role-field]'
                );
            }

            function findWorkshopFields(form) {
                return Array.from(
                    form.querySelectorAll(
                        'select[name="workshop_id"], ' +
                        'input[name="workshop_id"], ' +
                        'select[name="jurusan_id"], ' +
                        'input[name="jurusan_id"], ' +
                        '[data-workshop-field], ' +
                        '[data-jurusan-field]'
                    )
                );
            }

            function findFieldWrapper(field) {
                return field.closest(
                    '[data-workshop-wrapper], ' +
                    '[data-jurusan-wrapper], ' +
                    '[data-workshop-field-wrapper], ' +
                    '[data-jurusan-field-wrapper], ' +
                    '.workshop-wrapper, ' +
                    '.jurusan-wrapper, ' +
                    '.workshop-field, ' +
                    '.jurusan-field, ' +
                    '.form-group, ' +
                    '.mb-3, ' +
                    '.mb-4'
                ) || field.parentElement;
            }

            function clearSelect2(field) {
                if (
                    !window.jQuery ||
                    !window.jQuery.fn ||
                    typeof window.jQuery.fn.select2 !== 'function'
                ) {
                    return;
                }

                const jqueryField = window.jQuery(field);

                if (
                    jqueryField.hasClass(
                        'select2-hidden-accessible'
                    )
                ) {
                    jqueryField
                        .val(null)
                        .trigger('change.select2');
                }
            }

            function clearField(field) {
                field.disabled = false;
                field.required = false;
                field.value = '';
                field.classList.remove('is-invalid');

                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                }

                clearSelect2(field);
            }

            function clearValidation(wrapper) {
                if (!wrapper) {
                    return;
                }

                wrapper
                    .querySelectorAll(
                        '.invalid-feedback, ' +
                        '.text-danger, ' +
                        '[data-workshop-error], ' +
                        '[data-jurusan-error]'
                    )
                    .forEach(function (errorElement) {
                        errorElement.classList.add('d-none');
                    });
            }

            function hideField(field) {
                const wrapper = findFieldWrapper(field);

                clearField(field);
                clearValidation(wrapper);

                if (!wrapper) {
                    return;
                }

                wrapper.classList.add('d-none');
                wrapper.setAttribute('aria-hidden', 'true');
            }

            function showField(field) {
                const wrapper = findFieldWrapper(field);

                field.disabled = false;

                if (!wrapper) {
                    return;
                }

                wrapper.classList.remove('d-none');
                wrapper.removeAttribute('hidden');
                wrapper.removeAttribute('aria-hidden');

                if (wrapper.style.display === 'none') {
                    wrapper.style.removeProperty('display');
                }
            }

            function synchronizeForm(form) {
                if (
                    form.dataset.userJurusanGuardInitialized === '1'
                ) {
                    return;
                }

                const roleField = findRoleField(form);
                const workshopFields = findWorkshopFields(form);

                if (
                    !roleField ||
                    workshopFields.length === 0
                ) {
                    return;
                }

                form.dataset.userJurusanGuardInitialized = '1';

                /*
                 * Memastikan opsi role Waka Sarpras menggunakan
                 * nilai wakil_sarpras.
                 */
                if (roleField.tagName === 'SELECT') {
                    const wakaOption = roleField.querySelector(
                        WAKIL_SARPRAS_OPTION_SELECTOR
                    );

                    if (wakaOption) {
                        wakaOption.value = ROLE_WAKIL_SARPRAS;
                    }
                }

                function updateWorkshopState() {
                    const selectedRole = normalizeRole(
                        roleField.value
                    );

                    const tanpaWorkshop =
                        rolesTanpaWorkshop.has(selectedRole);

                    workshopFields.forEach(function (field) {
                        if (tanpaWorkshop) {
                            hideField(field);
                            return;
                        }

                        showField(field);
                    });
                }

                roleField.addEventListener(
                    'change',
                    updateWorkshopState
                );

                roleField.addEventListener(
                    'input',
                    updateWorkshopState
                );

                if (window.jQuery) {
                    window.jQuery(roleField).on(
                        'change.userJurusanGuard',
                        updateWorkshopState
                    );
                }

                form.addEventListener('submit', function () {
                    const selectedRole = normalizeRole(
                        roleField.value
                    );

                    roleField.value = selectedRole;

                    if (
                        rolesTanpaWorkshop.has(selectedRole)
                    ) {
                        workshopFields.forEach(function (field) {
                            field.disabled = false;
                            field.required = false;
                            field.value = '';
                        });
                    }
                });

                updateWorkshopState();
            }

            function initializeForms() {
                document
                    .querySelectorAll('form')
                    .forEach(synchronizeForm);
            }

            initializeForms();

            const observer = new MutationObserver(
                function (mutations) {
                    const hasAddedNodes = mutations.some(
                        function (mutation) {
                            return mutation.addedNodes.length > 0;
                        }
                    );

                    if (hasAddedNodes) {
                        initializeForms();
                    }
                }
            );

            observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        });
    </script>
@endonce