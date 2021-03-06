FROM php:7.0-fpm
MAINTAINER Kristoph Junge <kristoph.junge@gmail.com>
MAINTAINER Matteo Basso <matteo.basso@gmail.com>

# Change UID and GID of www-data user to match host privileges
ARG MEDIAWIKI_USER_UID=999
ARG MEDIAWIKI_USER_GID=999
RUN usermod -u $MEDIAWIKI_USER_UID www-data && \
    groupmod -g $MEDIAWIKI_USER_GID www-data

# Utilities
RUN apt-get update && \
    apt-get -y install apt-transport-https git curl wget --no-install-recommends && \
    rm -r /var/lib/apt/lists/*

# MySQL PHP extension
RUN docker-php-ext-install mysqli

# Pear mail
RUN apt-get update && \
    apt-get install -y php-pear --no-install-recommends && \
    pear install mail Net_SMTP && \
    rm -r /var/lib/apt/lists/*

# Imagick with PHP extension
RUN apt-get update && apt-get install -y imagemagick libmagickwand-6.q16-dev --no-install-recommends && \
    ln -s /usr/lib/x86_64-linux-gnu/ImageMagick-6.8.9/bin-Q16/MagickWand-config /usr/bin/ && \
    pecl install imagick-3.4.0RC6 && \
    echo "extension=imagick.so" > /usr/local/etc/php/conf.d/ext-imagick.ini && \
    rm -rf /var/lib/apt/lists/*

# Intl PHP extension
RUN apt-get update && apt-get install -y libicu-dev g++ --no-install-recommends && \
    docker-php-ext-install intl && \
    apt-get install -y --auto-remove libicu52 g++ && \
    rm -rf /var/lib/apt/lists/*

# APC PHP extension
RUN pecl install apcu && \
    pecl install apcu_bc-1.0.3 && \
    docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini && \
    docker-php-ext-enable apc --ini-name 20-docker-php-ext-apc.ini

# Nginx
RUN apt-key adv --keyserver hkp://pgp.mit.edu:80 --recv-keys 573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62 && \
    echo "deb http://nginx.org/packages/mainline/debian/ jessie nginx" >> /etc/apt/sources.list
ARG NGINX_VERSION=1.9.9-1~jessie
RUN apt-get update && \
    apt-get -y install ca-certificates nginx=${NGINX_VERSION} --no-install-recommends && \
    rm -r /var/lib/apt/lists/*
COPY config/nginx/* /etc/nginx/

# PHP-FPM
COPY config/php-fpm/php-fpm.conf /usr/local/etc/
COPY config/php-fpm/php.ini /usr/local/etc/php/
RUN mkdir -p /var/run/php7-fpm/ && \
    chown www-data:www-data /var/run/php7-fpm/

# Supervisor
RUN apt-get update && \
    apt-get install -y supervisor --no-install-recommends && \
    rm -r /var/lib/apt/lists/*
COPY config/supervisor/supervisord.conf /etc/supervisor/conf.d/
COPY config/supervisor/kill_supervisor.py /usr/bin/

# NodeJS
RUN curl -sL https://deb.nodesource.com/setup_4.x | bash - && \
    apt-get install -y nodejs --no-install-recommends

# Parsoid

ARG PARSOID_USER_UID=998
ARG PARSOID_USER_GID=998
RUN useradd parsoid -u $PARSOID_USER_UID --no-create-home --home-dir /usr/lib/parsoid --shell /usr/sbin/nologin && \
    groupmod -g $PARSOID_USER_GID parsoid
RUN apt-key advanced --keyserver pgp.mit.edu --recv-keys 90E9F83F22250DD7 && \
    echo "deb https://releases.wikimedia.org/debian jessie-mediawiki main" > /etc/apt/sources.list.d/parsoid.list && \
    apt-get update && \
    apt-get -y install parsoid --no-install-recommends
COPY config/parsoid/config.yaml /usr/lib/parsoid/src/config.yaml
ENV NODE_PATH /usr/lib/parsoid/node_modules:/usr/lib/parsoid/src


# MediaWiki
ARG MEDIAWIKI_VERSION_MAJOR=1.29
ARG MEDIAWIKI_VERSION=1.29.0

RUN curl -s -o /tmp/keys.txt https://www.mediawiki.org/keys/keys.txt && \
    curl -s -o /tmp/mediawiki.tar.gz https://releases.wikimedia.org/mediawiki/$MEDIAWIKI_VERSION_MAJOR/mediawiki-$MEDIAWIKI_VERSION.tar.gz && \
    curl -s -o /tmp/mediawiki.tar.gz.sig https://releases.wikimedia.org/mediawiki/$MEDIAWIKI_VERSION_MAJOR/mediawiki-$MEDIAWIKI_VERSION.tar.gz.sig && \
    gpg --import /tmp/keys.txt && \
    gpg --list-keys --fingerprint --with-colons | sed -E -n -e 's/^fpr:::::::::([0-9A-F]+):$/\1:6:/p' | gpg --import-ownertrust && \
    gpg --verify /tmp/mediawiki.tar.gz.sig /tmp/mediawiki.tar.gz && \
    mkdir -p /var/www/mediawiki /data /images && \
    tar -xzf /tmp/mediawiki.tar.gz -C /tmp && \
    mv /tmp/mediawiki-$MEDIAWIKI_VERSION/* /var/www/mediawiki && \
    rm -rf /tmp/mediawiki.tar.gz /tmp/mediawiki-$MEDIAWIKI_VERSION/ /tmp/keys.txt && \
    rm -rf /var/www/mediawiki/images && \
    ln -s /images /var/www/mediawiki/images
COPY config/mediawiki/* /var/www/mediawiki/

# VisualEditor extension
ARG EXTENSION_VISUALEDITOR_VERSION=REL1_29-ef45039
RUN curl -s -o /tmp/extension-visualeditor.tar.gz https://extdist.wmflabs.org/dist/extensions/VisualEditor-$EXTENSION_VISUALEDITOR_VERSION.tar.gz && \
    tar -xzf /tmp/extension-visualeditor.tar.gz -C /var/www/mediawiki/extensions && \
    rm /tmp/extension-visualeditor.tar.gz

# User merge and delete extension
ARG EXTENSION_USERMERGE_VERSION=REL1_29-de5f67d
RUN curl -s -o /tmp/extension-usermerge.tar.gz https://extdist.wmflabs.org/dist/extensions/UserMerge-$EXTENSION_USERMERGE_VERSION.tar.gz && \
    tar -xzf /tmp/extension-usermerge.tar.gz -C /var/www/mediawiki/extensions && \
    rm /tmp/extension-usermerge.tar.gz

#Input Box extension
ARG EXTENSION_INPUTBOX_VERSION=REL1_29-b35129f
RUN curl -s -o /tmp/extension-inputbox.tar.gz https://extdist.wmflabs.org/dist/extensions/InputBox-$EXTENSION_INPUTBOX_VERSION.tar.gz && \
    tar -xzf /tmp/extension-inputbox.tar.gz -C /var/www/mediawiki/extensions && \
    rm /tmp/extension-inputbox.tar.gz

#Boilerplate extension
ARG EXTENSION_BOILERPLATE_VERSION=REL1_29-49317c0
RUN curl -s -o /tmp/extension-boilerplate.tar.gz https://extdist.wmflabs.org/dist/extensions/BoilerPlate-$EXTENSION_BOILERPLATE_VERSION.tar.gz && \
    tar -xzf /tmp/extension-boilerplate.tar.gz -C /var/www/mediawiki/extensions && \
    rm /tmp/extension-boilerplate.tar.gz

#Newest Pages extension
ARG EXTENSION_NEWESTPAGES_VERSION=REL1_29-75aa49b
RUN curl -s -o /tmp/extension-newestpages.tar.gz https://extdist.wmflabs.org/dist/extensions/NewestPages-$EXTENSION_NEWESTPAGES_VERSION.tar.gz && \
    tar -xzf /tmp/extension-newestpages.tar.gz -C /var/www/mediawiki/extensions && \
    rm /tmp/extension-newestpages.tar.gz

#Parser functions
ARG EXTENSION_PARSERFUNCTIONS_VERSION=REL1_29-ec53ace
RUN curl -s -o /tmp/extension-parserfunctions.tar.gz https://extdist.wmflabs.org/dist/extensions/ParserFunctions-$EXTENSION_PARSERFUNCTIONS_VERSION.tar.gz && \
    tar -xzf /tmp/extension-parserfunctions.tar.gz -C /var/www/mediawiki/extensions && \
    rm /tmp/extension-parserfunctions.tar.gz

#Tweeki skin
RUN cd /var/www/mediawiki/skins && \
    git clone https://github.com/thaider/Tweeki

#Set root to wiki files
RUN chown -R root:root /var/www/mediawiki && \
        chown -R www-data:www-data /data /images
# Set work dir
WORKDIR /var/www/mediawiki

# Copy docker entry point script
COPY docker-entrypoint.sh /docker-entrypoint.sh

# Copy install and update script
RUN mkdir /script
COPY script/* /script/

# General setup
VOLUME ["/var/cache/nginx", "/data", "/images"]
EXPOSE 80 443
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD []
