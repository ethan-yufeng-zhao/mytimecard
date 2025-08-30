#!/bin/bash


# php 7.4 for ubuntu
sudo apt install apache2 libapache2-mod-php7.4
sudo apt install php7.4-amqp php7.4-bz2 php7.4-cgi php7.4-cli php7.4-common php7.4-curl php7.4-fpm php7.4-gd php7.4-http php7.4-json php7.4-ldap php7.4-mbstring php7.4-pgsql php7.4-mysql php7.4-odbc php7.4-snmp php7.4-xdebug php7.4-uuid php7.4-xml php7.4-xmlrpc php7.4-xsl php7.4-zip php7.4-ssh2
sudo systemctl restart apache2
sudo systemctl restart php7.4-fpm.service

# php 8.3 for ubuntu
sudo apt install apache2 libapache2-mod-php8.3
sudo apt install php8.3-amqp php8.3-bz2 php8.3-cgi php8.3-cli php8.3-common php8.3-curl php8.3-fpm php8.3-gd php8.3-http php8.3-ldap php8.3-mbstring php8.3-pgsql php8.3-mysql php8.3-odbc php8.3-snmp php8.3-xdebug php8.3-uuid php8.3-xml php8.3-xmlrpc php8.3-xsl php8.3-zip php8.3-ssh2
sudo systemctl restart apache2
sudo systemctl restart php8.3-fpm.service

# php 7.2 for RHEL
sudo dnf install  php-bz2 php-cgi php-cli php-common php-curl php-fpm php-gd  php-json php-ldap php-mbstring php-pgsql  php-odbc php-snmp  php-xml php-xmlrpc php-xsl php-zip
sudo systemctl restart httpd
