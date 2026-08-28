<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastro de Pedido de Exame</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f8;
            font-family: Arial;
        }

        .container {
            max-width: 850px;
            margin: 30px auto;
        }

        .card {
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                Pedido de Exame
            </div>

            <div class="card-body">

                <!-- ALERTA TOPO -->
                <div id="alertaErro" class="alert alert-danger d-none"></div>

                <form method="post" action="cadastro_exame.php" enctype="multipart/form-data" novalidate>

                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <label class="form-label">Nome do paciente</label>
                            <input class="form-control" type="text" name="nome_paciente" required>
                        </div>

                        <div class="col-md-6 col-12">
                            <label class="form-label">Cartão SUS</label>
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
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4 col-12">
                            <label class="form-label">Telefone</label>
                            <input class="form-control" type="text" name="telefone">
                        </div>

                        <div class="col-md-4 col-12">
                            <label class="form-label">E-mail</label>
                            <input class="form-control" type="email" name="email" required>
                            <div class="invalid-feedback">
                                E-mail inválido.
                            </div>
                        </div>

                        <div class="col-md-4 col-12">
                            <label class="form-label">Exame solicitado</label>
                            <input class="form-control" type="text" name="exame_solicitado" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>

                    <hr>

                    <h6>Documentos</h6>
                    <div id="docs">
                        <div class="mb-3">
                            <input class="form-control mb-2" type="text" name="titulo_documento[]" placeholder="Título do documento" required>
                            <input class="form-control" type="file" name="documentos[]" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-3">
                        Cadastrar Pedido
                    </button>

                </form>
            </div>
        </div>
    </div>

    <script>
        /* =============================
   MÁSCARA AUTOMÁTICA CNS
============================= */
        const cnsInput = document.getElementById('cartao_sus');

        cnsInput.addEventListener('input', () => {
            let valor = cnsInput.value.replace(/\D/g, '').slice(0, 15);

            valor = valor.replace(
                /^(\d{3})(\d{4})(\d{4})(\d{4})$/,
                '$1 $2 $3 $4'
            );

            cnsInput.value = valor;

            validarCnsTempoReal();
        });

        /* =============================
           VALIDAÇÃO CNS
        ============================= */
        function validarCNS(cns) {
            cns = cns.replace(/\D/g, '');

            if (cns.length !== 15) return false;

            let soma = 0;
            let peso = 15;

            for (let i = 0; i < 15; i++) {
                soma += parseInt(cns[i]) * peso--;
            }

            return soma % 11 === 0;
        }

        /* =============================
           VALIDAÇÃO EM TEMPO REAL
        ============================= */
        function validarCnsTempoReal() {
            if (validarCNS(cnsInput.value)) {
                cnsInput.classList.add('is-valid');
                cnsInput.classList.remove('is-invalid');
            } else {
                cnsInput.classList.add('is-invalid');
                cnsInput.classList.remove('is-valid');
            }
        }

        /* =============================
           SUBMIT + ALERTA TOPO
        ============================= */
        document.querySelector('form').addEventListener('submit', function(e) {
            const alerta = document.getElementById('alertaErro');
            alerta.classList.add('d-none');
            alerta.innerHTML = '';

            if (!validarCNS(cnsInput.value)) {
                e.preventDefault();
                alerta.innerHTML = '❌ Cartão SUS inválido. Verifique o número informado.';
                alerta.classList.remove('d-none');
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    </script>

</body>

</html>