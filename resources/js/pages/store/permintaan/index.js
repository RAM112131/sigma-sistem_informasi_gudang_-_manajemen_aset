let searchTimer = null;

        function debounceSearch(input) {
            clearTimeout(searchTimer);
            const val = input.value.trim();
            if (val.length >= 3 || val.length === 0) {
                searchTimer = setTimeout(() => {
                    input.form.submit();
                }, 450);
            }
        }

        if (!window._sigmaPermintaanSearchFocusInstalled) {
            window._sigmaPermintaanSearchFocusInstalled = true;
            document.addEventListener('turbo:load', function() {
                const searchInput = document.getElementById('searchInput');
                if (searchInput && searchInput.value) {
                    searchInput.focus();
                    const len = searchInput.value.length;
                    searchInput.setSelectionRange(len, len);
                }
            });
        }
