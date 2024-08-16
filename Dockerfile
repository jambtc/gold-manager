# Utilizza l'immagine ufficiale PHP 7.4 con Apache
FROM php:7.4-apache

# Installa l'estensione mysqli
RUN docker-php-ext-install mysqli

# Copia il tuo codice sorgente nella cartella predefinita di Apache
COPY . /var/www/html/

# Esponi la porta 80 per il server web
EXPOSE 80

# Imposta il comando di avvio di Apache
CMD ["apache2-foreground"]
