
FROM php:7.4-fpm

COPY composer.lock composer.json /var/www/
COPY apex /usr/bin/
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libfreetype6-dev \
    locales \
    libzip-dev \
    zip \ 
    vim \
    nano \
    unzip \
    git \
    curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install mysqli tokenizer gd
#RUN docker-php-ext-configure zip --with-libzip
#RUN docker-php-ext-install zip

RUN pecl install -o -f zip && \ 
    rm -rf /tmp/pear && \
    docker-php-ext-enable zip

# Install php-redis
RUN pecl install -o -f redis && \ 
    rm -rf /tmp/pear && \
    docker-php-ext-enable redis

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add user
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]


