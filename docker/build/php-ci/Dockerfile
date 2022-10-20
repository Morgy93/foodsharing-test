FROM php:8.1.8-fpm-bullseye

RUN apt-get update && \
    apt-get install -y \
      apt-transport-https \
      ca-certificates \
      rsync \
      curl \
      gnupg2 \
      software-properties-common \
      unzip \
      apt-utils python3 python3-pip openssh-client

RUN curl -fsSL https://download.docker.com/linux/$(. /etc/os-release; echo "$ID")/gpg | apt-key add - && \
    add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/$(. /etc/os-release; echo "$ID") $(lsb_release -cs) stable" && \
    (curl -sL https://deb.nodesource.com/setup_16.x | bash -) && \
    (curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -) && \
    (echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list)

RUN apt-get update && apt-get install -y \
      nodejs \
      yarn \
      docker-ce

RUN (curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer)
RUN pip3 install --no-cache-dir docker-compose

RUN rm -rf /usr/include /usr/share/man /tmp/* /root/.cache
RUN rm -rf /usr/lib/python3.7/idlelib /usr/lib/python3.7/__pycache__
RUN rm -rf /usr/bin/docker-containerd*
