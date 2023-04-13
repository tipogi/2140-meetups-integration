FROM php:7.4-cli
COPY . /usr/src/2140_geo
WORKDIR /usr/src/2140_geo
CMD [ "php", "./index.php" ]