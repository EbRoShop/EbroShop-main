# Use a standard PHP image with Apache
FROM php:8.2-apache

# Install the MySQL extension so PHP can talk to Aiven
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy your website files into the server folder
COPY . /var/www/html/

# Tell Apache to listen on port 80 (Render will handle the rest)
EXPOSE 80