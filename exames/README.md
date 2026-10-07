# Portal de Exames e Consultas — Manual Completo

## 1. O que é o sistema

O **Portal de Exames e Consultas** permite que pacientes façam solicitações pela internet e que a equipe administrativa/regulatória faça a análise, controle de pendências, fila, agendamento e conclusão do atendimento.

O sistema possui duas áreas principais:

- **Paciente:** cadastro, login, solicitação de exame/consulta, envio de documentos, acompanhamento do pedido, resposta a pendências e visualização do agendamento.
- **Administrador:** dashboard, gestão de pedidos, prontuário do paciente, análise de documentos, central de pendências, controle de fila, emergência, agendamento, retorno à fila, cancelamento e conclusão.

A ideia central é que **um paciente possui um cadastro único e pode ter vários pedidos ao longo do tempo**. Cada pedido possui protocolo, status, documentos, histórico e, quando necessário, agendamento.

---

# 2. Fluxo geral do sistema

O fluxo normal de uma solicitação é:

```text
Paciente se cadastra
       ↓
Paciente faz login
       ↓
Solicita exame/consulta + documentos
       ↓
Pedido = AGUARDANDO ANÁLISE
       ↓
Administrador analisa
       ├── Aprova → AGUARDANDO VAGA → FILA
       ├── Coloca em espera → EM ESPERA
       └── Cancela → CANCELADO
                         
EM ESPERA
       ↓
Paciente vê o motivo
       ↓
Paciente envia documento/resposta
       ↓
AGUARDANDO NOVA ANÁLISE
       ↓
Administrador revisa
       ├── Aprova → AGUARDANDO VAGA → FILA
       ├── Nova pendência → EM ESPERA
       └── Cancela → CANCELADO

AGUARDANDO VAGA
       ↓
Administrador pode marcar emergência ou manter normal
       ↓
Agendamento
       ↓
AGENDADO
       ├── Alterar agendamento → continua AGENDADO
       ├── Voltar para fila → AGUARDANDO VAGA
       ├── Cancelar → CANCELADO
       └── Concluir → FINALIZADO
```

### Exemplo completo

1. João cria sua conta.
2. João solicita **Consulta - Clínica Geral** e envia o pedido médico.
3. O pedido recebe um protocolo e fica **Aguardando Análise**.
4. O administrador percebe que falta um documento e coloca o pedido **Em Espera**, escrevendo: `É necessário enviar o pedido médico assinado.`
5. João entra no painel, vê exatamente essa pendência e usa **Adicionar documento / Responder pendência**.
6. O pedido muda para **Aguardando Nova Análise**. O administrador recebe uma notificação interna.
7. O administrador revisa o novo documento e aprova o pedido.
8. O pedido entra em **Aguardando Vaga**.
9. Quando houver disponibilidade, o administrador agenda data, horário, unidade, profissional e orientações.
10. João passa a ver o agendamento no painel.
11. Após o atendimento, o administrador usa **Concluir Ordem**, podendo registrar uma observação.
12. O pedido passa para **Finalizado** e todo o histórico permanece registrado.

---

# 3. Instalação no XAMPP

## 3.1. Requisitos

- Windows com XAMPP.
- Apache ativo.
- MySQL/MariaDB ativo.
- PHP com PDO MySQL habilitado.
- Navegador atualizado.

## 3.2. Instalar o projeto

1. Extraia a pasta `exames`.
2. Coloque-a em:

```text
C:\xampp\htdocs\exames
```

3. Abra o XAMPP.
4. Inicie **Apache** e **MySQL**.
5. Abra o phpMyAdmin.
6. Importe o arquivo:

```text
servicos.sql
```

O `servicos.sql` cria o banco `servicos`, suas tabelas e os dados iniciais do catálogo.

7. Acesse:

```text
http://localhost/exames/index.php
```

## 3.3. Banco já existente

`servicos.sql` é exclusivamente um script de instalação limpa, não uma atualização. Ele contém:

```sql
DROP DATABASE IF EXISTS servicos;
```

Importá-lo apaga e recria o banco `servicos`. **Não o execute sobre uma instalação com dados que deseja preservar.** Este pacote não inclui uma migração automática de atualização. Para atualizar uma instalação existente, faça backup e prepare uma migração incremental validada em uma cópia do banco.

## 3.4. Configuração do banco

O arquivo é:

```text
conexao.php
```

Por padrão:

```text
Host: localhost
Banco: servicos
Usuário: root
Senha: vazia
```

Também é possível usar variáveis de ambiente:

```text
DB_HOST
DB_NAME
DB_USER
DB_PASS
```

Em uma instalação real, prefira configurar as credenciais por ambiente em vez de deixar uma senha de banco diretamente no código.

Para recuperação de senha por e-mail, configure também:

```text
APP_PUBLIC_URL=https://seu-dominio/exames
APP_MAIL_FROM=no-reply@seu-dominio
```

O PHP do servidor precisa estar configurado para enviar e-mail. Sem essas configurações, a tela não revela o token; ela mostra uma resposta genérica e registra a falta de configuração no log do PHP.

---

# 4. Endereços principais

| Endereço | Função |
|---|---|
| `index.php` | Página inicial |
| `cadastro_paciente.php` | Cadastro de paciente |
| `login.php` | Login do paciente |
| `login_admin.php` | Login administrativo |
| `paciente/painel.php` | Painel do paciente |
| `paciente/solicitar.php` | Nova solicitação |
| `acompanhamento_fila.php` | Consulta da fila do próprio paciente autenticado |
| `admin/dashboard.php` | Dashboard administrativo |
| `admin/listagem_ordens.php` | Lista e filtros de pedidos |
| `admin/catalogo_exames.php` | Gestão do catálogo de procedimentos |
| `admin/pendencias.php` | Central de pendências |
| `admin/paciente_perfil.php?id=ID` | Prontuário/gestão de um paciente |
| `logout.php` | Encerramento da sessão |

---

# 5. Área do paciente

## 5.1. Cadastro

O paciente acessa:

```text
cadastro_paciente.php
```

