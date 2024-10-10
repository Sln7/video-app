
# DynFlow - Gestão de clinicas

Este é um projeto de frontend para o sistema de gestão de clínicas DynFlow Odonto, tem como principal objetivo fornecer as interfaces necessárias para os gestores e funcionários da clínica gerenciarem o estabelecimento.

## 🛠️ Tecnologias Utilizadas

O projeto foi desenvolvido utilizando as seguintes tecnologias:

- **[Vue.js](https://vuejs.org/)** - Um framework JavaScript progressivo para a construção de interfaces de usuário.
- **[Vuetify](https://vuetifyjs.com/)** - Um framework de componentes UI para Vue.js, baseado no Material Design.
- **[Vue Router](https://router.vuejs.org/)** - Biblioteca de roteamento para Vue.js.
- **[Pinia](https://pinia.vuejs.org/)** - Um sistema de gerenciamento de estado para Vue.js, substituindo o Vuex.
- **[Vite](https://vitejs.dev/)** - Ferramenta de build para desenvolvimento rápido de frontend.
- **[V-Mask](https://github.com/probil/v-mask)** - Biblioteca para aplicação de máscaras de input em campos de formulário.

## :cog: Funcionalidades e Requisitos

O documento completo pode ser encontrado em [Requisitos-Dynflow.md](Requisitos-Dynflow.md).

Exemplo de estrutura da API no arquivo OpenAPI [openapi.yaml](openapi.yaml).

## 📂 Estrutura do Projeto

O projeto está estruturado da seguinte forma:

```
/src
├── /assets               # Arquivos de imagens, fontes, etc.
├── /components           # Componentes reutilizáveis
│   ├── Layout.vue        # Componente de layout principal
│   ├── PatientForm.vue   # Formulário para adicionar/editar paciente
│   └── Sidebar.vue       # Menu lateral da aplicação
├── /views                # Páginas do sistema
│   ├── ManageCustomers.vue  # Tela de gerenciamento de pacientes
│   └── Dashboard.vue        # Tela de dashboard (em construção)
├── /router
│   └── index.js          # Configuração de rotas da aplicação
├── /store                # Gerenciamento de estado utilizando Pinia
│   ├── calendarStore.js  # Estado de calendário (em construção)
│   ├── dashboardStore.js # Estado do dashboard (em construção)
│   └── patients.json     # Dados de pacientes para uso local
├── /services             # Serviços de API (em construção)
├── App.vue               # Componente raiz da aplicação
├── main.js               # Arquivo de entrada principal da aplicação
└── style.css             # Estilos globais
```

## 📄 Padrões de Código e Estilo

O projeto segue os seguintes padrões de código e boas práticas:

- **Vue Single File Components (SFC)**: Todos os componentes seguem a estrutura `.vue`, separando template, script e estilos.
- **Modularização**: O projeto é modularizado em `components`, `views`, e `services`, facilitando a manutenção e expansão.
- **Pinia para gerenciamento de estado**: Utilizado para estados globais da aplicação, substituindo o Vuex.
- **Vuetify como framework de UI**: Utilizando componentes padronizados baseados em Material Design.

## 🖥️ Telas e Componentes Implementados

1. **Gerenciar Pacientes** (`ManageCustomers.vue`):
   - Listagem de pacientes.
   - Busca de pacientes.
   - Botão para adicionar novo paciente.
   - Ações de editar e excluir pacientes diretamente da lista.
   - Modal para adicionar ou editar um paciente.

2. **Formulário de Paciente** (`PatientForm.vue`):
   - Formulário que inclui campos como nome, CPF, RG, data de nascimento, email, telefone, entre outros.
   - Aplicação de máscaras para os campos de CPF, telefone, etc.
   - Validação de campos obrigatórios.

## 📝 Como Rodar o Projeto

### Pré-requisitos

- **Node.js**: Certifique-se de que você tem o [Node.js](https://nodejs.org/en/) instalado na sua máquina.
- **NPM ou Yarn**: O gerenciador de pacotes [NPM](https://www.npmjs.com/) é instalado automaticamente com o Node.js, ou você pode utilizar o [Yarn](https://yarnpkg.com/) como alternativa.

### Passos para Rodar

1. Clone o repositório:
   ```bash
   git clone https://github.com/seuusuario/nome-do-repositorio.git
   ```

2. Instale as dependências:
   ```bash
   cd nome-do-repositorio
   npm install
   ```

3. Inicie o servidor de desenvolvimento:
   ```bash
   npm run dev
   ```

4. Acesse a aplicação no navegador:
   ```
   http://localhost:5173
   ```

## 📈 Melhorias Futuras

- Implementar autenticação de usuário.
- Finalizar a página de **Dashboard** com gráficos e métricas gerais.
- Implementar integração com APIs para gerenciamento de pacientes.
- Melhorar a acessibilidade e responsividade do sistema.
- Criar mais testes unitários e end-to-end.

## 📝 Licença
 Este projeto é privado e não possui licença de uso.
