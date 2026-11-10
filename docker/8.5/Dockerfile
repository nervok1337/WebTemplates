FROM ubuntu:24.04

ARG WWWGROUP=1000
ARG NODE_VERSION=24

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER=sail

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl git gosu gnupg libcap2-bin supervisor unzip \
    && curl -fsSL https://packages.sury.org/debsuryorg-archive-keyring.deb -o /tmp/php-keyring.deb \
    && dpkg -i /tmp/php-keyring.deb \
    && echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ noble main" > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.5-cli php8.5-mysql php8.5-sqlite3 php8.5-mbstring php8.5-xml \
        php8.5-curl php8.5-zip php8.5-bcmath php8.5-intl php8.5-redis \
    && rm -rf /var/lib/apt/lists/* /tmp/php-keyring.deb

RUN curl -fsSL https://getcomposer.org/composer-stable.phar -o /usr/bin/composer \
    && chmod +x /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x -o /tmp/setup-node.sh \
    && bash /tmp/setup-node.sh \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/* /tmp/setup-node.sh

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.5 \
    && userdel -r ubuntu \
    && groupadd --force -g "$WWWGROUP" sail \
    && useradd -ms /bin/bash --no-user-group -g "$WWWGROUP" -u 1337 sail \
    && git config --system --add safe.directory /var/www/html

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.5/cli/conf.d/99-sail.ini

RUN chmod +x /usr/local/bin/start-container

EXPOSE 80

ENTRYPOINT ["start-container"]
