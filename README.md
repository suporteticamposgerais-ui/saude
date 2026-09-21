# Sistema de Gestão de Solicitações de Saúde

## Visão geral

Este projeto é uma aplicação web em PHP com MySQL para gestão de solicitações de consultas e exames, com acompanhamento do pedido, controle de fila e administração interna do atendimento.

O fluxo principal está focado em:

- cadastro de pedido do paciente
- validação de dados pessoais e do pedido
- geração de protocolo único por ano
- acompanhamento do paciente por telefone, e-mail ou cartão SUS
- listagem administrativa dos pedidos
- controle de status e urgência
- envio e organização de documentos anexados

A estrutura é simples, procedural e direta, com conexão via PDO, sem framework.

---

## Objetivo do sistema

O sistema foi pensado para atender uma prefeitura ou unidade de saúde que precisa:

1. receber solicitações de exames e consultas
2. permitir que o paciente acompanhe seu pedido
3. manter uma fila de atendimento organizada
4. centralizar a gestão administrativa dos pedidos
5. registrar documentos e manter histórico do atendimento

---

## Regras de negócio atuais

### Complexidade do pedido
O campo principal da lógica do pedido é `complexidade`.

Valores usados no sistema:

- `baixa` = Secretaria de Saúde
- `media` = Policlínica
- `alta` = reserva de uso interno ou extensões futuras

A interface exibe nomes amigáveis para o usuário, mas o banco mantém o valor padronizado em `complexidade`.

### Protocolo
O protocolo do pedido é gerado automaticamente no formato:

- `SAU-2026-000001`
- `SAU-2026-000002`

O padrão segue o ano atual e reinicia a contagem a cada ano. A sequência é controlada pela tabela `protocolo_controle`.

### Estrutura da ordem
A lógica principal do pedido não usa mais `tipo` como campo de negócio.

O que ficou padronizado:

- `complexidade` para a regra de unidade/nível do atendimento
- `protocolo` para a identificação do pedido
- `status` para o fluxo de atendimento
- `exame_solicitado` e `especialidade` para o conteúdo do pedido

> A referência a `tipo` só se mantém em contextos específicos, como canal de mensagem, e não faz parte da regra principal do pedido.

---

## Funcionalidades principais

### 1. Cadastro da solicitação
A página [exames/index.php](exames/index.php) permite:

- selecionar a complexidade do atendimento
- preencher dados do paciente
- informar endereço e contato
- escolher exame ou consulta
- anexar documentos
- enviar a solicitação

### 2. Validação do cadastro
O backend valida:

- nome do paciente
- cartão SUS
- telefone
- e-mail
- endereço
- exame ou consulta solicitada
- complexidade válida

### 3. Login do paciente
A página [exames/login.php](exames/login.php) permite entrar com:

- telefone
- e-mail
- cartão SUS

A sessão é usada para recuperar o pedido do paciente e redirecionar para o acompanhamento.

### 4. Acompanhamento do paciente
A página [exames/acompanhamento.php](exames/acompanhamento.php) exibe:

- pedidos do paciente autenticado
- status atual
- documentos anexados
- dados da solicitação

### 5. Fila de atendimento
A página [exames/acompanhamento_fila.php](exames/acompanhamento_fila.php) mostra a posição do paciente na fila, sem expor demais dados dos demais usuários.

### 6. Administração da demanda
A página [exames/listagem_ordens.php](exames/listagem_ordens.php) permite:

- listar todos os pedidos
- filtrar por busca, especialidade e complexidade
- atender o pedido
- finalizar o pedido
- marcar ou remover emergência
- acompanhar totalizadores por status

---

## Estrutura do projeto

```text
saude/
├── exames/
│   ├── conexao.php
│   ├── index.php
│   ├── cadastro_exame.php
│   ├── login.php
│   ├── acompanhamento.php
│   ├── acompanhamento_fila.php
│   ├── listagem_ordens.php
│   ├── visualizar.php
│   ├── enviar_mensagem.php
│   ├── buscachat.php
│   ├── logout.php
│   ├── servicos.sql
│   ├── adicionar_emergencia.sql
│   ├── css/
│   ├── img/
│   └── uploads/
│       └── exames/
├── README.md
└── .gitignore
```

> O projeto principal está na pasta [exames](exames). Arquivos duplicados e versões antigas que aparecem no diretório não devem ser tratados como fonte oficial do fluxo principal.

---

## Banco de dados

### Script principal
O banco principal para desenvolvimento local está em:

- [exames/servicos.sql](exames/servicos.sql)

Esse script cria a base `servicos` e estrutura as tabelas principais:

- `pedidos_exames`
- `documentos_exames`
- `mensagens_exames`
- `protocolo_controle`

### Tabela principal: `pedidos_exames`
Principais campos:

- id
- nome_paciente
- cartao_sus
- telefone
- email
- rua
- numero
- bairro
- exame_solicitado
- especialidade
- observacoes
- status
- complexidade
- protocolo
- emergencia
- prioridade
- robo
- criado_em

### Tabela de documentos: `documentos_exames`
Armazena os anexos de cada pedido.

### Tabela de mensagens: `mensagens_exames`
Mantém e registra mensagens do pedido, com o canal de envio do campo `tipo` quando necessário.

### Tabela de controle do protocolo: `protocolo_controle`
Controla a sequência dos protocolos por ano para manter a geração em ordem.

---

## Fluxo principal

### 1. Cadastro
O usuário acessa [exames/index.php](exames/index.php) e cria o pedido.

A ação envia os dados para [exames/cadastro_exame.php](exames/cadastro_exame.php), que:

- valida os dados do paciente
- gera o protocolo do pedido
- grava o registro em `pedidos_exames`
- salva os documentos anexados em `documentos_exames`

### 2. Login e acompanhamento
Após o cadastro, o paciente acessa [exames/login.php](exames/login.php) e usa telefone, e-mail ou cartão SUS para visualizar seus pedidos em [exames/acompanhamento.php](exames/acompanhamento.php).

### 3. Administração
A equipe usa [exames/listagem_ordens.php](exames/listagem_ordens.php) para:

- visualizar todas as solicitações
- filtrar por especialidade e complexidade
- alterar status
- marcar urgência
- acompanhar fila e pendências

---

## Requisitos locais

Para rodar localmente, normalmente são necessários:

- PHP 8+
- Apache ou XAMPP
- MySQL
- permissões de escrita na pasta [exames/uploads](exames/uploads)

### Configuração de conexão
O arquivo [exames/conexao.php](exames/conexao.php) precisa apontar para o banco local correto:

- host
- usuário
- senha
- nome do banco

No ambiente padrão do projeto, o nome do banco é `servicos`.

---

## Importação do banco

Antes de testar o sistema localmente, importe o script principal:

- [exames/servicos.sql](exames/servicos.sql)

Se o banco já existir e estiver incompleto, é recomendado recriar a estrutura limpa a partir do script principal antes de validar o fluxo.

---

## Validações importantes

### Telefone
- aceita valor com máscara
- remove caracteres inválidos no backend
- rejeita número inválido

### Cartão SUS
- valida tamanho correto
- valida o algoritmo do CNS
- rejeita valores inválidos

### E-mail
- valida formato com `FILTER_VALIDATE_EMAIL`

### Arquivos
- aceita apenas extensões permitidas
- salva os documentos em pasta por pedido

---

## Observações de manutenção

### Arquivos preservados por request
Os arquivos a seguir foram mantidos fora da correção principal, conforme escopo solicitado:

- [exames/buscachat.php](exames/buscachat.php)
- [exames/visualizar.php](exames/visualizar.php)
- [exames/enviar_mensagem.php](exames/enviar_mensagem.php)

### Evolução do projeto
O sistema foi padronizado para a regra atual da saúde pública municipal:

- principal regra de negócio: `complexidade`
- principal identificador: `protocolo`
- principal fluxo: cadastro → login → acompanhamento → gestão

---

## Quick start

1. Configure o banco local MySQL.
2. Importe o script principal em [exames/servicos.sql](exames/servicos.sql).
3. Ajuste as credenciais em [exames/conexao.php](exames/conexao.php).
4. Verifique permissões de escrita em [exames/uploads](exames/uploads).
5. Inicie o servidor Apache/XAMPP.
6. Acesse o projeto e teste o fluxo:
   - [exames/index.php](exames/index.php)
   - [exames/login.php](exames/login.php)
   - [exames/acompanhamento.php](exames/acompanhamento.php)
   - [exames/listagem_ordens.php](exames/listagem_ordens.php)

---

## Observações finais

- O projeto é procedural, sem framework.
- A autenticação depende de sessão.
- O diretório de upload precisa estar gravável.
- O banco deve ser consistente com o modelo atual, especialmente em `complexidade` e `protocolo`.

---

## Conclusão

Este projeto foi organizado para ser um sistema funcional de gestão de solicitações de saúde, com foco em simplicidade, manutenção fácil e fluxo operacional real.

O estado atual do projeto está centrado em:

- cadastro de pedidos
- complexidade por unidade
- geração de protocolo por ano
- acompanhamento do paciente
- gestão da fila e do status
- organização dos documentos anexados
