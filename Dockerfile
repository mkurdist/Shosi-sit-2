FROM wordpress:php8.3-apache
RUN set -eux; \
    apt-get update; apt-get install -y --no-install-recommends unzip curl ca-certificates; rm -rf /var/lib/apt/lists/*; \
    curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; chmod +x /usr/local/bin/wp; \
    cd /usr/src/wordpress/wp-content; \
    curl -fsSL -o /tmp/wc.zip https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip; unzip -q /tmp/wc.zip -d plugins; \
    curl -fsSL -o /tmp/pw.zip https://downloads.wordpress.org/plugin/persian-woocommerce.latest-stable.zip && unzip -q /tmp/pw.zip -d plugins || echo "persian-woocommerce skipped"; \
    curl -fsSL -o /tmp/sf.zip https://downloads.wordpress.org/theme/storefront.latest-stable.zip; unzip -q /tmp/sf.zip -d themes; \
    rm -f /tmp/*.zip; a2enmod expires headers deflate
COPY php.ini /usr/local/etc/php/conf.d/zz-shop.ini
COPY apache.conf /etc/apache2/conf-available/zz-shop.conf
RUN a2enconf zz-shop
COPY wp-config-extra.php health.txt /usr/src/wordpress/
COPY shop-core.php card-to-card.php /usr/src/wordpress/wp-content/mu-plugins/
COPY entrypoint.sh setup.sh seed.php products.json /setup/
RUN sed -i 's/\r$//' /setup/*.sh && chmod +x /setup/*.sh
ENV WORDPRESS_CONFIG_EXTRA="require_once '/var/www/html/wp-config-extra.php';"
ENTRYPOINT ["/setup/entrypoint.sh"]
CMD ["apache2-foreground"]
