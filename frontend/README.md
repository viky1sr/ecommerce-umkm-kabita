# Frontend (Vue 3 + Vite)

## Setup

```sh
npm install
cp .env.example .env
```

## Environment

- `VITE_API_BASE_URL`: Backend API base URL (default: `http://localhost:8000/api/v1`)
- `VITE_API_TIMEOUT_MS`: Request timeout in milliseconds
- `VITE_API_WITH_CREDENTIALS`: Set `true` only when cookie-based auth is enabled

## Run

```sh
npm run dev
```

## Quality checks

```sh
npm run lint
npm run build
```
