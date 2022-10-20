FROM php:8.0.13-fpm-bullseye
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update
RUN apt-get install -y --no-install-recommends \
    mariadb-client \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libcurl4-gnutls-dev \
    libzip-dev \
    libc-client-dev \
    libkrb5-dev \
    libssl-dev \
    libsasl2-dev \
    libssl-doc \
    libmagickwand-dev \
    zlib1g-dev \
    libgmp-dev

# PHP packages
RUN docker-php-ext-configure gd \
    && docker-php-ext-install gd

RUN docker-php-ext-configure gmp \
    && docker-php-ext-install gmp

RUN docker-php-ext-configure opcache \
    && docker-php-ext-install opcache

RUN docker-php-ext-configure mysqli \
    && docker-php-ext-install mysqli

RUN docker-php-ext-configure pdo_mysql \
    && docker-php-ext-install pdo_mysql

RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip

RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

# More functions
RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN pecl install imagick \
    && docker-php-ext-enable imagick

RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer

RUN rm -rf /var/cache/debconf/*-old
RUN rm -rf /var/lib/apt/lists/*
RUN rm -rf /usr/share/locale/* /usr/share/doc/* /usr/include/* /usr/share/man/*

RUN apt-get autoclean -y
RUN apt-get autoremove -y

COPY php.ini /usr/local/etc/php/
COPY fpm.conf /usr/local/etc/php-fpm.d/fpm.conf
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

USER www-data
