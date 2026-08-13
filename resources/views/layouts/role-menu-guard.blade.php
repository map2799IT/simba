@auth
    @php
        $simbaRoleAccess =
            app(
                \App\Support\SimbaRoleAccess::class
            );

        $blockedPathPatterns =
            $simbaRoleAccess
                ->blockedPathPatterns(
                    auth()->user()
                );
    @endphp

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const blockedPatterns =
                    @json($blockedPathPatterns);

                if (
                    ! Array.isArray(
                        blockedPatterns
                    )
                    || blockedPatterns.length === 0
                ) {
                    return;
                }

                const escapeRegExp =
                    function (value) {
                        return value.replace(
                            /[.*+?^${}()|[\]\\]/g,
                            '\\$&'
                        );
                    };

                const patternToRegExp =
                    function (pattern) {
                        const expression =
                            pattern
                                .split('*')
                                .map(escapeRegExp)
                                .join('.*');

                        return new RegExp(
                            '^' + expression + '$'
                        );
                    };

                const compiledPatterns =
                    blockedPatterns.map(
                        patternToRegExp
                    );

                const isBlocked =
                    function (rawUrl) {
                        if (! rawUrl) {
                            return false;
                        }

                        try {
                            const url = new URL(
                                rawUrl,
                                window.location.origin
                            );

                            if (
                                url.origin
                                !== window.location.origin
                            ) {
                                return false;
                            }

                            const path =
                                url.pathname.replace(
                                    /\/+$/,
                                    ''
                                )
                                || '/';

                            return compiledPatterns
                                .some(
                                    function (pattern) {
                                        return pattern.test(
                                            path
                                        );
                                    }
                                );
                        } catch (error) {
                            return false;
                        }
                    };

                const removeNavigationItem =
                    function (element) {
                        const container =
                            element.closest(
                                [
                                    '[data-menu-item]',
                                    '.sidebar-item',
                                    '.nav-item',
                                    '.menu-item',
                                    'li',
                                ].join(',')
                            );

                        if (container) {
                            container.remove();
                            return;
                        }

                        element.remove();
                    };

                document
                    .querySelectorAll(
                        'a[href]'
                    )
                    .forEach(
                        function (anchor) {
                            if (
                                isBlocked(
                                    anchor.getAttribute(
                                        'href'
                                    )
                                )
                            ) {
                                removeNavigationItem(
                                    anchor
                                );
                            }
                        }
                    );

                document
                    .querySelectorAll(
                        'form[action]'
                    )
                    .forEach(
                        function (form) {
                            if (
                                isBlocked(
                                    form.getAttribute(
                                        'action'
                                    )
                                )
                            ) {
                                const actionContainer =
                                    form.closest(
                                        [
                                            '[data-action]',
                                            '.btn-group',
                                            '.dropdown-item',
                                            'td',
                                        ].join(',')
                                    );

                                if (
                                    actionContainer
                                    && actionContainer
                                        !== form
                                ) {
                                    /*
                                     * Jangan menghapus seluruh baris tabel.
                                     * Hanya sembunyikan form/tombol aksi.
                                     */
                                    form.remove();
                                    return;
                                }

                                form.remove();
                            }
                        }
                    );

                /*
                 * Bersihkan grup sidebar yang tidak lagi memiliki link.
                 */
                document
                    .querySelectorAll(
                        [
                            '.sidebar-section',
                            '.menu-section',
                            '.nav-section',
                            '.sidebar-group',
                            '.menu-group',
                        ].join(',')
                    )
                    .forEach(
                        function (section) {
                            if (
                                section.querySelector(
                                    'a[href]'
                                ) === null
                            ) {
                                section.remove();
                            }
                        }
                    );
            }
        );
    </script>
@endauth
