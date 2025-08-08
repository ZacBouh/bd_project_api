FROM php:8.3.17-fpm-bookworm

# Utilities
RUN apt-get update && apt-get install -y unzip curl git procps  lsb-release ca-certificates apt-transport-https software-properties-common gnupg && \
    docker-php-ext-install pdo pdo_mysql

# Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer
# Symfony cli
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Add vendor to path 
ENV PATH="${PATH}:/acses_api/vendor/bin"
WORKDIR /bd_project_api