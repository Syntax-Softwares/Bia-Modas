/* Bia Modas - interações do painel administrativo */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;

        const mensagem = form.getAttribute('data-confirm') || 'Confirmar esta ação?';
        if (!window.confirm(mensagem)) {
            event.preventDefault();
        }
    });

    const input = document.querySelector('[data-image-input]');
    const preview = document.querySelector('[data-image-preview]');

    if (input && preview) {
        input.addEventListener('input', function () {
            const caminho = input.value.trim();
            preview.src = caminho !== '' ? caminho : '/img/logoBiaModas.png';
        });
    }

    function fecharSelectsAdmin() {
        document.querySelectorAll('[data-admin-select].is-open').forEach(function (wrapper) {
            wrapper.classList.remove('is-open');

            const trigger = wrapper.querySelector('.admin-select-trigger');
            const menu = wrapper.querySelector('.admin-select-menu');

            if (trigger) trigger.setAttribute('aria-expanded', 'false');
            if (menu) menu.hidden = true;
        });
    }

    function criarSelectAdmin(select, indice) {
        if (select.dataset.selectCustom === '1' || select.multiple || select.size > 1) return;
        select.dataset.selectCustom = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'admin-select';
        wrapper.dataset.adminSelect = '';

        if (select.classList.contains('admin-size-picker')) {
            wrapper.classList.add('admin-select-inline');
        }

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'admin-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        const valor = document.createElement('span');
        valor.className = 'admin-select-value';

        const seta = document.createElement('i');
        seta.className = 'icon-chevron-down';
        seta.setAttribute('aria-hidden', 'true');

        trigger.appendChild(valor);
        trigger.appendChild(seta);

        const menu = document.createElement('div');
        menu.className = 'admin-select-menu';
        menu.id = 'admin-select-menu-' + indice;
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        trigger.setAttribute('aria-controls', menu.id);

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);

        select.classList.add('admin-native-select');
        select.setAttribute('tabindex', '-1');
        select.setAttribute('aria-hidden', 'true');

        function sincronizar() {
            const opcao = select.options[select.selectedIndex];
            valor.textContent = opcao ? opcao.textContent.trim() : '';
            trigger.disabled = select.disabled;
            wrapper.classList.toggle('is-disabled', select.disabled);

            menu.innerHTML = '';

            Array.from(select.options).forEach(function (option) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'admin-select-option';
                item.setAttribute('role', 'option');
                item.dataset.value = option.value;
                item.textContent = option.textContent.trim();

                if (option.disabled) {
                    item.disabled = true;
                    item.classList.add('is-disabled');
                    item.setAttribute('aria-disabled', 'true');
                }

                if (option.selected) {
                    item.classList.add('is-selected');
                    item.setAttribute('aria-selected', 'true');
                }

                item.addEventListener('click', function (evento) {
                    evento.stopPropagation();
                    if (option.disabled) return;

                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    fechar();
                });

                menu.appendChild(item);
            });
        }

        function abrir() {
            if (select.disabled) return;

            fecharSelectsAdmin();
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            menu.hidden = false;

            const selecionado = menu.querySelector('.admin-select-option.is-selected');
            const primeiro = selecionado || menu.querySelector('.admin-select-option:not(:disabled)');

            if (primeiro) primeiro.focus();
        }

        function fechar() {
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        }

        trigger.addEventListener('click', function (evento) {
            evento.stopPropagation();

            if (menu.hidden) {
                abrir();
            } else {
                fechar();
            }
        });

        trigger.addEventListener('keydown', function (evento) {
            if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp' || evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                abrir();
            }
        });

        menu.addEventListener('keydown', function (evento) {
            const itens = Array.from(menu.querySelectorAll('.admin-select-option:not(:disabled)'));
            const indiceAtual = itens.indexOf(document.activeElement);

            if (evento.key === 'ArrowDown') {
                evento.preventDefault();
                itens[Math.min(itens.length - 1, indiceAtual + 1)]?.focus();
            } else if (evento.key === 'ArrowUp') {
                evento.preventDefault();
                itens[Math.max(0, indiceAtual - 1)]?.focus();
            } else if (evento.key === 'Home') {
                evento.preventDefault();
                itens[0]?.focus();
            } else if (evento.key === 'End') {
                evento.preventDefault();
                itens[itens.length - 1]?.focus();
            } else if (evento.key === 'Escape') {
                evento.preventDefault();
                fechar();
                trigger.focus();
            }
        });

        select.addEventListener('change', sincronizar);
        select.__adminSelectSync = sincronizar;
        sincronizar();
    }

    document.querySelectorAll('.admin-form select').forEach(function (select, indice) {
        criarSelectAdmin(select, indice + 1);
    });

    document.addEventListener('click', function (evento) {
        if (!evento.target.closest('[data-admin-select]')) {
            fecharSelectsAdmin();
        }
    });

    document.querySelectorAll('[data-size-selector]').forEach(function (selector) {
        const field = selector.closest('.admin-field');
        const tags = selector.querySelector('[data-size-tags]');
        const picker = selector.querySelector('[data-size-picker]');
        const stocks = field ? field.querySelector('[data-size-stocks]') : null;
        const empty = field ? field.querySelector('[data-size-empty]') : null;

        if (!tags || !picker) return;

        function atualizarEstado() {
            const temTags = tags.querySelector('[data-size-tag]') !== null;

            if (empty) empty.hidden = temTags;
            if (stocks) stocks.hidden = !temTags;

            const disponiveis = Array.from(picker.options).filter(function (option) {
                return option.value !== '' && !option.disabled;
            });

            picker.disabled = disponiveis.length === 0;

            if (typeof picker.__adminSelectSync === 'function') {
                picker.__adminSelectSync();
            }
        }

        function criarLinhaEstoque(id, nome, estoque) {
            if (!stocks) return;

            const label = document.createElement('label');
            label.className = 'admin-size-stock';
            label.dataset.sizeStockRow = id;

            const span = document.createElement('span');
            span.textContent = nome;

            const input = document.createElement('input');
            input.type = 'number';
            input.name = 'tamanhos[' + id + '][estoque]';
            input.value = estoque == null ? 0 : estoque;
            input.min = '0';
            input.step = '1';
            input.setAttribute('aria-label', 'Estoque do tamanho ' + nome);

            label.appendChild(span);
            label.appendChild(input);
            stocks.appendChild(label);
        }

        function adicionarTamanho(id, nome) {
            if (!id || tags.querySelector('[data-size-tag="' + id + '"]')) return;

            const tag = document.createElement('span');
            tag.className = 'admin-size-tag';
            tag.dataset.sizeTag = id;

            tag.appendChild(document.createTextNode(nome + ' '));

            const botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'admin-size-tag-remove';
            botao.dataset.sizeRemove = '';
            botao.setAttribute('aria-label', 'Remover tamanho ' + nome);
            botao.innerHTML = '&times;';
            tag.appendChild(botao);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'tamanhos[' + id + '][selecionado]';
            hidden.value = '1';
            hidden.dataset.sizeHidden = '';
            tag.appendChild(hidden);

            const pickerWrapper = picker.closest('.admin-select') || picker;
            tags.insertBefore(tag, pickerWrapper);
            criarLinhaEstoque(id, nome, 0);

            const option = picker.querySelector('option[value="' + id + '"]');
            if (option) option.disabled = true;

            picker.value = '';
            atualizarEstado();
        }

        function removerTamanho(id) {
            const tag = tags.querySelector('[data-size-tag="' + id + '"]');
            if (tag) tag.remove();

            if (stocks) {
                const row = stocks.querySelector('[data-size-stock-row="' + id + '"]');
                if (row) row.remove();
            }

            const option = picker.querySelector('option[value="' + id + '"]');
            if (option) option.disabled = false;

            atualizarEstado();
        }

        picker.addEventListener('change', function () {
            const option = picker.options[picker.selectedIndex];
            if (!option || option.value === '') return;
            adicionarTamanho(option.value, option.dataset.nome || option.textContent.trim());
        });

        tags.addEventListener('click', function (evento) {
            const botao = evento.target.closest('[data-size-remove]');
            if (!botao) return;

            const tag = botao.closest('[data-size-tag]');
            if (tag) removerTamanho(tag.dataset.sizeTag);
        });

        atualizarEstado();
    });
})();
