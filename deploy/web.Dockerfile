# Production web server: builds the SPA, then Caddy serves it with https and forwards /api and /storage to the API.
# Build context: the repository root (see docker-compose.prod.yml).
FROM node:22-alpine AS build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY frontend/ ./
ARG VITE_SENTRY_DSN=""
ARG VITE_PRIVACY_CONTROLLER=""
ARG VITE_PRIVACY_DOCUMENT=""
ARG VITE_PRIVACY_EMAIL=""
ARG VITE_GOOGLE_LOGIN=""
ENV VITE_API_URL=/api \
    VITE_SENTRY_DSN=$VITE_SENTRY_DSN \
    VITE_PRIVACY_CONTROLLER=$VITE_PRIVACY_CONTROLLER \
    VITE_PRIVACY_DOCUMENT=$VITE_PRIVACY_DOCUMENT \
    VITE_PRIVACY_EMAIL=$VITE_PRIVACY_EMAIL \
    VITE_GOOGLE_LOGIN=$VITE_GOOGLE_LOGIN
RUN npm run build

FROM caddy:2-alpine
COPY deploy/Caddyfile /etc/caddy/Caddyfile
COPY --from=build /app/dist /srv
