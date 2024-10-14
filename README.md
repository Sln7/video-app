# Vídeo App

O **Vídeo App** é uma aplicação web que permite a exibição e gestão de vídeos, sendo possível adicionar através do identificador do YouTube ou realizar upload direto de arquivos de vídeo. A plataforma oferece uma listagem dos vídeos cadastrados, com a opção de visualizar os detalhes de cada um, além de fornecer o código de **incorporação** (embed) ou a URL para execução do vídeo via **HLS** (m3u8).

## 📽️ Funcionalidades

- **Cadastro de vídeos**: Insira vídeos via YouTube ou faça upload de arquivos.
- **Visualização detalhada**: Acesse informações detalhadas de cada vídeo.
- **Compartilhamento**: Obtenha o código de incorporação ou o link HLS para reprodução externa.
- **Sistema de Likes**: Adicione ou remova likes dos vídeos.

- **Conversão de vídeos em HLS**: Ao efetuar o upload de um arquivo de vídeo, o sistema realiza a conversão para disponibilização via HLS. Todo o processo é realizado em segundo plano.

### 📦 Requisitos
- **Docker**
- **Docker Compose**

## 🚀 Tecnologias Utilizadas

- **Laravel 11.9**
- **Laravel Horizon**
- **PostgreSQL**
- **Docker**
- **PHP 8.3**

## 🛠️ Como Executar

### 1. Clonando o Repositório

Clone este repositório em sua máquina local:
```bash
git clone https://github.com/seu-usuario/repositorio.git
cd repositorio
```

### 2. Configuração do Ambiente

Copie o conteúdo do arquivo `.env.example` para um novo arquivo `.env`:
```bash
cp .env.example .env
```

### 3. Instale as Dependências

Utilize o Docker para instalar as dependências com o Composer:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Inicialize os Containers

Suba os containers da aplicação:
```bash
./vendor/bin/sail up -d
```

### 5. Gere a Chave da Aplicação

Crie uma nova chave de aplicação para o Laravel:
```bash
./vendor/bin/sail artisan key:generate
```

### 6. Execute as Migrations

Rode as migrations para configurar o banco de dados:
```bash
./vendor/bin/sail artisan migrate
```

### 7. Configuração do MinIO para Armazenamento de Vídeos

Acesse o MinIO para criar um bucket de armazenamento:

- Acesse o painel de administração:
  ```
  http://localhost:8900
  ```

  - Use as credenciais padrão:
    - **Access Key**: `minio`
    - **Secret Key**: `minio123`

  - Crie um bucket chamado `videos`:
    ```
    http://localhost:8900/browser/add-bucket
    ```

  - Crie novas credenciais para a aplicação:
    ```
    http://localhost:8900/access-keys/new-account
    ```

  - Copie as novas credenciais geradas para o arquivo `.env`:
    ```bash
    AWS_ACCESS_KEY_ID=SUAS_ACCESS_KEY
    AWS_SECRET_ACCESS_KEY=SEU_SECRET_KEY
    AWS_ENDPOINT=http://minio:9000
    ```

### 8. Configuração da API do YouTube

Para utilizar a API do YouTube, siga os passos abaixo:

1. Acesse o [Google Cloud Console](https://console.developers.google.com/).
2. Crie um novo projeto.
3. Ative a **YouTube Data API**.
4. Gere uma chave de API e adicione-a ao arquivo `.env`:
   ```bash
   YOUTUBE_API_KEY=SUA_YOUTUBE_API_KEY
   ```

## 📚 Endpoints da API

Todos os endpoints estão disponíveis sob o prefixo `/api`. Aqui está uma visão geral das rotas principais:

### Autenticação
- **POST** `/api/login` - Login de usuários
- **POST** `/api/logout` - Logout de usuários
- **POST** `/api/register` - Cadastro de novos usuários

### Vídeos
- **GET** `/api/videos` - Lista todos os vídeos cadastrados
- **GET** `/api/videos/{id}` - Detalhes de um vídeo específico
- **POST** `/api/videos` - Cadastra um novo vídeo
- **PATCH** `/api/videos/{id}/like` - Adiciona ou remove um like em um vídeo

##### 📝 Obtenha o arquivo [openapi.yml](./openapi.yaml)


## 🧪 Executando Testes

Para rodar a suíte de testes, utilize o comando abaixo:
```bash
./vendor/bin/sail test
```
