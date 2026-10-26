FROM php:8.2-apache

# Serve the bloodbank/ folder; honour the PORT the host provides (Render uses 10000).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/bloodbank
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && a2enmod rewrite headers

COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

CMD ["sh", "-c", "sed -ri \"s/Listen 80/Listen ${PORT:-80}/; s/:80>/:${PORT:-80}>/\" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
