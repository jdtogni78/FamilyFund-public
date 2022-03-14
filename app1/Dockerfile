# syntax=docker/dockerfile:1
FROM docker.io/bitnami/laravel:8

RUN apt-get update && apt-get install -y \
    git \
    golang-go \
    sendmail

RUN go get github.com/mailhog/mhsendmail && \
    cp ~/go/bin/mhsendmail /usr/local/bin/

RUN apt-get remove -y \
    git \
    golang-go

RUN sed -i.bkp 's/^;sendmail_path.*=/sendmail_path = "\/usr\/local\/bin\/mhsendmail --smtp-addr=mailhog:1025"/gi'  \
    /opt/bitnami/php/etc/php.ini
