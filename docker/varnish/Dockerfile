FROM ghcr.io/emgag/varnish:6.0.10

RUN apt-get update && apt-get install -y wget

WORKDIR /etc/varnish

RUN wget --no-verbose https://raw.githubusercontent.com/ezsystems/ezplatform-http-cache/1.0/docs/varnish/vcl/varnish5.vcl --output-document=default.vcl
COPY parameters.vcl parameters.vcl
RUN sed -i 's/X-Forwarded-Port = "80"/X-Forwarded-Port = "8080"/' default.vcl
RUN sed -i 's/include "parameters.vcl";/#include "parameters.vcl";/' default.vcl \
  && sed -i '/include "parameters.vcl";/ r parameters.vcl' default.vcl;
