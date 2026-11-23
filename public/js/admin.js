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

            tags.insertBefore(tag, picker);
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
