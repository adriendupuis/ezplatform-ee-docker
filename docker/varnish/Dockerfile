FROM varnish:6.0

LABEL maintainer="adrien.dupuis@ez.no"
LABEL description="Varnish 6.0 with Varnish Modules for eZ Platform usage"

#ENV HTTPCACHE_VARNISH_INVALIDATE_TOKEN=docker

# Varnish Modules — https://github.com/varnish/varnish-modules/blob/0.15.0/README.rst#installation
ARG VMODS_VERSION=0.15.0
RUN apt-get update && apt-get install -y make pkg-config varnish-dev wget \
    && wget --quiet https://download.varnish-software.com/varnish-modules/varnish-modules-${VMODS_VERSION}.tar.gz \
    && tar -xzf varnish-modules-${VMODS_VERSION}.tar.gz \
    && cd varnish-modules-${VMODS_VERSION} \
    && ./configure \
    && make && make install \
    && cd .. \
    && rm -r varnish-modules-${VMODS_VERSION}.tar.gz varnish-modules-${VMODS_VERSION}

# VCL
RUN wget --no-verbose https://raw.githubusercontent.com/ezsystems/ezplatform-http-cache/v2.0.0/docs/varnish/vcl/varnish5.vcl --output-document=default.vcl
COPY parameters.vcl parameters.vcl
RUN sed -i 's/X-Forwarded-Port = "80"/X-Forwarded-Port = "8080"/' default.vcl