São solicitados dados como:

- Nome.
- CPF.
- Data de nascimento.
- CNS/Cartão SUS.
- Telefone.
- E-mail.
- Endereço.
- Número.
- Bairro.
- Complemento.
- Senha.

### Validações importantes

- CPF precisa ser válido.
- CNS precisa ser válido.
- Telefone precisa possuir DDD + 8 ou 9 dígitos.
- E-mail precisa ser válido.
- Senha precisa ter **no mínimo 8 caracteres**.
- A senha precisa conter **pelo menos 1 letra maiúscula, 1 letra minúscula e 1 número**.
- O formulário mostra cada requisito em tempo real enquanto o paciente digita.
- A confirmação informa imediatamente se as duas senhas conferem.
- CPF, CNS e e-mail não podem estar duplicados.

### Exemplo

Um paciente cadastra:

```text
Nome: João da Silva
CPF: 123.456.789-00
CNS: 123 4567 8901 2345
E-mail: joao@exemplo.com
Senha: MinhaSenha2026
```

Depois do cadastro, ele pode entrar usando **CPF, CNS ou e-mail**.

### Como funciona a validação da senha

O sistema verifica os requisitos enquanto o usuário digita:

```text
✓ Pelo menos 8 caracteres
✓ Pelo menos 1 letra maiúscula
✓ Pelo menos 1 letra minúscula
✓ Pelo menos 1 número
```

Cada requisito é atualizado em tempo real. A confirmação também informa se as duas senhas conferem. A mesma regra é aplicada no backend, portanto alterar o HTML do navegador não permite cadastrar uma senha fora da política. A redefinição de senha usa exatamente a mesma regra.

---

# 6. Login do paciente

O endereço é:

```text
login.php
```

O campo de login aceita:

- CPF;
- CNS;
- E-mail.

A senha é a definida no cadastro.

As senhas são armazenadas usando `password_hash()` e verificadas com `password_verify()`.

Se o paciente esquecer a senha, pode utilizar:

```text
recuperar_senha.php
```

O fluxo atual de recuperação é baseado em **CPF + e-mail** e cria um token temporário para redefinição.

O link é enviado para o e-mail cadastrado e expira em duas horas. Configure `APP_PUBLIC_URL`, `APP_MAIL_FROM` e o envio de e-mail do PHP. Sem essas configurações, a resposta é genérica, o token não aparece na tela e a falha é registrada no log. Tokens novos são armazenados como hash; tokens antigos em texto puro continuam aceitos somente até expirarem ou serem usados.

---

# 7. Painel do paciente

A página inicial não exibe CPF, CNS, telefone ou e-mail. Esses dados continuam armazenados na conta e disponíveis internamente quando necessários, mas não ficam expostos no painel operacional do paciente.

Depois do login, o paciente utiliza:

```text
paciente/painel.php
```

Nesse painel ele consegue acompanhar seus pedidos e, conforme o status, visualizar diferentes informações.

## 7.1. Aguardando Análise

Significa que a solicitação foi recebida e ainda precisa ser analisada pela equipe.

O paciente deve aguardar a análise administrativa.

## 7.2. Em Espera

Significa que existe uma pendência que impede o avanço do pedido.

O sistema mostra:

- motivo da pendência;
- data/hora em que entrou em espera;
- há quanto tempo está esperando;
- botão **Adicionar documento / Responder pendência**.

### Exemplo

```text
Motivo:
É necessário enviar o pedido médico assinado e legível.
```

O paciente deve providenciar o documento e responder pelo próprio pedido.

## 7.3. Responder uma pendência

Ao clicar em **Adicionar documento / Responder pendência**, o paciente pode:

- adicionar um ou vários arquivos;
- informar o título de cada arquivo;
- remover uma linha adicionada por engano;
- enviar PDF, JPG, JPEG ou PNG.

Limites atuais:

- até 10 arquivos por resposta;
- até 10 MB por arquivo;
- título obrigatório para cada arquivo;
- título com no máximo 150 caracteres.

### Exemplo

```text
Título: Pedido médico assinado
Arquivo: pedido_medico.pdf

Título: Exame anterior
Arquivo: exame_anterior.jpg
```

Após o envio, o pedido **não volta automaticamente para a fila**.

Ele passa para:

```text
AGUARDANDO NOVA ANÁLISE
```

Isso força a equipe administrativa a conferir a resposta antes de liberar o pedido.

## 7.4. Aguardando Nova Análise

Significa que o paciente respondeu uma pendência e a equipe ainda precisa revisar o material enviado.

O paciente vê que sua resposta foi recebida e deve aguardar a nova análise.

## 7.5. Aguardando Vaga

Significa que o pedido foi aprovado na análise e está na fila aguardando disponibilidade.

## 7.6. Agendado

Quando o administrador agenda o atendimento, o paciente passa a visualizar:

- data;
- horário;
- unidade/local;
- profissional, quando informado;
- orientações do administrador.

### Exemplo de orientação

```text
Comparecer 15 minutos antes.
Levar documento de identidade, Cartão SUS e pedido médico original.
```

## 7.7. Cancelado

O paciente visualiza o motivo informado pelo administrador.

Um pedido cancelado **não volta automaticamente para a fila**.

## 7.8. Finalizado

Indica que o administrador concluiu a ordem após o atendimento.

---

# 8. Nova solicitação de exame/consulta

O endereço é:

```text
paciente/solicitar.php
```

O paciente escolhe uma unidade ativa e depois um procedimento. A troca de unidade filtra a lista e limpa o procedimento que estava selecionado. O servidor não confia no campo oculto de unidade: ele obtém unidade e complexidade do procedimento ativo selecionado no banco. Unidade e complexidade são campos independentes; no catálogo inicial, Secretaria está cadastrada como baixa e Policlínica como média.

Também pode informar:

- observações/motivo da solicitação;
- documentos.

## Documentos na solicitação inicial

É possível enviar até 10 documentos, com até 10 MB cada.

Cada arquivo recebe um título.

### Exemplo

