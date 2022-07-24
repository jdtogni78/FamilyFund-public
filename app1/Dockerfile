# syntax=docker/dockerfile:1
FROM docker.io/bitnami/laravel:8

RUN apt-get update

RUN apt-get install -y \
    git \
    golang-go \
    sendmail \
    supervisor

RUN go get github.com/mailhog/mhsendmail \
    && cp ~/go/bin/mhsendmail /usr/local/bin/

RUN curl -sLO https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb \
    && apt install -y ./wkhtmltox_0.12.6-1.buster_amd64.deb \
    && rm wkhtmltox_0.12.6-1.buster_amd64.deb

RUN apt-get remove -y \
    git \
    golang-go

RUN sed -i.bkp 's/^;sendmail_path.*=/sendmail_path = "\/usr\/local\/bin\/mhsendmail --smtp-addr=mailhog:1025"/gi'  \
    /opt/bitnami/php/etc/php.ini

# TODO TEST SUPERVISOR
COPY supervisor.conf /etc/supervisor/conf.d