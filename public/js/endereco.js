/* Bia Modas - ViaCEP e checkbox "sem numero" em todos os formularios de endereco */
(function () {
    'use strict';

    function campo(form, nomes) {
        for (var i = 0; i < nomes.length; i++) {
            const elemento = form.querySelector('[name="' + nomes[i] + '"]');
            if (elemento) return elemento;
        }

        return null;
    }

    function somenteDigitos(valor) {
        return (valor || '').replace(/\D+/g, '');
    }

    function formatarCep(valor) {
        const digitos = somenteDigitos(valor).slice(0, 8);
        return digitos.length > 5 ? digitos.slice(0, 5) + '-' + digitos.slice(5) : digitos;
    }

    function inicializar(form) {
        if (form.dataset.enderecoReady === '1') return;
        form.dataset.enderecoReady = '1';

        const cep = campo(form, ['cep', 'endereco_cep']);
        if (!cep) return;

        const logradouro = campo(form, ['logradouro', 'endereco_logradouro']);
        const bairro = campo(form, ['bairro', 'endereco_bairro']);
        const cidade = campo(form, ['cidade', 'endereco_cidade']);
        const uf = campo(form, ['uf', 'endereco_uf']);
        const complemento = campo(form, ['complemento', 'endereco_complemento']);
        const numero = campo(form, ['numero', 'endereco_numero']);
        const semNumero = form.querySelector('[data-sem-numero]');
        const feedback = form.querySelector('[data-cep-feedback]');

        let ultimoCep = '';
        let controlador = null;

        function mostrarFeedback(mensagem, erro) {
            if (!feedback) return;

            feedback.textContent = mensagem;
            feedback.hidden = mensagem === '';
            feedback.classList.toggle('is-error', Boolean(erro));
        }

        function preencher(dados) {
            if (logradouro && dados.logradouro) logradouro.value = dados.logradouro;
            if (bairro && dados.bairro) bairro.value = dados.bairro;
            if (cidade && dados.localidade) cidade.value = dados.localidade;
            if (uf && dados.uf) uf.value = dados.uf;

            if (complemento && !complemento.value.trim() && dados.complemento) {
                complemento.value = dados.complemento;
            }

            if (numero && !numero.readOnly && !numero.value.trim()) {
                numero.focus();
            }
        }

        async function buscar() {
            const digitos = somenteDigitos(cep.value);

            if (digitos.length !== 8 || digitos === ultimoCep) return;

            ultimoCep = digitos;

            if (controlador) controlador.abort();
            controlador = new AbortController();

            cep.classList.add('is-loading');
            cep.setAttribute('aria-busy', 'true');
            mostrarFeedback('Buscando endereço...', false);

            try {
                const resposta = await fetch('https://viacep.com.br/ws/' + digitos + '/json/', {
                    signal: controlador.signal,
                    headers: { Accept: 'application/json' }
                });

                if (!resposta.ok) throw new Error('Falha na consulta');

                const dados = await resposta.json();

                if (dados.erro) throw new Error('CEP não encontrado');

                preencher(dados);
                mostrarFeedback('Endereço preenchido pelo ViaCEP.', false);
            } catch (erro) {
                if (erro.name === 'AbortError') return;

                ultimoCep = '';
                mostrarFeedback('Não foi possível consultar o CEP. Preencha o endereço manualmente.', true);
            } finally {
                cep.classList.remove('is-loading');
                cep.removeAttribute('aria-busy');
            }
        }

        cep.addEventListener('input', function () {
            const digitos = somenteDigitos(cep.value);

            if (digitos.length === 8) {
                cep.value = formatarCep(digitos);
                buscar();
                return;
            }

            ultimoCep = '';
            mostrarFeedback('', false);
        });

        cep.addEventListener('blur', function () {
            cep.value = formatarCep(cep.value);
            buscar();
        });

        if (numero && semNumero) {
            function sincronizarNumero() {
                if (semNumero.checked) {
                    if (numero.value.trim() !== 'S/N') {
                        numero.dataset.valorAnterior = numero.value;
                    }

                    numero.value = 'S/N';
                    numero.readOnly = true;
                    numero.classList.add('is-readonly');
                    numero.setAttribute('aria-readonly', 'true');
                } else {
                    numero.readOnly = false;
                    numero.classList.remove('is-readonly');
                    numero.removeAttribute('aria-readonly');

                    if (numero.value.trim().toUpperCase() === 'S/N') {
                        numero.value = numero.dataset.valorAnterior || '';
                    }
                }
            }

            semNumero.addEventListener('change', sincronizarNumero);

            if (numero.value.trim().toUpperCase() === 'S/N') {
                semNumero.checked = true;
            }

            sincronizarNumero();
        }
    }

    document.querySelectorAll('[data-endereco-form]').forEach(inicializar);
})();