```text
Procedimento: Consulta - Cardiologia
Observações: Retorno por orientação médica.

Documento 1:
Título: Pedido Médico
Arquivo: pedido.pdf

Documento 2:
Título: Exame de ECG
Arquivo: ecg.jpg
```

Ao enviar, o pedido recebe um protocolo e entra em:

```text
AGUARDANDO ANÁLISE
```

---

# 9. Área administrativa

O administrador acessa:

```text
login_admin.php
```

## Acesso administrativo padrão

Esta versão volta a usar o modelo administrativo original do sistema, com uma conta padrão criada automaticamente pelo `servicos.sql`.

Credenciais iniciais:

```text
E-mail: admin@camposgerais.mg.gov.br
Senha:  Admin@2026!
```

Acesse:

```text
http://localhost/exames/login_admin.php
```

Depois de entrar, o administrador é direcionado para a listagem de ordens.

### Importante

- O e-mail padrão aparece somente aqui na documentação de instalação; a tela de login não precisa pré-preencher o e-mail.
- A senha administrativa é armazenada no banco somente como hash.
- A conta e a senha padrão são conhecidas e servem para instalação/teste. Troque-a antes de expor o sistema fora do ambiente local.
- Nesta versão não existe `criar_admin.php`.
- Nesta versão também não foi implementada uma ferramenta específica de recuperação administrativa local.
- A criação de novos administradores e a recuperação administrativa local serão tratadas futuramente, quando essa parte for projetada de forma definitiva.
- O banco aceita os perfis `admin`, `gestor` e `atendente`. A tela do catálogo restringe alterações a `admin` e `gestor`, mas as outras telas ainda não possuem uma matriz completa de permissões por perfil.

# 10. Dashboard administrativo

Endereço:

```text
admin/dashboard.php
```

Exibe indicadores gerais de:

- total de pacientes;
- total de pedidos;
- aguardando análise;
- novas respostas;
- em espera;
- aguardando vaga;
- agendados;
- finalizados;
- cancelados.

Também existe acesso direto à **Central de Pendências**.

---

# 11. Lista de pedidos

Endereço:

```text
admin/listagem_ordens.php
```

Essa é a área principal para localizar solicitações.

## Filtros disponíveis

A listagem mostra 50 pedidos por página. A navegação mantém os filtros selecionados e informa quantos resultados foram encontrados.

### Busca livre

Pesquisa por:

- nome do paciente;
- CPF;
- CNS;
- protocolo.

### Status

Permite filtrar por status específico.

### Especialidade

As especialidades são carregadas diretamente da tabela `especialidades`.

### Exame/Consulta

Os procedimentos são carregados diretamente da tabela `exames`.

Isso significa que, quando um novo procedimento é cadastrado no banco, ele pode aparecer automaticamente no filtro, sem precisar alterar o HTML da página.

### Unidade

Unidade e complexidade são conceitos separados. O catálogo possui uma tabela `unidades`, atualmente com:

- `Secretaria de Saúde` (`secretaria`)
- `Policlínica` (`policlinica`)

Cada procedimento possui `unidade_id`, e cada pedido guarda uma cópia desse vínculo para preservar a unidade do pedido mesmo que o catálogo seja alterado no futuro.

A `complexidade` continua significando complexidade (`baixa`, `media`, `alta`) e não é usada como sinônimo de unidade. Isso evita que uma futura complexidade `alta` seja confundida com uma unidade.

---

# 12. Prontuário/gestão do paciente

Ao clicar em **Abrir Prontuário**, o administrador acessa:

```text
admin/paciente_perfil.php?id=ID
```

Essa página concentra o histórico do paciente.

Ela permite visualizar:

- dados cadastrais;
- todos os pedidos do paciente;
- status de cada pedido;
- protocolo;
- documentos;
- histórico de ações;
- pendências;
- respostas do paciente;
- agendamentos;
- orientações;
- motivos de cancelamento.

Também é possível abrir todos os arquivos do paciente em uma visualização centralizada.

---

# 13. Central de Pendências

Endereço:

```text
admin/pendencias.php
```

Essa tela existe para evitar que uma pendência fique esquecida.

Ela separa/permite filtrar:

- novas respostas dos pacientes;
- pedidos em espera;
- pedidos em espera há mais de 3 dias.

Também exibe:

- paciente;
- procedimento;
- status;
- tempo em espera;
- data da última resposta;
- quantidade de documentos;
- indicação de nova notificação.

## Exemplo de uso

O administrador entra pela manhã e vê:

```text
Novas respostas: 4
Em espera: 12
Em espera há mais de 3 dias: 3
```

Ele pode abrir cada paciente, conferir os documentos e decidir o próximo passo.

---

# 14. Como colocar um pedido em espera

A ação **Colocar em Espera** pode ser usada em pedidos que ainda estão em análise, em nova análise ou na fila.

O administrador deve informar o motivo.

O código exige pelo menos 10 caracteres.

### Bom exemplo

```text
Ausência do pedido médico assinado.
```

### Exemplo mais completo

```text
Falta documento de identidade e pedido médico assinado para validação da solicitação.
```

O sistema grava:

- status `em_espera`;
- motivo;
- data/hora de entrada em espera;
- histórico da ação.

A entrada deixa de ocupar a fila enquanto estiver em espera.

---

# 15. Como o paciente responde uma pendência

Fluxo recomendado:

1. Administrador coloca o pedido em espera.
2. Administrador escreve o motivo.
3. Paciente entra no painel.
4. Paciente lê o motivo.
5. Paciente envia os documentos solicitados.
6. Sistema muda para `aguardando_nova_analise`.
7. Sistema cria uma notificação administrativa.
8. Administrador abre a Central de Pendências.
9. Administrador revisa os documentos.
10. Administrador aprova, coloca novamente em espera ou cancela.

Esse mecanismo evita que um envio do paciente pule diretamente para a fila sem revisão.

---

# 16. Aprovar e colocar na fila

A ação **Aprovar p/ Fila** é usada quando o administrador considera a documentação/análise suficiente.

O pedido passa para:

