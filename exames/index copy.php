<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastro de Pedido de Exame</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial;
            background: #f4f6f8;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
        }

        label {
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
        }

        .doc {
            border: 1px dashed #ccc;
            padding: 15px;
            margin-bottom: 10px;
        }

        button {
            padding: 15px;
            background: #2a5298;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .filtros {
            display: flex;
            gap: 14px;
            justify-content: center;
            margin: 20px 0;
        }

        .btn-filtro {
            padding: 12px 18px;
            border-radius: 24px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            cursor: pointer;
            font-size: 14px;
            transition:
                opacity .25s ease,
                transform .2s ease,
                background .25s ease;
        }

        /* quando um estiver ativo, os outros ficam opacos */
        .filtros .btn-filtro {
            opacity: .35;
            color: black;
        }

        /* botão ativo */
        .btn-filtro.ativo {
            opacity: 1;
            background: #2a5298;
            color: #fff;
            border-color: #2a5298;
            transform: scale(1.05);
            color: black;
        }

        /* hover só se NÃO estiver ativo */
        .btn-filtro:not(.ativo):hover {
            opacity: .6;

        }

        .btn-add-doc {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #22c55e;
            /* verde bonito */
            color: #fff;
            font-size: 28px;
            font-weight: bold;
            border: none;
            cursor: pointer;

            display: flex;
            align-items: center;
            justify-content: center;

            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.35);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .btn-add-doc:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 28px rgba(34, 197, 94, 0.55);
        }

        .btn-add-doc:active {
            transform: scale(0.95);
        }
    </style>

</head>

<body>
    <div class="container container-exames">
        <div class="card card-exames">
            <div class="card-header">
                Pedido de Exame
            </div>

            <div class="card-body">


                <div class="alert alert-info-custom mb-4">
                    📄 Anexe todos os documentos necessários (pedido médico, cartão SUS, RG, etc.)
                </div>
                <form method="post" action="cadastro_exame.php" enctype="multipart/form-data">
                    <div class="filtros container">
                        <input type="hidden" name="tipo" id="tipo" value="baixa">

                        <button type="button"
                            class="btn-filtro ativo"
                            data-ativo="🟢 Secretaria de Saúde"
                            data-inativo="🔴 Secretaria de Saúde"
                            onclick="selecionarTipo('baixa', this)">
                            🟢 Secretaria de Saúde
                        </button>

                        <button type="button"
                            class="btn-filtro"
                            data-ativo="🟢 Policlínica"
                            data-inativo="🔴 Policlínica"
                            onclick="selecionarTipo('alta', this)">
                            🔴 Policlínica
                        </button>
                    </div>


                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label">Nome do paciente</label>
                            <input class="form-control" type="text" name="nome_paciente" required>
                        </div>

                        <input
                            class="form-control"
                            type="text"
                            name="cartao_sus"
                            id="cartao_sus"
                            placeholder="000 0000 0000 0000"
                            required>
                        <div class="invalid-feedback">
                            Cartão SUS inválido.
                        </div>

                    </div>

                    <div class="row g-3 mt-1">

                        <div class="col-md-4 col-12">
                            <label class="form-label">Telefone</label>
                            <input class="form-control" type="text" name="telefone" id="telefone">
                        </div>

                        <div class="col-md-4 col-12">
                            <label class="form-label">E-mail</label>
                            <input
                                class="form-control"
                                type="email"
                                name="email"
                                id="email"
                                placeholder="exemplo@email.com"
                                required>
                            <div class="invalid-feedback">
                                Informe um e-mail válido.
                            </div>
                        </div>

                        <div class="col-md-8 coal-12">
                            <label class="form-label">Exame solicitado</label>
                            <input class="form-control" type="text" name="exame_solicitado" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="4"></textarea>
                        </div>
                    </div>

                    <h5 class="section-title mt-4">Documentos</h5>

                    <div id="docs">
                        <div class="doc-box mb-3">
                            <label class="doc-title">Título do documento</label>
                            <input class="form-control mb-2" type="text" name="titulo_documento[]" required>
                            <input class="form-control" type="file" name="documentos[]" required>
                        </div>
                    </div>

                    <!-- <button class="btn btn-outline-primary w-100 mb-3" type="button" onclick="addDocumento()">
                        ➕ Adicionar documento
                    </button> -->

                    <button type="button"
                        class="btn-add-doc"
                        onclick="addDocumento()"
                        title="Adicionar documento">
                        +
                    </button>
                    <br>
                    <button class="btn btn-primary w-100" type="submit">
                        Cadastrar Pedido
                    </button>

                </form>


            </div>
        </div>
    </div>
    <script>
        function addDocumento() {
            const div = document.createElement('div');
            div.className = 'doc-box mb-3';

            div.innerHTML = `
        <label class="doc-title">Título do documento</label>
        <input class="form-control mb-2" type="text" name="titulo_documento[]" required>
        <input class="form-control" type="file" name="documentos[]" required>
    `;

            document.getElementById('docs').appendChild(div);
        }
    </script>
    <script>
        function selecionarTipo(valor, botaoAtivo) {
            document.getElementById('tipo').value = valor;

            document.querySelectorAll('.btn-filtro').forEach(btn => {
                btn.classList.remove('ativo');
                btn.textContent = btn.dataset.inativo;
            });

            botaoAtivo.classList.add('ativo');
            botaoAtivo.textContent = botaoAtivo.dataset.ativo;
        }
    </script>
    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
            let formValido = true;

            // EMAIL
            const emailInput = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

            if (!emailRegex.test(emailInput.value)) {
                emailInput.classList.add('is-invalid');
                emailInput.classList.remove('is-valid');
                formValido = false;
            } else {
                emailInput.classList.remove('is-invalid');
                emailInput.classList.add('is-valid');
            }

            // TELEFONE (opcional, mas validado se preenchido)
            const telefoneInput = document.getElementById('telefone');
            const telefoneRegex = /^[0-9()\s\-+]{8,20}$/;

            if (telefoneInput.value !== '' && !telefoneRegex.test(telefoneInput.value)) {
                telefoneInput.classList.add('is-invalid');
                formValido = false;
            } else {
                telefoneInput.classList.remove('is-invalid');
            }

            // DOCUMENTOS (garante pelo menos 1)
            const arquivos = document.querySelectorAll('input[type="file"]');
            if (arquivos.length === 0) {
                alert('Adicione pelo menos um documento.');
                formValido = false;
            }

            if (!formValido) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    </script>
    <script>
        function validarCNS(cns) {
            cns = cns.replace(/\D/g, '');

            if (cns.length !== 15) return false;

            let soma = 0;
            let peso = 15;

            if (['1', '2'].includes(cns.charAt(0))) {
                for (let i = 0; i < 15; i++) {
                    soma += parseInt(cns.charAt(i)) * peso--;
                }
                return soma % 11 === 0;

            } else if (['7', '8', '9'].includes(cns.charAt(0))) {
                for (let i = 0; i < 15; i++) {
                    soma += parseInt(cns.charAt(i)) * peso--;
                }
                return soma % 11 === 0;
            }

            return false;
        }
    </script>




</body>

</html>