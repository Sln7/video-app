# Kids Hybrid Media App

Plataforma Laravel 13 para gerenciamento e streaming de **vídeos e áudios**. Suporta YouTube, upload de vídeo com conversão HLS, upload de áudio local com extração automática de tags ID3, e um player React PWA estilo quiosque otimizado para tablets.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Autenticação | Laravel Sanctum (tokens Bearer) |
| Filas | Laravel Horizon + Redis |
| Armazenamento | S3 / MinIO |
| Banco de dados | PostgreSQL 17 |
| Frontend | React 19, Vite 6, react-player, Tailwind CSS |
| Containers | Docker (Laravel Sail) + Caddy (HTTPS local) |

---

## Ambiente local

### Requisitos

- Docker e Docker Compose
- WSL2 (Windows) ou Linux/macOS

### 1. Clone e inicialize

```bash
git clone <repo-url>
cd video-app
./setup.sh
```

O `setup.sh` faz tudo automaticamente:

- Copia `.env.example` → `.env` com `WWWUSER`/`WWWGROUP` corretos
- Instala dependências PHP via Docker (sem precisar de PHP local)
- Sobe o Sail com todos os serviços (Laravel, Caddy, PostgreSQL, Redis, MinIO, Horizon)
- Gera a chave da aplicação
- Publica as configurações do Sanctum
- Executa as migrations
- Instala os pacotes npm

### 2. Rode o front-end em modo desenvolvimento

```bash
./vendor/bin/sail npm run dev
```

Mantenha esse processo rodando enquanto desenvolve — ele ativa o HMR (hot reload).

### 3. Configure o HTTPS local (uma vez)

O projeto usa **Caddy** como proxy reverso para expor tudo via `https://localhost`. Após o primeiro `sail up`, execute:

```bash
./trust-https.sh
```

O script exporta o certificado CA interno do Caddy e exibe o comando correto para instalar no seu sistema.

**WSL2 / Windows (Chrome, Edge)** — rode no PowerShell como Administrador:

```powershell
certutil.exe -addstore -f "ROOT" "C:\Users\...\caddy-root.crt"
```

> Após instalar o certificado, reinicie o browser.

### 4. Acesse a aplicação

| URL | O que é |
|---|---|
| `https://localhost` | Aplicação React (player + admin) |
| `https://localhost/horizon` | Painel de filas |
| `http://localhost:8900` | Console MinIO (armazenamento) |

### 5. Configure o MinIO

Acesse `http://localhost:8900` com as credenciais padrão:

- **Usuário:** `sail`
- **Senha:** `password`

Passos:
1. Crie um bucket (ex.: `media`)
2. Gere um par de chaves de acesso
3. Adicione no `.env`:

```env
AWS_ACCESS_KEY_ID=sua-chave
AWS_SECRET_ACCESS_KEY=seu-segredo
AWS_BUCKET=media
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000
```

### 6. YouTube (opcional)

Só necessário para cadastrar mídias do tipo `youtube`.

1. Acesse o [Google Cloud Console](https://console.cloud.google.com/)
2. Ative a **YouTube Data API v3** e gere uma chave
3. Adicione no `.env`:

```env
YOUTUBE_API_KEY=sua-chave
```

---

## Usando o Frontend React

A aplicação SPA está acessível em `https://localhost`.

### Rotas disponíveis

| Rota | Descrição |
|---|---|
| `/login` | Login com email e senha |
| `/` | Listagem de mídias (público) |
| `/player/:id` | Player de uma mídia específica (público) |
| `/upload` | Cadastro de nova mídia (requer login) |

### Login padrão (seed)

```
Email:  admin@media.test
Senha:  password
```

### Popular o banco com dados de exemplo

```bash
./vendor/bin/sail artisan db:seed
```

Cria:
- 1 usuário admin (`admin@media.test` / `password`)
- 4 usuários aleatórios
- 5 vídeos do YouTube, 3 HLS, 4 áudios locais e 1 item não processado
- Favoritos aleatórios entre os usuários

---

## Referência da API

Todos os endpoints usam o prefixo `/api`. Rotas protegidas exigem `Authorization: Bearer <token>`.

### Autenticação

| Método | Endpoint | Auth | Descrição |
|---|---|---|---|
| POST | `/api/register` | — | Cadastro de novo usuário |
| POST | `/api/login` | — | Login, retorna token Bearer |
| POST | `/api/logout` | Obrigatório | Revoga o token atual |

#### Login

```http
POST /api/login
Content-Type: application/json

{
    "email": "admin@media.test",
    "password": "password"
}
```

Resposta:

```json
{
    "message": "User successfully logged in",
    "token": "1|abc123..."
}
```

---

### Mídia

| Método | Endpoint | Auth | Descrição |
|---|---|---|---|
| GET | `/api/media` | — | Lista mídias processadas (paginado) |
| GET | `/api/media/{id}` | — | Detalhes da mídia |
| POST | `/api/media` | Obrigatório | Cadastra nova mídia |
| PATCH | `/api/media/{id}/favorite` | Obrigatório | Adiciona/remove dos favoritos |

#### Parâmetros de listagem (`GET /api/media`)

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `search` | string | Filtra por título, descrição ou artista |
| `media_type` | `audio` \| `video` | Filtra por tipo |
| `order_by` | `title` \| `views` \| `created_at` | Campo de ordenação (padrão: `created_at`) |
| `order` | `asc` \| `desc` | Direção (padrão: `desc`) |
| `per_page` | inteiro | Itens por página (padrão: 10, máx: 100) |

#### Cadastrar vídeo do YouTube

```http
POST /api/media
Authorization: Bearer <token>
Content-Type: application/json

{
    "media_type": "video",
    "source": "youtube",
    "video_id": "dQw4w9WgXcQ"
}
```

#### Cadastrar vídeo HLS (upload)

```http
POST /api/media
Authorization: Bearer <token>
Content-Type: multipart/form-data

media_type = video
source     = hls
title      = Meu Vídeo
file       = <mp4/mov, máx 200MB>
thumbnail  = <jpeg/png, máx 2MB>  (opcional)
```

O vídeo é convertido para HLS de forma assíncrona. O campo `processed` fica `false` até o job concluir.

#### Cadastrar áudio local (upload)

```http
POST /api/media
Authorization: Bearer <token>
Content-Type: multipart/form-data

media_type = audio
source     = local_audio
file       = <mp3/wav/flac/ogg/aac, máx 50MB>
thumbnail  = <jpeg/png, máx 2MB>  (opcional — substitui a capa embutida)
```

Tags ID3 (`title`, `artist`, `album`, `duration`) e capa de álbum são extraídas automaticamente.

---

## Fontes de mídia suportadas

| `source` | `media_type` | Descrição |
|---|---|---|
| `youtube` | `video` | Metadados via YouTube Data API v3 |
| `hls` | `video` | Upload MP4/MOV → conversão assíncrona FFmpeg → HLS |
| `local_audio` | `audio` | Upload MP3/WAV/FLAC/OGG/AAC → extração de tags ID3 |
| `soundcloud` | `audio` | Planejado — não implementado |

---

## Testes

```bash
./vendor/bin/sail test
```

---

## Filas e Horizon

Conversão HLS, incremento de views e contagem de favoritos são processados de forma assíncrona via Redis. Monitore em:

```
https://localhost/horizon
```

Os containers `horizon` e `queue-worker` sobem automaticamente com `sail up`.