```text
AGUARDANDO VAGA
```

O sistema define uma nova `data_entrada_fila`.

A partir daí, o pedido pode ser organizado pela fila e posteriormente agendado.

---

# 17. Emergência

Enquanto o pedido está em `aguardando_vaga`, o administrador pode:

- **Marcar Emergência**;
- **Remover Emergência**.

Ao marcar emergência:

```text
emergencia = 1
prioridade = 1
```

Pedido normal:

```text
emergencia = 0
prioridade = 2
```

O sistema registra a alteração no histórico.

> A classificação de emergência deve seguir os critérios e responsabilidades definidos pela instituição. O sistema apenas registra a classificação feita pelo operador.

---

# 18. Agendamento

O administrador pode agendar um pedido que esteja:

- `aguardando_vaga`; ou
- `agendado`, para alterar o agendamento existente.

O formulário solicita:

- data e horário;
- unidade/local;
- profissional, opcional;
- orientações ao paciente, opcional.

### Exemplo

```text
Data/hora: 15/10/2026 08:30
Unidade: Policlínica Municipal
Profissional: Dr. João Silva
Orientações: Levar documento de identidade, Cartão SUS e pedido médico.
```

Ao salvar:

- o pedido passa para `agendado`;
- um novo agendamento é criado;
- eventual agendamento ativo anterior é cancelado;
- o histórico registra a operação.

Isso evita manter dois agendamentos ativos para o mesmo pedido.

---

# 19. Alterar agendamento

Se o pedido já estiver agendado, o botão muda para **Alterar Agendamento**.

Exemplo:

```text
Antes:
10/10 às 08:00 — Policlínica

Depois:
15/10 às 14:30 — Policlínica
```

O sistema cancela o agendamento ativo anterior e cria o novo, preservando o registro histórico.

---

# 20. Voltar para a fila

**Voltar para Fila** é diferente de cancelar o pedido.

Essa ação existe especificamente para um pedido que já está `agendado` quando, por algum motivo administrativo, o atendimento não deve mais ocorrer naquele agendamento, mas o pedido continua válido.

O administrador deve informar uma justificativa de pelo menos 20 caracteres.

Exemplo:

```text
Paciente solicitou remarcação por impossibilidade de comparecer na data agendada.
```

O sistema:

1. cancela o agendamento ativo;
2. mantém o pedido existente;
3. muda para `aguardando_vaga`;
4. cria uma nova `data_entrada_fila`;
5. registra a justificativa no histórico.

Isso é diferente de cancelar o pedido.

---

# 21. Cancelar pedido

A ação **Cancelar Pedido** encerra o pedido.

O administrador deve informar uma justificativa de pelo menos 20 caracteres.

### Exemplo

```text
Solicitação cancelada após análise administrativa devido à ausência dos documentos necessários dentro do prazo.
```

O sistema:

- muda o status para `cancelado`;
- cancela eventual agendamento ativo;
- remove a entrada da fila;
- grava o motivo;
- registra a ação no histórico.

O pedido **não retorna para a fila**.

Se futuramente for necessário atender novamente o paciente, deve ser criada uma nova solicitação ou adotado um fluxo específico de reabertura em uma versão futura.

---

# 22. Concluir ordem

O botão **Concluir Ordem** aparece para pedido `agendado`.

O administrador pode informar uma observação opcional.

### Exemplo

```text
Atendimento realizado normalmente. Documentação conferida no local.
```

Ao concluir:

- o agendamento passa para `concluido`;
- o pedido passa para `finalizado`;
- a observação é gravada no histórico.

O pedido finalizado não pode ser cancelado pelas ações atuais.

---

# 23. Documentos

Os documentos são relacionados ao paciente e ao pedido.

O sistema guarda:

- título do documento;
- nome original do arquivo;
- nome físico armazenado;
- MIME type;
- tamanho;
- quem enviou;
- data/hora.

Os arquivos ficam em:

```text
uploads/exames/<ID_DO_PEDIDO>/
```

O download não é feito apontando diretamente para o arquivo. O sistema usa:

```text
paciente/documento_download.php
admin/documento_download.php
```

para controlar o acesso.

### Tipos aceitos no envio atual

- PDF
- JPG
- JPEG
- PNG

### Limites

- 10 MB por arquivo.
- Até 10 arquivos por envio/solicitação.

### Validação administrativa

Cada documento possui seu próprio status:

```text
pendente → validado
pendente → rejeitado
```

O administrador pode **Validar** ou **Rejeitar** cada documento. Ao rejeitar, deve informar um motivo com pelo menos 10 caracteres. O motivo fica gravado e aparece para o paciente.

Quando um documento pendente é rejeitado enquanto o pedido está em `aguardando_analise`, `aguardando_nova_analise`, `em_espera` ou `aguardando_vaga`, o pedido vai para `em_espera`, recebe o motivo e sai da fila. O paciente pode então corrigir o problema e enviar outra resposta. Documentos não podem ser rejeitados depois que o pedido foi agendado ou encerrado.

Validar um documento **não coloca automaticamente o pedido na fila**. Documento validado e aprovação do pedido são decisões diferentes. Depois de conferir os documentos, o administrador usa **Aprovar p/ Fila** quando o pedido estiver apto.

---

# 24. Histórico e auditoria

Cada pedido possui histórico na tabela:

```text
historico_pedidos
```

O histórico registra, entre outras informações:

- status anterior;
- status novo;
- ação realizada;
- observação/justificativa;
- tipo de autor;
- ID do autor;
- nome do autor;
- data/hora.

Exemplos de registros:

```text
Solicitação cadastrada pelo paciente
Pedido colocado em espera
Nova resposta do paciente
Aprovado na triagem e colocado na fila
Pedido agendado
Agendamento alterado
Retornado para a fila
Pedido cancelado
Procedimento concluído
```

Isso permite reconstruir o que aconteceu com um pedido sem depender apenas do status atual.

---

# 25. Notificações administrativas

Quando o paciente responde uma pendência, o sistema cria um registro em:

```text
notificacoes_admin
```

