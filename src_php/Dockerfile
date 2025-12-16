FROM php:8.3-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN mkdir -p /app/data /app/log && \
    chown -R www-data:www-data /app/data /app/log

# Expose port if needed (e.g. for built-in server or future web server)
EXPOSE 8080

# Command to run (can be overridden)
CMD ["php", "src/index.php"]
