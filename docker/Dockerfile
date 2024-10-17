FROM php:8.2-apache

#Enable apache modules
RUN a2enmod rewrite

#Install PHP Dependencies
RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		zip \
		libzip-dev \
		libmagickwand-dev \
		libfreetype6-dev \
		zlib1g-dev \
		libpng-dev \
		libjpeg-dev \
	&& docker-php-ext-configure gd \
		--with-freetype=/usr/include/ \
		--with-jpeg=/usr/include/ \
	&& pecl install \
		imagick \
		xdebug \
	&& docker-php-ext-install -j$(nproc) \
		zip \
		mysqli \
		gd \
	&& docker-php-ext-enable \
		mysqli \
		imagick \
		gd \
		zip \
		xdebug \
	&& apt-get purge -y \
		libzip-dev \
		libmagickwand-dev \
		libfreetype6-dev \
		zlib1g-dev \
		libpng-dev \
		libjpeg-dev
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

ARG APACHE_CONFIG="apache.conf"
ADD ${APACHE_CONFIG} /etc/apache2/sites-enabled/000-default.conf

ARG INI_CONFIG="ini"
ADD ${INI_CONFIG} /usr/local/etc/php/conf.d/
