FROM solr:6.6

LABEL maintainer="adrien.dupuis@ez.no"
LABEL description="Solr 6.6 with eZ Platform's schema"

RUN cp -r /opt/solr/server/solr/configsets/basic_configs /opt/solr/server/solr/configsets/ezplatform \
    && sed -i '/<updateRequestProcessorChain name="add-unknown-fields-to-the-schema">/,/<\/updateRequestProcessorChain>/d' /opt/solr/server/solr/configsets/ezplatform/conf/solrconfig.xml \
    && sed -i -e 's/${solr.autoSoftCommit.maxTime:-1}/${solr.autoSoftCommit.maxTime:20}/' /opt/solr/server/solr/configsets/ezplatform/conf/solrconfig.xml \
    && rm /opt/solr/server/solr/configsets/ezplatform/conf/managed-schema

# This means that `composer install` must have been executed earlier
COPY --chown=solr:solr vendor/ezsystems/ezplatform-solr-search-engine/lib/Resources/config/solr /opt/solr/server/solr/configsets/ezplatform/conf
