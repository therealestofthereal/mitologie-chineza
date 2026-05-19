FROM dunglas/frankenphp:php8.2.31-bookworm

RUN apt-get update && apt-get install -y \
    php8.2-mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080"]