A notificação fica não lida até que a área administrativa trate o pedido.

O objetivo é evitar que uma nova documentação enviada pelo paciente passe despercebida.

Ao abrir o prontuário do paciente, as notificações relacionadas aos pedidos daquele paciente são marcadas como lidas.

---

# 26. Estados do pedido

Os principais estados atuais são:

| Status | Significado |
|---|---|
| `aguardando_analise` | Pedido recém-recebido aguardando primeira análise |
| `aguardando_nova_analise` | Paciente respondeu uma pendência e aguarda nova análise |
| `em_espera` | Existe pendência que impede o avanço |
| `aguardando_vaga` | Pedido aprovado e aguardando disponibilidade |
| `agendado` | Atendimento possui agendamento ativo |
| `em_andamento` | Estado previsto no banco, sem ação administrativa específica neste pacote |
| `realizado` | Estado previsto no banco, sem ação administrativa específica neste pacote |
| `finalizado` | Ordem concluída pelo administrador |
| `cancelado` | Pedido encerrado por cancelamento |

## Transições implementadas

| De | Ação | Para |
|---|---|---|
| Aguardando análise | Aprovar p/ fila | Aguardando vaga |
| Aguardando nova análise | Aprovar p/ fila | Aguardando vaga |
| Aguardando análise | Colocar em espera | Em espera |
| Aguardando nova análise | Colocar em espera | Em espera |
| Aguardando vaga | Colocar em espera | Em espera |
| Em espera | Liberar p/ fila | Aguardando vaga |
| Aguardando vaga | Agendar | Agendado |
| Agendado | Alterar agendamento | Agendado |
| Agendado | Voltar para fila | Aguardando vaga |
| Agendado | Concluir ordem | Finalizado |
| Pedidos não finalizados/cancelados | Cancelar | Cancelado |
| Aguardando vaga | Marcar emergência | Aguardando vaga |
| Aguardando vaga | Remover emergência | Aguardando vaga |
| Em espera | Resposta do paciente | Aguardando nova análise |

---

# 27. O que cada usuário pode fazer

## Paciente

Pode:

- criar sua conta;
- entrar usando CPF, CNS ou e-mail;
- recuperar a senha;
- solicitar exame/consulta;
- anexar documentos;
- acompanhar seus pedidos;
- visualizar pendências;
- responder pendências;
- adicionar novos documentos;
- visualizar agendamento;
- visualizar orientações administrativas;
- visualizar motivo de cancelamento;
- acompanhar o histórico/status dos seus pedidos.

Não pode:

- alterar o status do próprio pedido;
- colocar pedido em fila;
- agendar atendimento;
- cancelar administrativamente;
- marcar emergência;
- concluir ordem.

## Administrador

Pode:

- visualizar indicadores;
- pesquisar pedidos;
- filtrar por vários critérios;
- abrir o prontuário do paciente;
- analisar documentos;
- aprovar pedido para a fila;
- colocar pedido em espera;
- liberar pedido da espera;
- acompanhar novas respostas;
- marcar/remover emergência;
- agendar;
- alterar agendamento;
- devolver agendamento para a fila;
- cancelar pedido;
- concluir ordem;
- visualizar histórico;
- visualizar documentos.

---

# 28. Catálogo de exames e consultas

O catálogo fica nas tabelas:

```text
especialidades
exames
```

A tabela `exames` possui:

- especialidade;
- nome;
- tipo (`Consulta` ou `Exame`);
- complexidade;
- preparo;
- ativo/inativo.

Para cadastrar um novo procedimento, deve-se criar um registro na tabela `exames` associado a uma especialidade existente.

### Exemplo

```text
Especialidade: Cardiologia
Nome: Ecocardiograma
Tipo: Exame
Complexidade: media
```

O procedimento passa a fazer parte do catálogo utilizado pelo sistema e dos filtros administrativos dinâmicos.

Procedimentos podem ser cadastrados, editados e desativados em `admin/catalogo_exames.php`, acessível aos perfis `admin` e `gestor`. A tela valida os dados e impede duplicatas iguais. Procedimentos associados a pedidos não podem ser editados; para preservar o histórico, desative o antigo e cadastre outro. Desativar não apaga pedidos nem documentos existentes. O filtro administrativo identifica registros inativos para localizar pedidos antigos.

A tela não gerencia especialidades nem unidades; essas ainda são mantidas no banco. O formulário do paciente mostra todas as unidades ativas, e a complexidade permanece independente da unidade.

---

# 29. Fila

A consulta da fila exige **login do paciente**. Não existe mais consulta pública por telefone.

O paciente autenticado só consegue consultar os próprios pedidos. A página verifica o `paciente_id` da sessão no banco, portanto alterar um ID na URL não permite acessar a fila de outra pessoa.

A posição é calculada por procedimento, usando:

```text
prioridade ASC
data_entrada_fila ASC
criado_em ASC
```

Pedidos de emergência têm prioridade 1; pedidos normais têm prioridade 2. Somente pedidos em `aguardando_vaga` participam da posição da fila. Pedidos em análise, espera, agendados ou cancelados não ocupam posição.

Na listagem do paciente, outros usuários são mostrados apenas como **Outro paciente**, evitando exposição desnecessária de nomes.

# 30. Segurança atual

O projeto possui alguns mecanismos importantes:

- sessões seguras centralizadas em `helpers/auth.php`;
- autenticação separada para paciente e administrador;
- senhas com `password_hash`;
- consultas PDO preparadas;
- tokens CSRF nos formulários de login, solicitação, resposta a pendências e operações administrativas cobertas;
- regeneração do ID da sessão no login e cookies HttpOnly/SameSite; cookies Secure são ativados em HTTPS;
- validação de MIME dos documentos;
- limites de tamanho e quantidade de arquivos;
- downloads autenticados passando por PHP, sem cache privado e com `nosniff`;
- bloqueio Apache de acesso HTTP direto a `uploads/` por `uploads/.htaccess`;
- registros de histórico;
- validações de estado antes das ações administrativas.

