FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]
