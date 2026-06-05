FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    librabbitmq-dev \
 && docker-php-ext-install sockets \
 && pecl install amqp \
 && docker-php-ext-enable amqp \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY src/ /app/

CMD ["php", "/app/consumer.php"]
