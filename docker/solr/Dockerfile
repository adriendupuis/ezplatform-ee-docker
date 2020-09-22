FROM solr:7.7

LABEL maintainer="adrien.dupuis@ibexa.co"
LABEL description="Solr 7.7 with eZ Platform's schema"

RUN cp -r /opt/solr/server/solr/configsets/_default /opt/solr/server/solr/configsets/ezplatform \
    && sed -i '/<updateRequestProcessorChain name="add-unknown-fields-to-the-schema">/,/<\/updateRequestProcessorChain>/d' /opt/solr/server/solr/configsets/ezplatform/conf/solrconfig.xml \
    && sed -i -e 's/${solr.autoSoftCommit.maxTime:-1}/${solr.autoSoftCommit.maxTime:20}/' /opt/solr/server/solr/configsets/ezplatform/conf/solrconfig.xml \
    && rm /opt/solr/server/solr/configsets/ezplatform/conf/managed-schema

# This means that `composer install` must have been executed earlier
COPY --chown=solr:solr conf /opt/solr/server/solr/configsets/ezplatform/conf
