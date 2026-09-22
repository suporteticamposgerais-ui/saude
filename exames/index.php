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
            padding: 18px 20px;
            font-size: 21px;
            font-weight: 600;
            text-align: center;
            border-radius: 0;
        }

        body {
            background: #f4f6f8;
            font-family: Arial;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
        }

        .card {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #dfe4ea;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .card-body {
            padding: 12px 18px 20px;
            background: #f5f5f5;
        }

        .action-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 12px 0 18px;
            width: 100%;
        }

        .filtros {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: nowrap;
            margin: 0;
            width: 100%;
            max-width: 700px;
        }

        .btn-filtro {
            border: 1px solid #cfcfcf;
            padding: 0 18px;
            border-radius: 12px;
            background: #f8f9fa;
            cursor: pointer;
            font-size: 17px;
            width: 100%;
            max-width: 260px;
            height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            transition: all 0.2s ease;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,0.02);
            color: #282828;
        }

        .btn-filtro.ativo {
            border-color: #0d6efd;
            background: #e7f1ff;
            font-weight: bold;
        }

        .btn-acompanhamento {
            min-width: 200px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #0d6efd;
            border-radius: 10px;
            background: #fff;
            color: #0d6efd;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            padding: 0 18px;
            white-space: nowrap;
            margin-left: auto;
        }

        .btn-acompanhamento:hover {
            background: #eef5ff;
            text-decoration: none;
            color: #0d6efd;
        }

        .acompanhamento-link {
            font-size: 15px;
            color: #2d2d2d;
            text-decoration: none;
            line-height: 1.5;
            display: block;
            text-align: center;
            white-space: nowrap;
            margin-left: 0;
            margin-top: 2px;
        }

        .acompanhamento-link a {
            color: #0d6efd;
            font-weight: 700;
            text-decoration: underline;
        }

        .form-control[type="file"] {
            padding: 10px 12px;
            line-height: 1.3;
            min-height: 48px;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
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
            position: relative;
        }

        .doc-box-principal {
            padding-top: 12px;
        }

        .doc-close {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 26px;
            height: 26px;
            border: none;
            border-radius: 50%;
            background: #e5e7eb;
            color: #444;
            font-size: 18px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .doc-close:hover {
            background: #f1c5c5;
            color: #a11c1c;
        }

        .doc-info-box {
            border: 1px solid #dfe8f7;
            background: #f4f8ff;
            border-radius: 10px;
            padding: 16px 18px;
            margin: 10px 0 18px;
            color: #2a3d57;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.4);
        }

        .doc-info-box strong {
            color: #1b3964;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
            color: #2d2d2d;
        }

        .doc-action-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            position: relative;
            z-index: 2;
        }

        .btn-add-doc {
            background: #19b25c;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            box-shadow: 0 6px 16px rgba(25, 178, 92, 0.22);
            transition: transform .2s ease, box-shadow .2s ease;
            flex-shrink: 0;
        }

        .btn-add-doc:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(25, 178, 92, 0.28);
        }

        .btn-add-doc:active {
            transform: scale(0.98);
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
                <div id="alertaErro" class="alert alert-danger d-none"></div>

                <form method="post" action="cadastro_exame.php" enctype="multipart/form-data">

                    <div class="action-row">
                        <div class="filtros">
                            <input type="hidden" name="complexidade" id="complexidade">

                            <button type="button" class="btn-filtro" data-ativo="🟢 Secretaria de Saúde"
                                data-inativo="🔴 Secretaria de Saúde" onclick="selecionarComplexidade('baixa', this)">
                                🔴 Secretaria de Saúde
                            </button>

                            <button type="button" class="btn-filtro" data-ativo="🟢 Policlínica"
                                data-inativo="🔴 Policlínica" onclick="selecionarComplexidade('media', this)">
                                🔴 Policlínica
                            </button>
                        </div>

                        <div class="acompanhamento-link">
                            Já realizou um pedido? <a href="login.php">clique aqui</a> para acompanhar seu pedido.
                        </div>
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
                                <input class="form-control" type="text" name="cartao_sus" id="cartao_sus" maxlength="18" inputmode="numeric" pattern="[0-9\s]*" required>
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
                                        placeholder="(00) 00000-0000" maxlength="15" inputmode="numeric" pattern="[0-9()\-\s]*" required>
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
                                        <option>Exame - Hemograma completo</option>
                                        <option>Exame - Glicemia de jejum</option>
                                        <option>Exame - Hemoglobina glicada (HbA1c)</option>
                                        <option>Exame - Colesterol total</option>
                                        <option>Exame - HDL</option>
                                        <option>Exame - LDL</option>
                                        <option>Exame - Triglicerídeos</option>
                                        <option>Exame - Ureia</option>
                                        <option>Exame - Creatinina</option>
                                        <option>Exame - TGO (AST)</option>
                                        <option>Exame - TGP (ALT)</option>
                                        <option>Exame - Ácido úrico</option>
                                        <option>Exame - TSH</option>
                                        <option>Exame - T4 Livre</option>
                                        <option>Exame - PCR</option>
                                    </optgroup>

                                    <optgroup label="Exames de Urina">
                                        <option>Exame - EAS (Urina tipo 1)</option>
                                        <option>Exame - Urocultura</option>
                                        <option>Exame - Microalbuminúria</option>
                                    </optgroup>

                                    <optgroup label="Exames de Fezes">
                                        <option>Exame - Parasitológico de fezes</option>
                                        <option>Exame - Sangue oculto nas fezes</option>
                                    </optgroup>

                                    <optgroup label="Exames de Imagem">
                                        <option>Exame - Raio-X</option>
                                        <option>Exame - Ultrassonografia abdominal</option>
                                        <option>Exame - Ultrassonografia pélvica</option>
                                        <option>Exame - Ultrassonografia obstétrica</option>
                                        <option>Exame - Ultrassonografia transvaginal</option>
                                        <option>Exame - Mamografia</option>
                                    </optgroup>

                                    <optgroup label="Exames Cardiológicos">
                                        <option>Exame - Eletrocardiograma (ECG)</option>
                                        <option>Exame - Teste ergométrico</option>
                                        <option>Exame - Holter 24h</option>
                                        <option>Exame - MAPA 24h</option>
                                        <option>Exame - Ecocardiograma</option>
                                    </optgroup>

                                    <optgroup label="Exames Preventivos">
                                        <option>Exame - Papanicolau (Citologia oncótica)</option>
                                        <option>Exame - PSA total</option>
                                        <option>Exame - PSA livre</option>
                                    </optgroup>

                                    <optgroup label="Sorologias">
                                        <option>Exame - HIV</option>
                                        <option>Exame - Hepatite B</option>
                                        <option>Exame - Hepatite C</option>
                                        <option>Exame - VDRL (Sífilis)</option>
                                        <option>Exame - Dengue</option>
                                        <option>Exame - Toxoplasmose</option>
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

                        <div class="doc-info-box">
                            <div style="font-size: 15px; font-weight: 600; margin-bottom: 6px;">
                                Anexe os documentos necessários para o atendimento, como pedido médico, cartão SUS, RG ou outros comprovantes relacionados ao caso.
                            </div>
                            <div>
                                <strong>Formatos permitidos:</strong> PDF, JPG, JPEG, PNG, DOC e DOCX. Outros formatos não serão aceitos.
                            </div>
                        </div>

                        <div id="docs">
                            <div class="doc-box mb-3 doc-box-principal">
                                <label class="doc-title">Título do documento</label>
                                <input class="form-control mb-2" type="text" name="titulo_documento[]" required>
                                <input class="form-control" type="file" name="documentos[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                            </div>
                        </div>

                        <div class="doc-action-bar">
                            <button type="button" class="btn-add-doc" onclick="addDocumento()" title="Adicionar novo arquivo" aria-label="Adicionar novo arquivo">
                                Adicionar novo arquivo
                            </button>
                        </div>

                        <button class="btn btn-primary w-100 mt-3">
                            Cadastrar Pedido
                        </button>

                    </div>

                </form>
            </div>
        </div>
    </div>
    <script>
        function selecionarComplexidade(valor, botao) {
            document.getElementById('complexidade').value = valor;

            document.querySelectorAll('.btn-filtro').forEach(btn => {
                btn.classList.remove('ativo');
                btn.innerText = btn.dataset.inativo;
            });

            botao.classList.add('ativo');
            botao.innerText = botao.dataset.ativo;

            document.getElementById('formCompleto').style.display = 'block';
        }

        function ajustarMobile() {
            const largura = window.innerWidth;
            const filtros = document.querySelector('.filtros');
            const acompanhamento = document.querySelector('.acompanhamento-link');
            const actionRow = document.querySelector('.action-row');

            if (filtros) {
                filtros.style.display = 'flex';
                filtros.style.gap = largura <= 767 ? '10px' : '12px';
                filtros.style.width = '100%';
                filtros.style.justifyContent = 'center';
                filtros.style.flexWrap = largura <= 767 ? 'wrap' : 'nowrap';
            }

            if (acompanhamento) {
                acompanhamento.style.display = 'block';
                acompanhamento.style.marginLeft = '0';
                acompanhamento.style.width = '100%';
                acompanhamento.style.textAlign = 'center';
                acompanhamento.style.fontSize = largura <= 767 ? '13px' : '14px';
                acompanhamento.style.whiteSpace = largura <= 767 ? 'normal' : 'nowrap';
            }

            if (actionRow) {
                actionRow.style.flexDirection = 'column';
                actionRow.style.gap = '12px';
                actionRow.style.marginBottom = largura <= 767 ? '16px' : '24px';
                actionRow.style.justifyContent = 'center';
                actionRow.style.alignItems = 'center';
            }
        }

        window.addEventListener('resize', ajustarMobile);
        window.addEventListener('load', ajustarMobile);

        document.querySelector('form').addEventListener('submit', function (e) {
            const complexidade = document.getElementById('complexidade').value;
            if (!complexidade) {
                e.preventDefault();
                alert('❌ Selecione a complexidade antes de enviar o formulário.');
                return false;
            }
        });
    </script>

    <script>
        function validarDocumento(input) {
            const tiposPermitidos = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            if (!input.files || !input.files.length) return true;

            const nomeArquivo = input.files[0].name.toLowerCase();
            const extensao = nomeArquivo.split('.').pop();

            if (!tiposPermitidos.includes(extensao)) {
                alert('Formato de arquivo inválido. Use PDF, JPG, JPEG, PNG, DOC ou DOCX.');
                input.value = '';
                return false;
            }

            return true;
        }

        function removerDocumento(elemento) {
            if (!elemento) return;
            const container = document.getElementById('docs');
            const totalDocumentos = container.querySelectorAll('.doc-box').length;

            if (container && totalDocumentos > 1) {
                elemento.remove();
            } else {
                alert('É necessário manter ao menos um documento na solicitação.');
            }
        }

        function addDocumento() {
            const container = document.getElementById('docs');

            const div = document.createElement('div');
            div.className = 'doc-box mb-3';

            div.innerHTML = `
        <button type="button" class="doc-close" aria-label="Fechar documento" onclick="removerDocumento(this.closest('.doc-box'))">×</button>
        <label class="doc-title">Título do documento</label>
        <input class="form-control mb-2" type="text" name="titulo_documento[]" required>
        <input class="form-control" type="file" name="documentos[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required onchange="validarDocumento(this)">
    `;

            container.appendChild(div);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[name="documentos[]"]').forEach(input => {
                input.addEventListener('change', () => validarDocumento(input));
            });
        });
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