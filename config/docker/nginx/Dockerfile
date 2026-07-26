FROM nginx:1.30-alpine

RUN rm /etc/nginx/conf.d/default.conf
COPY config/docker/nginx/config/backend-integration-demo.conf /etc/nginx/conf.d/

WORKDIR /var/www/backend-integration-demo.test/public

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
