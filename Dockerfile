FROM php:8.4.13-fpm-trixie

# Debian Packages
RUN apt-get update && apt-get install -y unzip curl git procps  lsb-release ca-certificates apt-transport-https gnupg \
    libicu-dev \
    && docker-php-ext-install pdo pdo_mysql intl


# Composer
ARG COMPOSER_SHA
RUN curl -sS https://composer.github.io/installer.sig -o composer.sig \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === trim(file_get_contents('composer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); }" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer



# Symfony cli
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Add vendor to path 
ENV PATH="${PATH}:/acses_api/vendor/bin"
WORKDIR /bd_project_api