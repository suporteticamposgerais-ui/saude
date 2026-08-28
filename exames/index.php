<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastro de Pedido de Exame</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <style>
        .card-exames .card-header {
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: #fff;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
            text-align: center;
        }

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

        .btn-filtro {
            border: 1px solid #ccc;
            padding: 8px 14px;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            margin-right: 8px;
            font-size: 14px;
        }

        .btn-filtro.ativo {
            border-color: #0d6efd;
            background: #e7f1ff;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            color: #333;
        }

        .doc-box {
            border: 1px dashed #cbd5e1;
            padding: 12px;
            border-radius: 8px;
            background: #f8fafc;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            display: block;
        }

        .btn-add-doc {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #22c55e;
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
    <!-- Icone do Whats -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body>
    <div class="container">
        <div class="card shadow-sm card-exames">
            <div class="card-header">
                Pedido de Exame
            </div>
            <div class="card-body">
                <div class="alert alert-info-custom mb-4">
                    📄 Anexe todos os documentos necessários (pedido médico, cartão SUS, RG, etc.)
                </div>

                <div id="alertaErro" class="alert alert-danger d-none"></div>

                <form method="post" action="cadastro_exame.php" enctype="multipart/form-data">

                    <div class="filtros container my-3">
                        <input type="hidden" name="tipo" id="tipo">

                        <button type="button" class="btn-filtro" data-ativo="🟢 Secretaria de Saúde"
                            data-inativo="🔴 Secretaria de Saúde" onclick="selecionarTipo('baixa', this)">
                            🔴 Secretaria de Saúde
                        </button>

                        <button type="button" class="btn-filtro" data-ativo="🟢 Policlínica"
                            data-inativo="🔴 Policlínica" onclick="selecionarTipo('alta', this)">
                            🔴 Policlínica
                        </button>
                    </div>
                    <!-- Formulário completo escondido -->
                    <div id="formCompleto" style="display:none;">
                        <!-- resto do formulário aqui -->

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do paciente</label>
                                <input class="form-control" type="text" name="nome_paciente" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Cartão SUS</label>
                                <input class="form-control" type="text" name="cartao_sus" id="cartao_sus" required>
                                <div class="invalid-feedback">Cartão SUS inválido.</div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Rua</label>
                                <input class="form-control" type="text" name="rua" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Número</label>
                                <input class="form-control" type="text" name="numero" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Bairro</label>
                                <input class="form-control" type="text" name="bairro" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>

                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">
                                        <i class="fab fa-whatsapp"></i>
                                    </span>

                                    <input class="form-control" type="text" name="telefone" id="telefone"
                                        placeholder="(00) 00000-0000" required>
                                </div>
                            </div>


                            <div class="col-md-4">
                                <label class="form-label">E-mail</label>
                                <input class="form-control" type="email" name="email" id="email" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Exame / Consulta solicitada</label>
                                <select class="form-control" name="exame_solicitado" required>
                                    <option value="">Selecione</option>

                                    <optgroup label="Consultas - Especialidades">
                                        <option>Consulta - Clínica Geral</option>
                                        <option>Consulta - Pediatria</option>
                                        <option>Consulta - Ginecologia</option>
                                        <option>Consulta - Obstetrícia</option>
                                        <option>Consulta - Cardiologia</option>
                                        <option>Consulta - Dermatologia</option>
                                        <option>Consulta - Neurologia</option>
                                        <option>Consulta - Ortopedia</option>
                                        <option>Consulta - Oftalmologia</option>
                                        <option>Consulta - Otorrinolaringologia</option>
                                        <option>Consulta - Urologia</option>
                                        <option>Consulta - Endocrinologia</option>
                                        <option>Consulta - Gastroenterologia</option>
                                        <option>Consulta - Pneumologia</option>
                                        <option>Consulta - Reumatologia</option>
                                        <option>Consulta - Oncologia</option>
                                        <option>Consulta - Psiquiatria</option>
                                        <option>Consulta - Psicologia</option>
                                        <option>Consulta - Fonoaudiologia</option>
                                        <option>Consulta - Fisioterapia</option>
                                        <option>Consulta - Nutrição</option>
                                        <option>Consulta - Terapia Ocupacional</option>
                                        <option>Consulta - Assistência Social</option>
                                    </optgroup>

                                    <optgroup label="Exames de Sangue">
                                        <option>Hemograma completo</option>
                                        <option>Glicemia de jejum</option>
                                        <option>Hemoglobina glicada (HbA1c)</option>
                                        <option>Colesterol total</option>
                                        <option>HDL</option>
                                        <option>LDL</option>
                                        <option>Triglicerídeos</option>
                                        <option>Ureia</option>
                                        <option>Creatinina</option>
                                        <option>TGO (AST)</option>
                                        <option>TGP (ALT)</option>
                                        <option>Ácido úrico</option>
                                        <option>TSH</option>
                                        <option>T4 Livre</option>
                                        <option>PCR</option>
                                    </optgroup>

                                    <optgroup label="Exames de Urina">
                                        <option>EAS (Urina tipo 1)</option>
                                        <option>Urocultura</option>
                                        <option>Microalbuminúria</option>
                                    </optgroup>

                                    <optgroup label="Exames de Fezes">
                                        <option>Parasitológico de fezes</option>
                                        <option>Sangue oculto nas fezes</option>
                                    </optgroup>

                                    <optgroup label="Exames de Imagem">
                                        <option>Raio-X</option>
                                        <option>Ultrassonografia abdominal</option>
                                        <option>Ultrassonografia pélvica</option>
                                        <option>Ultrassonografia obstétrica</option>
                                        <option>Ultrassonografia transvaginal</option>
                                        <option>Mamografia</option>
                                    </optgroup>

                                    <optgroup label="Exames Cardiológicos">
                                        <option>Eletrocardiograma (ECG)</option>
                                        <option>Teste ergométrico</option>
                                        <option>Holter 24h</option>
                                        <option>MAPA 24h</option>
                                        <option>Ecocardiograma</option>
                                    </optgroup>

                                    <optgroup label="Exames Preventivos">
                                        <option>Papanicolau (Citologia oncótica)</option>
                                        <option>PSA total</option>
                                        <option>PSA livre</option>
                                    </optgroup>

                                    <optgroup label="Sorologias">
                                        <option>HIV</option>
                                        <option>Hepatite B</option>
                                        <option>Hepatite C</option>
                                        <option>VDRL (Sífilis)</option>
                                        <option>Dengue</option>
                                        <option>Toxoplasmose</option>
                                    </optgroup>

                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacoes"></textarea>
                            </div>
                        </div>

                        <hr>

                        <h5 class="section-title mt-4">Documentos</h5>

                        <div id="docs">
                            <div class="doc-box mb-3">
                                <label class="doc-title">Título do documento</label>
                                <input class="form-control mb-2" type="text" name="titulo_documento[]" required>
                                <input class="form-control" type="file" name="documentos[]" required>
                            </div>
                        </div>

                        <button type="button" class="btn-add-doc" onclick="addDocumento()" title="Adicionar documento">
                            +
                        </button>


                        <button class="btn btn-primary w-100 mt-3">
                            Cadastrar Pedido
                        </button>

                    </div>

                </form>
            </div>
        </div>
    </div>
    <script>
        function selecionarTipo(valor, botao) {
            // Atualiza o hidden input
            document.getElementById('tipo').value = valor;

            // Remove estado ativo de todos
            document.querySelectorAll('.btn-filtro').forEach(btn => {
                btn.classList.remove('ativo');
                btn.innerText = btn.dataset.inativo;
            });

            // Ativa o botão clicado
            botao.classList.add('ativo');
            botao.innerText = botao.dataset.ativo;

            // Mostra o restante do formulário
            document.getElementById('formCompleto').style.display = 'block';
        }

        // Bloqueia submit se tipo não selecionado
        document.querySelector('form').addEventListener('submit', function (e) {
            const tipo = document.getElementById('tipo').value;
            if (!tipo) {
                e.preventDefault();
                alert('❌ Selecione o tipo de pedido antes de enviar o formulário.');
                return false;
            }
        });
    </script>
    <script>
        function selecionarTipo(valor, botao) {
            // Atualiza o hidden input
            document.getElementById('tipo').value = valor;

            // Remove estado ativo de todos os botões
            document.querySelectorAll('.btn-filtro').forEach(b => {
                b.classList.remove('ativo');
                b.innerText = b.dataset.inativo;
            });

            // Ativa o botão clicado
            botao.classList.add('ativo');
            botao.innerText = botao.dataset.ativo;

            // Mostra o restante do formulário
            document.getElementById('formCompleto').style.display = 'block';
        }

        // Bloqueia envio do formulário se tipo não selecionado
        document.querySelector('form').addEventListener('submit', function (e) {
            const tipo = document.getElementById('tipo').value;
            if (!tipo) {
                e.preventDefault();
                alert('❌ Selecione o tipo de pedido antes de enviar o formulário.');
            }
        });
    </script>

    <script>
        function addDocumento() {
            const container = document.getElementById('docs');

            const div = document.createElement('div');
            div.className = 'doc-box mb-3';

            div.innerHTML = `
        <label class="doc-title">Título do documento</label>
        <input class="form-control mb-2" type="text" name="titulo_documento[]" required>
        <input class="form-control" type="file" name="documentos[]" required>
    `;

            container.appendChild(div);
        }
    </script>
    <script>
        const form = document.querySelector('form');
        const cnsInput = document.getElementById('cartao_sus');
        const emailInput = document.getElementById('email');
        const telInput = document.getElementById('telefone');

        // Validações
        function validarCNS(cns) {
            cns = cns.replace(/\D/g, '');
            if (cns.length !== 15) return false;
            let soma = 0,
                peso = 15;
            for (let i = 0; i < 15; i++) soma += parseInt(cns[i]) * peso--;
            return soma % 11 === 0;
        }

        function formatarTelefone(valor) {
            valor = valor.replace(/\D/g, '').slice(0, 11);
            if (valor.length > 10) return valor.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            return valor.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
        }

        function telefoneValido(valor) {
            const num = valor.replace(/\D/g, '');
            if (!(num.length === 10 || num.length === 11)) return false;
            if (/^(\d)\1+$/.test(num)) return false; // números repetidos
            return true;
        }

        // Eventos de input
        cnsInput.addEventListener('input', () => {
            let valor = cnsInput.value.replace(/\D/g, '').slice(0, 15);
            valor = valor.replace(/^(\d{3})(\d{4})(\d{4})(\d{4})$/, '$1 $2 $3 $4');
            cnsInput.value = valor;

            if (validarCNS(valor)) {
                cnsInput.classList.add('is-valid');
                cnsInput.classList.remove('is-invalid');
            } else {
                cnsInput.classList.add('is-invalid');
                cnsInput.classList.remove('is-valid');
            }
        });

        emailInput.addEventListener('input', () => {
            if (emailInput.checkValidity()) {
                emailInput.classList.add('is-valid');
                emailInput.classList.remove('is-invalid');
            } else {
                emailInput.classList.add('is-invalid');
                emailInput.classList.remove('is-valid');
            }
        });

        telInput.addEventListener('input', () => {
            telInput.value = formatarTelefone(telInput.value);

            if (telefoneValido(telInput.value)) {
                telInput.classList.add('is-valid');
                telInput.classList.remove('is-invalid');
            } else {
                telInput.classList.add('is-invalid');
                telInput.classList.remove('is-valid');
            }
        });

        function existeCampoInvalido() {
            return document.querySelectorAll('.is-invalid').length > 0;
        }
        // Validação final no submit
        form.addEventListener('submit', function (e) {
            let erros = [];

            if (!validarCNS(cnsInput.value)) erros.push('Cartão SUS inválido.');
            if (!emailInput.checkValidity()) erros.push('E-mail inválido.');
            if (!telefoneValido(telInput.value)) erros.push('Telefone inválido ou vazio.');

            // força validação final
            if (!validarCNS(cnsInput.value)) {
                erros.push('Cartão SUS inválido.');
                cnsInput.classList.add('is-invalid');
            }

            if (!emailInput.checkValidity()) {
                erros.push('E-mail inválido.');
                emailInput.classList.add('is-invalid');
            }

            if (!telefoneValido(telInput.value)) {
                erros.push('Telefone inválido ou vazio.');
                telInput.classList.add('is-invalid');
            }



            if (erros.length > 0) {
                e.preventDefault();
                alert('❌ Verifique os campos:\n' + erros.join('\n'));
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    </script>


</body>

</html>