FROM php:8.3-apache

# System dependencies for PHP extensions and local Python face verification runtime
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libjpeg62-turbo-dev \
        libpng-dev \
        libfreetype6-dev \
        python3 \
        python3-pip \
        python3-venv \
        libglib2.0-0 \
        libgl1 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Apache settings for local app routing
RUN a2enmod rewrite

# Copy the app source
COPY . /var/www/html

# Install Python dependencies used by python/verify_face.py (best-effort)
# If a package fails on a specific host, app still boots and face engine can be adjusted.
RUN if [ -f /var/www/html/python/requirements.txt ]; then \
      pip3 install --no-cache-dir -r /var/www/html/python/requirements.txt || true; \
    fi

EXPOSE 80
