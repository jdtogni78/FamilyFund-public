# syntax=docker/dockerfile:1
FROM php:8.4-cli-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    supervisor \
    golang-go \
    && rm -rf /var/lib/apt/lists/*

# Install wkhtmltopdf
RUN ARCH=$(dpkg --print-architecture) \
    && curl -sLO https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_${ARCH}.deb \
    && apt-get update && apt-get install -y ./wkhtmltox_0.12.6.1-3.bookworm_${ARCH}.deb \
    && rm wkhtmltox_0.12.6.1-3.bookworm_${ARCH}.deb \
    && rm -rf /var/lib/apt/lists/*

# Install mhsendmail for MailHog integration
RUN go install github.com/mailhog/mhsendmail@latest \
    && cp ~/go/bin/mhsendmail /usr/local/bin/ \
    && rm -rf ~/go \
    && apt-get purge -y golang-go \
    && apt-get autoremove -y

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Install pcov for code coverage (faster than Xdebug)
RUN pecl install pcov && docker-php-ext-enable pcov

# Configure PHP for development
RUN echo 'sendmail_path = "/usr/local/bin/mhsendmail --smtp-addr=mail:1025"' >> /usr/local/etc/php/conf.d/sendmail.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/custom.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy supervisor config
COPY supervisor.conf /etc/supervisor/conf.d/

# Expose Laravel dev server port
EXPOSE 8000

# Start Laravel dev server (supervisor can be started separately if needed)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