### Limites que ainda precisam ser resolvidos para produção

- Configure HTTPS. O atributo Secure do cookie depende de o PHP receber a requisição como HTTPS.
- Troque a credencial administrativa padrão do `servicos.sql`; não publique a senha de instalação.
- Implemente limitação de tentativas de login e uma política de bloqueio/alerta.
- Defina permissões por perfil em todas as páginas administrativas. Atualmente, a tela do catálogo limita alterações a `admin` e `gestor`, mas não há uma matriz global.
- Configure e teste o envio real de recuperação por e-mail. Sem as variáveis da seção 3.4, a recuperação não envia link.
- Configure backup automático e teste de restauração.
- Defina retenção, auditoria de acesso e resposta a incidentes com os responsáveis municipais de proteção de dados.
- Em produção, prefira armazenar anexos fora da raiz pública; o `.htaccess` é uma barreira complementar, não substitui a configuração do servidor.
- Faça avaliação de segurança e revisão clínica/operacional antes de usar dados reais. Este manual não é certificação LGPD nem auditoria formal.

---

# 31. Estrutura do projeto

```text
exames/
│
├── index.php
├── login.php
├── login_admin.php
├── cadastro_paciente.php
├── recuperar_senha.php
├── redefinir_senha.php
├── acompanhamento_fila.php
├── logout.php
├── conexao.php
├── servicos.sql
│
├── admin/
│   ├── dashboard.php
│   ├── listagem_ordens.php
│   ├── catalogo_exames.php
│   ├── pendencias.php
│   ├── paciente_perfil.php
│   ├── acoes_pedido.php
│   └── documento_download.php
│
├── paciente/
│   ├── painel.php
│   ├── solicitar.php
│   ├── adicionar_documento.php
│   └── documento_download.php
│
├── helpers/
│   ├── auth.php
│   ├── seguranca.php
│   └── pedidos.php
│
├── scripts/
│   ├── criar_pacientes_teste.php
│   └── gerar_pedidos_teste.php
│
├── css/
│   └── exames.css
│
├── img/
│   └── brasao.jpg
│
└── uploads/
    └── exames/
```

---

# 32. Arquivos mais importantes para manutenção

## `conexao.php`

Centraliza a conexão PDO com o MySQL.

Se aparecer erro de conexão, verificar primeiro:

- servidor MySQL ativo;
- nome do banco;
- usuário;
- senha;
- host.

## `helpers/auth.php`

Responsável por:

- verificar sessão do paciente;
- verificar sessão do administrador;
- autenticar paciente;
- autenticar administrador.

## `helpers/seguranca.php`

Concentra funções relacionadas a segurança, sessão, CSRF e validações auxiliares.

## `helpers/pedidos.php`

Contém funções reutilizadas pelos pedidos, incluindo:

- geração de protocolo;
- cálculo da colocação na fila;
- registro de histórico;
- criação de notificação administrativa;
- formatação de tempo decorrido.

## `paciente/solicitar.php`

Cria novos pedidos e recebe os documentos iniciais.

## `paciente/adicionar_documento.php`

Processa a resposta do paciente a uma pendência.

É aqui que ocorre a transição:

```text
em_espera → aguardando_nova_analise
```

## `paciente/painel.php`

Apresenta os pedidos ao paciente e suas ações disponíveis.

## `admin/acoes_pedido.php`

É o principal controlador das mudanças de estado administrativas.

Qualquer nova ação administrativa deve ser implementada com validação do estado anterior, validação dos dados recebidos, registro no histórico e, quando necessário, transação.

## `admin/paciente_perfil.php`

Concentra a gestão do paciente e os formulários/ações disponíveis para cada status.

## `admin/pendencias.php`

Centraliza pedidos em espera e novas respostas.

## `admin/listagem_ordens.php`

Lista, pesquisa e filtra pedidos.

## `admin/documento_download.php` e `paciente/documento_download.php`

Controlam o acesso aos documentos armazenados. O download do paciente verifica `documento_id + paciente_id`; o administrador passa pela autenticação administrativa.

## `admin/validar_documento.php`

Recebe as ações `validar` e `rejeitar`, atualiza o status individual do documento e registra a decisão no histórico do pedido. A rejeição exige justificativa.

---

# 33. Banco de dados — visão para desenvolvedores

As principais tabelas são:

| Tabela | Responsabilidade |
|---|---|
| `usuarios_admin` | Usuários administrativos |
| `pacientes` | Cadastro único dos pacientes |
| `recuperacao_senhas` | Tokens de recuperação |
| `unidades` | Unidades de atendimento e suas chaves |
| `especialidades` | Especialidades do catálogo |
| `exames` | Procedimentos/exames/consultas e sua unidade |
| `dependencias_exames` | Estrutura para dependências futuras |
| `protocolo_controle` | Estrutura para controle anual de protocolos |
| `pedidos_exames` | Pedido principal e seu estado |
| `agendamentos` | Histórico/controle dos agendamentos |
| `documentos_exames` | Arquivos enviados e validação individual |
| `historico_pedidos` | Auditoria do pedido |
| `mensagens_exames` | Estrutura de mensagens |
| `notificacoes_admin` | Alertas internos para administradores |

### Relações principais

```text
pacientes
   │
   └── pedidos_exames
          │
          ├── exames ── especialidades
          │          └── unidades
          ├── unidade (snapshot do pedido)
          ├── agendamentos
          ├── documentos_exames
          ├── historico_pedidos
          └── notificacoes_admin
```

---

# 34. Regras importantes para futuros desenvolvedores

## Não usar complexidade para representar unidade

`complexidade` e `unidade_id` têm responsabilidades diferentes. A unidade é identificada por `unidade_id`; nunca volte a usar `baixa`, `media` ou `alta` como código de unidade.

## Não confiar na validação de senha do JavaScript

A interface mostra os requisitos em tempo real para orientar o usuário, mas a regra oficial fica em `validarSenhaForte()` no servidor. Toda nova tela de criação ou redefinição de senha deve reutilizar essa função.

## Documentos possuem estado próprio

