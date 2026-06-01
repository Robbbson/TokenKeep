FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_sqlite

WORKDIR /var/www/html

COPY . .

# Setting permissions for SQLite database and log file
RUN touch database.sqlite tokenkeep.log \
    && chmod 666 database.sqlite tokenkeep.log

EXPOSE 8000

# Start the built-in PHP server with routing to the public folder
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]