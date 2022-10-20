FROM michaelfbryan/mdbook-docker-image
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update
RUN apt-get install -y --no-install-recommends nodejs npm mariadb-client
RUN apt-get autoclean -y
RUN apt-get autoremove -y

WORKDIR /app
RUN npm install -g sql-ddl-to-json-schema