Não use o status do pedido para inferir se um arquivo foi validado. Consulte `documentos_exames.status`. Um pedido pode estar em `aguardando_nova_analise` com vários documentos, cada um em um estado diferente.

## Nunca alterar status sem pensar no histórico

Toda mudança relevante deve usar `registrarHistorico()`.

Exemplo:

```php
registrarHistorico(
    $pdo,
    $pedidoId,
    $statusAnterior,
    'aguardando_vaga',
    'Aprovado na triagem e colocado na fila',
    null,
    'admin',
    $admin['id'],
    $admin['nome']
);
```

## Validar o status anterior

Não basta receber `pedido_id` e executar um `UPDATE`.

Exemplo implementado:

```php
if ($statusAntigo !== 'agendado') {
    throw new RuntimeException('Somente pedidos agendados podem voltar para a fila.');
}
```

Isso impede ações fora da ordem correta.

## Usar transações em operações compostas

Operações que alteram pedido + agendamento + histórico devem usar:

```php
$pdo->beginTransaction();
```

seguido de `commit()` ou `rollBack()`.

## Não criar dois agendamentos ativos

Ao agendar novamente, o sistema cancela os agendamentos anteriores que estejam `agendado` ou `confirmado` antes de criar o novo.

## Não confundir cancelamento com retorno à fila

São operações diferentes:

```text
Cancelar Pedido
→ CANCELADO
→ não retorna para a fila

Voltar para Fila
→ AGUARDANDO VAGA
→ cria nova entrada na fila
```

## Não devolver automaticamente uma resposta do paciente para a fila

A resposta muda para:

```text
aguardando_nova_analise
```

O administrador precisa analisar novamente.

---

# 35. Como adicionar uma nova ação administrativa

Ao implementar uma nova ação:

1. Criar o botão somente nos status em que a ação faz sentido.
2. Enviar `pedido_id`.
3. Enviar `csrf_token`.
4. Enviar um identificador de ação.
5. Validar o pedido no banco.
6. Validar o status atual.
7. Validar os dados recebidos.
8. Abrir transação quando houver múltiplas alterações.
9. Alterar o pedido/agendamento/documentos necessários.
10. Registrar histórico.
11. Criar notificação, quando aplicável.
12. Fazer `commit()`.
13. Redirecionar para o prontuário.

O ponto central para as ações atuais é:

```text
admin/acoes_pedido.php
```

---

# 36. Como adicionar uma nova funcionalidade do paciente

Ao adicionar uma função ao painel do paciente:

1. Verificar `exigirLoginPaciente()`.
2. Obter o ID do paciente pela sessão.
3. Buscar o pedido usando também `paciente_id`.
4. Nunca confiar somente no `pedido_id` recebido pelo navegador.
5. Validar CSRF nas operações POST.
6. Validar status permitido.
7. Validar arquivos/valores recebidos.
8. Registrar histórico.
9. Redirecionar com mensagem apropriada.

Isso evita que um paciente tente acessar um pedido pertencente a outra pessoa alterando o ID na URL ou no formulário.

---

# 37. Uploads — cuidados para desenvolvimento

Os arquivos são gravados fisicamente em:

```text
uploads/exames/<pedido_id>/
```

O arquivo `uploads/.htaccess` bloqueia acesso HTTP direto e desativa a listagem do diretório. O Apache precisa permitir essa regra (`AllowOverride` apropriado). Os envios usam transação no banco e removem arquivos já movidos quando o processamento falha, para evitar anexos órfãos. Em produção, prefira armazenar documentos fora da raiz pública do site e servi-los apenas pelos endpoints autenticados.

Não altere a validação para confiar apenas na extensão do arquivo.

O código atual também verifica MIME usando `finfo`.

Ao modificar upload, mantenha pelo menos:

- limite de tamanho;
- limite de quantidade;
- MIME permitido;
- nome físico controlado pelo sistema;
- título separado do nome físico;
- controle de autorização para download.

---

# 38. Protocolo

A função atual de geração de protocolo está em:

```text
helpers/pedidos.php
```

Ela gera um identificador baseado em dia/hora/segundos e caracteres aleatórios.

A coluna `protocolo` é `UNIQUE` no banco.

A tabela `protocolo_controle` existe para uma futura evolução do controle anual de numeração, mas a função de geração atualmente utilizada pelo sistema não usa essa tabela.

Portanto, **não assumir que o sistema atual já possui protocolo anual sequencial**. Se essa regra for necessária, deve ser implementada explicitamente e com controle de concorrência.

---

# 39. Funcionalidades previstas no banco, mas ainda não completas na interface atual

Algumas estruturas existem no banco para evolução futura:

### `dependencias_exames`

Permite relacionar um exame a outro exame obrigatório, mas o fluxo atual não aplica automaticamente essas dependências.

### `mensagens_exames`

Existe estrutura para mensagens/SMS/WhatsApp/sistema, porém o pacote atual não implementa um módulo completo de envio externo.

### `em_andamento` e `realizado`

Existem no `ENUM` de status, mas as ações administrativas atuais não possuem um fluxo completo para passar por esses estados.

### Perfis administrativos

O banco possui `admin`, `gestor` e `atendente`, mas ainda não existe uma matriz completa de permissões por perfil.

### `protocolo_controle`

Existe estrutura para controle anual, mas a geração atual do protocolo usa outra lógica.

Esses pontos devem ser tratados como **extensões futuras**, não como funcionalidades já concluídas.

---

# 40. Diagnóstico rápido de problemas

## Erro de conexão com banco

Verifique:

```text
XAMPP → MySQL → Start
```

Depois confira `conexao.php`.

## Tabela ou coluna inexistente

Verifique se o banco foi criado importando a versão atual de `servicos.sql`. Não é necessário executar migração.

## Erro envolvendo `aguardando_nova_analise`

Confira se o `ENUM` de `pedidos_exames.status` contém esse valor.

## Upload não funciona

Verifique:

