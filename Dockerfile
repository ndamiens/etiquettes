FROM debian:jessie
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y libapache2-mod-php5 apache2 inkscape
ADD index.php /var/www/html
ADD etiquettes.php /var/www/html

EXPOSE 80

ENTRYPOINT ["apachectl", "-DFOREGROUND"]

