<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-nis-suggest]').forEach(function (input) {
            var menu = input.parentElement.querySelector('.nis-menu');
            var nama = document.getElementById(input.getAttribute('data-nis-target') || '');
            var kelas = document.getElementById(input.getAttribute('data-nis-kelas') || '');
            var items = [];
            var active = -1;
            var timer = null;

            if (!menu) {
                return;
            }

            var closeMenu = function () {
                menu.hidden = true;
                menu.innerHTML = '';
                active = -1;
            };

            var paint = function () {
                Array.prototype.forEach.call(menu.querySelectorAll('button'), function (button, index) {
                    button.classList.toggle('is-active', index === active);
                });
            };

            var choose = function (item) {
                input.value = item.nis;
                if (nama) {
                    if (nama.tagName === 'INPUT' || nama.tagName === 'TEXTAREA') {
                        nama.value = item.nama;
                    } else {
                        nama.textContent = item.nama;
                    }
                }
                if (kelas) {
                    kelas.value = item.kelas_kunci || '';
                }
                closeMenu();
                input.dispatchEvent(new CustomEvent('nis-picked', { bubbles: true, detail: item }));
            };

            var render = function (list) {
                menu.innerHTML = '';
                active = -1;
                if (!list.length) {
                    menu.hidden = false;
                    menu.innerHTML = '<p class="nis-empty">Tidak ditemukan</p>';
                    return;
                }

                list.forEach(function (item) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = item.nis + ' — ' + item.nama + (item.kelas_label ? ' — ' + item.kelas_label : '');
                    button.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                        choose(item);
                    });
                    menu.appendChild(button);
                });
                menu.hidden = false;
            };

            input.addEventListener('input', function () {
                var value = input.value.trim();
                window.clearTimeout(timer);
                if (value === '') {
                    if (nama) {
                        if (nama.tagName === 'INPUT' || nama.tagName === 'TEXTAREA') {
                            nama.value = '';
                        } else {
                            nama.textContent = '';
                        }
                    }
                    if (kelas) {
                        kelas.value = '';
                    }
                }
                if (value === '') {
                    closeMenu();
                    return;
                }

                timer = window.setTimeout(function () {
                    fetch(input.getAttribute('data-nis-suggest') + '?q=' + encodeURIComponent(value), {
                        headers: { 'Accept': 'application/json' }
                    }).then(function (response) {
                        if (!response.ok) {
                            throw new Error('missing');
                        }
                        return response.json();
                    }).then(function (data) {
                        if (input.value.trim() !== value) {
                            return;
                        }
                        items = data.items || [];
                        render(items);
                    }).catch(function () {
                        closeMenu();
                    });
                }, 180);
            });

            input.addEventListener('keydown', function (event) {
                var buttons = menu.querySelectorAll('button');
                if (menu.hidden || buttons.length === 0) {
                    return;
                }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    active = Math.min(active + 1, buttons.length - 1);
                    paint();
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    active = Math.max(active - 1, 0);
                    paint();
                } else if (event.key === 'Enter' && active >= 0) {
                    event.preventDefault();
                    choose(items[active]);
                } else if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            document.addEventListener('click', function (event) {
                if (!input.parentElement.contains(event.target)) {
                    closeMenu();
                }
            });
        });
    });
</script>