- pasta `uploads/exames`;
- permissões de escrita;
- tamanho máximo do PHP (`upload_max_filesize` e `post_max_size`);
- formato do arquivo;
- limite de 10 MB por arquivo.

## Pedido não aparece na fila

Verifique o status:

```text
aguardando_vaga
```

Pedidos `em_espera`, `aguardando_analise`, `aguardando_nova_analise`, `agendado`, `finalizado` e `cancelado` não representam a fila de disponibilidade.

## Paciente respondeu, mas não voltou para a fila

Isso é intencional.

A resposta gera:

```text
aguardando_nova_analise
```

O administrador precisa analisar e aprovar novamente.

## Agendamento antigo continua aparecendo

Verifique a tabela `agendamentos`. O sistema considera como ativo o agendamento com status:

```text
agendado
confirmado
```

Agendamentos cancelados ou concluídos não são considerados ativos pelo painel do paciente.

---

# 41. Checklist de implantação

Antes de colocar o sistema em uso:

- [ ] Apache funcionando.
- [ ] MySQL funcionando.
- [ ] Banco `servicos` criado.
- [ ] `servicos.sql` importado no phpMyAdmin.
- [ ] `conexao.php` configurado.
- [ ] Login do paciente testado.
- [ ] Cadastro de paciente testado.
- [ ] Login administrativo testado.
- [ ] Solicitação de exame testada.
- [ ] Upload de documento testado.
- [ ] Pedido em espera testado.
- [ ] Resposta do paciente testada.
- [ ] Notificação administrativa testada.
- [ ] Aprovação para fila testada.
- [ ] Emergência testada.
- [ ] Agendamento testado.
- [ ] Alteração de agendamento testada.
- [ ] Retorno para fila testado.
- [ ] Cancelamento testado.
- [ ] Conclusão testada.
- [ ] Histórico conferido.
- [ ] Downloads de documentos conferidos.
- [ ] Backup configurado.
- [ ] Login administrativo com a conta padrão testado.
- [ ] Política de senha dos pacientes testada no cadastro e na recuperação.
- [ ] Validação e rejeição de documentos testadas.
- [ ] Login obrigatório da fila testado com dois pacientes diferentes.
- [ ] Filtro por unidade testado.
- [ ] HTTPS configurado em ambiente real.
- [ ] Recuperação por e-mail testada com `APP_PUBLIC_URL`, `APP_MAIL_FROM` e envio configurados.
- [ ] Acesso HTTP direto a um anexo bloqueado no Apache.
- [ ] Listagem administrativa testada com paginação e filtros.

---

# 42. Gerar dados de teste

Os utilitários em `scripts/` são somente para linha de comando; não os execute pelo navegador. Inicie o MySQL no XAMPP e, no PowerShell, entre na pasta do projeto:

```powershell
Set-Location 'C:\xampp\htdocs\exames'
```

Antes de gravar, confirme o banco conectado:

```powershell
& 'C:\xampp\php\php.exe' --% -r "require 'conexao.php'; echo $pdo->query('SELECT DATABASE()')->fetchColumn(), PHP_EOL;"
```

O resultado deve ser `servicos`. Execute primeiro o gerador de pacientes e depois o de pedidos:

```powershell
& 'C:\xampp\php\php.exe' '.\scripts\criar_pacientes_teste.php' --confirm
& 'C:\xampp\php\php.exe' '.\scripts\gerar_pedidos_teste.php' --confirm
```

No XAMPP Shell, navegue primeiro até a pasta `scripts` e use o PHP já configurado pelo XAMPP:

```text
cd C:\xampp\htdocs\exames\scripts
php criar_pacientes_teste.php --confirm
php gerar_pedidos_teste.php --confirm
```

O primeiro cria ou reaproveita até 50 contas `usuario.teste01@example.com` a `usuario.teste50@example.com`; a senha de contas novas é `Teste@123`. O segundo cria um pedido e um JPEG de teste para cada combinação paciente ativo + procedimento ativo ainda inexistente. Os scripts exigem `--confirm`, não apagam dados e podem ser reexecutados sem duplicar contas ou combinações paciente/procedimento.

Com 50 pacientes e 63 procedimentos ativos, a carga inicial pode criar até 3.150 pedidos e documentos, consumindo cerca de 82 MB. Use apenas em banco de teste; não misture registros sintéticos a dados clínicos reais. Este pacote não inclui uma rotina automática de limpeza.

# 43. Resultado dos testes desta versão

Foram exercitados login, solicitação web com anexo, permissões de download, rejeição e resposta documental, validação, fila, emergência, agendamento/alteração, retorno à fila, cancelamento, conclusão e paginação administrativa. Os testes modificaram estados de algumas contas sintéticas e criaram solicitações/documentos adicionais; isso é intencional. A recuperação por e-mail não foi testada porque depende de configuração real de envio. Esses testes não validam critérios médicos nem substituem homologação da equipe de saúde.

---

# 44. Resumo operacional

## Paciente

```text
Cadastro
→ Login
→ Solicitação
→ Documentos
→ Aguardando análise
→ Pendência, se houver
→ Resposta
→ Nova análise
→ Fila
→ Agendamento
→ Atendimento
→ Finalização
```

## Administrador

```text
Dashboard
→ Lista/Pendências
→ Prontuário
→ Análise
→ Aprovar / Esperar / Cancelar
→ Fila
→ Emergência, se necessário
→ Agendar
→ Alterar / Voltar para fila / Cancelar
→ Concluir
→ Histórico
```

## Regra fundamental

O **status atual** mostra onde o pedido está agora; o **histórico** mostra como ele chegou até ali.

Para qualquer alteração futura, preserve os dois conceitos.

---

# 45. Documentos deste pacote

- `README.md` — este manual completo.
- `README.txt` — referência rápida para instalação.
- `README_ALTERACOES_2026-09-28.txt` — histórico consolidado de alterações recentes.
- `servicos.sql` — criação completa de um banco novo, incluindo o administrador padrão desta versão.
- `scripts/criar_pacientes_teste.php` e `scripts/gerar_pedidos_teste.php` — geradores de dados sintéticos, somente CLI.

