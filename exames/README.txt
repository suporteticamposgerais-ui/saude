PORTAL DE EXAMES E CONSULTAS — GUIA RÁPIDO

MANUAL COMPLETO
Consulte README.md para fluxos de paciente e administrador, banco, segurança, catálogo, documentos, fila e manutenção.

INSTALAÇÃO LIMPA NO XAMPP
1. Coloque a pasta exames em C:\xampp\htdocs\exames.
2. Inicie Apache e MySQL no painel do XAMPP.
3. Importe servicos.sql pelo phpMyAdmin.
4. Acesse http://localhost/exames/index.php.

ATENÇÃO AO BANCO
servicos.sql contém DROP DATABASE IF EXISTS servicos. Importá-lo apaga e recria o banco servicos. Use apenas para instalação limpa ou teste descartável; não é uma migração de atualização.

ACESSOS LOCAIS DE INSTALAÇÃO
Paciente: http://localhost/exames/login.php
Administrador padrão: admin@camposgerais.mg.gov.br / Admin@2026!
Troque a senha administrativa antes de expor o sistema fora do ambiente local.

FLUXO DO PEDIDO
Paciente envia procedimento e documento -> aguardando_analise.
Administrador pode aprovar para aguardando_vaga, colocar em espera, ou cancelar.
Paciente responde a pendência -> aguardando_nova_analise.
Somente pedidos aguardando_vaga entram na posição atual da fila.

CATÁLOGO
Administração: admin/catalogo_exames.php (perfis admin e gestor).
Desative procedimentos usados em pedidos em vez de apagá-los ou editá-los. Unidade e complexidade são campos diferentes.

GERADORES DE TESTE
Estão em scripts/ e só executam pelo terminal, com --confirm. Verifique primeiro que a conexão aponta para um banco de teste. Até 50 pacientes x 63 procedimentos podem criar 3.150 pedidos e cerca de 82 MB de anexos. A senha das contas novas é Teste@123.

RECUPERAÇÃO DE SENHA
O link é enviado por e-mail e exige APP_PUBLIC_URL, APP_MAIL_FROM e envio de e-mail configurado no PHP. Sem isso, nenhum link é enviado.

O README.md contém as instruções completas e os limites conhecidos desta versão.
