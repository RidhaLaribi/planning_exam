FROM php:8.2-cli

# System deps
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    curl \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql

# Set workdir
WORKDIR /app

# Copy app
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Frontend (if using Vite / Mix)
RUN npm install && npm run build || true

# Expose Railway port
EXPOSE 8080

# Start app
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
